<?php

declare(strict_types=1);

/*
 * Zero-dependency test runner (the shared host has no Composer): php tests/run.php
 * Exit status 1 when any test fails.
 */

$root = dirname(__DIR__);
require $root . '/src/bootstrap.php';

use Portfolio\App;
use Portfolio\Contact;
use Portfolio\Content;
use Portfolio\Response;

$failed = 0;
$passed = 0;

function test(string $name, callable $body): void
{
    global $failed, $passed;
    try {
        $body();
        $passed++;
        fwrite(STDOUT, "ok    {$name}\n");
    } catch (Throwable $error) {
        $failed++;
        fwrite(STDOUT, "FAIL  {$name}\n      {$error->getMessage()}\n");
    }
}

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function app(array $overrides = []): App
{
    static $varDir = null;
    $varDir ??= sys_get_temp_dir() . '/portfolio-test-' . bin2hex(random_bytes(4));

    return new App(dirname(__DIR__), $overrides + [
        'env' => 'production',
        'base_url' => 'https://example.test',
        'noindex' => false,
        'mail_mode' => 'log',
        'form_secret' => 'test-secret',
        'var_dir' => $varDir,
        'revision' => '',
    ]);
}

function dom(string $html): DOMDocument
{
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_NONET);
    libxml_clear_errors();

    return $doc;
}

/** Structure of a decoded JSON value: keys, list lengths and scalar types. */
function shape(mixed $value): mixed
{
    if (!is_array($value)) {
        return gettype($value);
    }
    if (array_is_list($value)) {
        return array_map('shape', $value);
    }
    ksort($value);

    return array_map('shape', $value);
}

// ---------- Content ----------

test('content files share one shape in every language', function () use ($root): void {
    $en = Content::load($root, 'en');
    $es = Content::load($root, 'es');
    expect(shape($en) === shape($es), 'en.json and es.json differ in structure (keys, list lengths or types)');
});

test('pins: unique ids, seven per side, every pin has evidence', function () use ($root): void {
    foreach (Content::LANGS as $lang) {
        $c = Content::load($root, $lang);
        $ids = array_column($c['pins'], 'id');
        expect(count($ids) === count(array_unique($ids)), "{$lang}: duplicate pin id");
        $sides = Content::pinSides($c);
        expect(count($sides['left']) === 7 && count($sides['right']) === 7, "{$lang}: the chip needs 7 pins per side");
        foreach (Content::evidence($c) as $pin => $items) {
            expect($items !== [], "{$lang}: pin {$pin} has no evidence");
        }
    }
});

test('ids and tags match across languages', function () use ($root): void {
    $collect = static function (array $c): array {
        $out = [array_column($c['pins'], 'id')];
        foreach ($c['experience']['items'] as $item) {
            $out[] = $item['id'];
            $out[] = array_map(static fn ($h) => $h['tags'], $item['highlights'] ?? []);
            foreach ($item['engagements'] ?? [] as $g) {
                $out[] = [$g['id'], array_map(static fn ($h) => $h['tags'], $g['highlights'])];
            }
        }
        $out[] = array_map(static fn ($r) => [$r['name'], $r['url'], $r['tags'], $r['stars']], $c['code']['repos']);
        $out[] = array_map(static fn ($d) => $d['tags'], $c['education']['degrees']);

        return $out;
    };
    expect($collect(Content::load($root, 'en')) === $collect(Content::load($root, 'es')), 'ids, tags or repo data differ between en and es');
});

test('no empty strings in content', function () use ($root): void {
    foreach (Content::LANGS as $lang) {
        $content = Content::load($root, $lang);
        array_walk_recursive($content, static function ($value, $key) use ($lang): void {
            expect(!is_string($value) || trim($value) !== '', "{$lang}: empty string at key {$key}");
        });
    }
});

// ---------- Routes and headers ----------

test('home pages render in each language with alternates', function (): void {
    foreach (['/' => 'en', '/es/' => 'es'] as $path => $lang) {
        $response = app()->handle('GET', $path);
        expect($response->status === 200, "{$path}: status {$response->status}");
        expect(str_contains($response->body, '<html lang="' . $lang . '">'), "{$path}: wrong lang attribute");
        expect(str_contains($response->body, 'hreflang="es" href="https://example.test/es/"'), "{$path}: missing es alternate");
        expect(str_contains($response->body, 'rel="canonical" href="https://example.test' . $path . '"'), "{$path}: wrong canonical");
    }
});

test('redirects, 404s and method checks', function (): void {
    $r = app()->handle('GET', '/es');
    expect($r->status === 301 && $r->header('Location') === '/es/', '/es should redirect to /es/');
    $r = app()->handle('GET', '/missing');
    expect($r->status === 404 && str_contains($r->body, 'lang="en"'), 'unknown path should be an English 404');
    $r = app()->handle('GET', '/es/falta');
    expect($r->status === 404 && str_contains($r->body, 'lang="es"'), 'unknown /es/ path should be a Spanish 404');
    $r = app()->handle('DELETE', '/');
    expect($r->status === 405, 'DELETE / should be 405');
    $r = app()->handle('HEAD', '/');
    expect($r->status === 200, 'HEAD / should be 200');
});

test('security headers on every response', function (): void {
    foreach (['/', '/missing', '/robots.txt', '/es'] as $path) {
        $r = app()->handle('GET', $path);
        expect(str_contains((string) $r->header('Content-Security-Policy'), "script-src 'self'"), "{$path}: CSP missing");
        expect($r->header('X-Content-Type-Options') === 'nosniff', "{$path}: nosniff missing");
        expect($r->header('X-Frame-Options') === 'DENY', "{$path}: X-Frame-Options missing");
        expect($r->header('Strict-Transport-Security') !== null, "{$path}: HSTS missing in production");
    }
    $staging = app(['env' => 'staging', 'noindex' => true])->handle('GET', '/');
    expect($staging->header('X-Robots-Tag') === 'noindex, nofollow', 'staging must send noindex');
    expect($staging->header('Strict-Transport-Security') === null, 'staging must not send HSTS');
    expect($staging->header('Cache-Control') === 'no-store', 'staging must not be cached');
    expect(str_contains($staging->body, 'stage-banner'), 'staging shows its banner');
});

test('robots.txt and sitemap.xml', function (): void {
    $robots = app()->handle('GET', '/robots.txt')->body;
    expect(str_contains($robots, 'Sitemap: https://example.test/sitemap.xml'), 'production robots.txt lists the sitemap');
    $closed = app(['noindex' => true])->handle('GET', '/robots.txt')->body;
    expect(str_contains($closed, 'Disallow: /'), 'staging robots.txt disallows everything');
    $sitemap = app()->handle('GET', '/sitemap.xml');
    expect(simplexml_load_string($sitemap->body) !== false, 'sitemap is valid XML');
    expect(substr_count($sitemap->body, '<loc>') === 2, 'sitemap lists both languages');
});

// ---------- HTML hygiene ----------

test('HTML: unique ids, working anchors, one h1, no inline styles or scripts', function (): void {
    foreach (['/', '/es/'] as $path) {
        $html = app()->handle('GET', $path)->body;
        $doc = dom($html);
        $xpath = new DOMXPath($doc);

        $ids = [];
        foreach ($xpath->query('//*[@id]') as $node) {
            $id = $node->getAttribute('id');
            expect(!isset($ids[$id]), "{$path}: duplicate id {$id}");
            $ids[$id] = true;
        }
        foreach ($xpath->query('//a[starts-with(@href, "#")]') as $link) {
            $target = substr($link->getAttribute('href'), 1);
            expect(isset($ids[$target]), "{$path}: link to missing #{$target}");
        }
        expect($xpath->query('//h1')->length === 1, "{$path}: expected exactly one h1");
        expect($xpath->query('//*[@style]')->length === 0, "{$path}: inline style attribute (blocked by the CSP)");
        foreach ($xpath->query('//script') as $script) {
            $type = $script->getAttribute('type');
            expect($script->hasAttribute('src') || $type === 'application/ld+json', "{$path}: inline script (blocked by the CSP)");
        }
        foreach ($xpath->query('//img') as $img) {
            expect($img->hasAttribute('alt'), "{$path}: img without alt");
        }
        foreach ($xpath->query('//input[not(@type="hidden")] | //textarea') as $field) {
            $id = $field->getAttribute('id');
            expect($id !== '' && $xpath->query('//label[@for="' . $id . '"]')->length === 1, "{$path}: field {$id} has no label");
        }
    }
});

test('HTML: heading levels never skip', function (): void {
    foreach (['/', '/es/'] as $path) {
        $doc = dom(app()->handle('GET', $path)->body);
        $previous = 0;
        foreach ((new DOMXPath($doc))->query('//h1|//h2|//h3|//h4|//h5|//h6') as $heading) {
            $level = (int) substr($heading->nodeName, 1);
            expect($level <= $previous + 1, "{$path}: h{$previous} is followed by h{$level} (\"" . trim($heading->textContent) . '")');
            $previous = $level;
        }
    }
});

test('JSON-LD parses and names no employer', function (): void {
    $html = app()->handle('GET', '/')->body;
    expect(preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m) === 1, 'JSON-LD block missing');
    $data = json_decode($m[1], true, 32, JSON_THROW_ON_ERROR);
    expect($data['mainEntity']['@type'] === 'Person', 'JSON-LD describes a Person');
    expect(!isset($data['mainEntity']['worksFor']) && !isset($data['mainEntity']['alumniOf']), 'JSON-LD must not name organizations');
});

test('every pin links to its evidence list', function (): void {
    $doc = dom(app()->handle('GET', '/')->body);
    $xpath = new DOMXPath($doc);
    $pins = $xpath->query('//a[contains(@class, "pin__link")]');
    expect($pins->length === 14, 'expected 14 pins, found ' . $pins->length);
    foreach ($pins as $pin) {
        $target = substr($pin->getAttribute('href'), 1);
        $details = $doc->getElementById($target);
        expect($details !== null && $details->nodeName === 'details', "pin {$target} has no evidence list");
        expect((int) $pin->getAttribute('data-count') > 0, "pin {$target} has a zero count");
    }
});

// ---------- Contact form ----------

/** A POST that passes every check unless a test overrides a field. */
function post(array $fields = [], ?App $app = null, string $ip = '203.0.113.7'): Response
{
    $app ??= app();
    $token = (new Contact($app->config()))->token(time() - 10);

    return $app->handle('POST', '/contact', $fields + [
        'lang' => 'en',
        'token' => $token,
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.test',
        'org' => '',
        'message' => 'Hello, I would like to talk about a senior role on our team.',
        'website' => '',
    ], ['REMOTE_ADDR' => $ip]);
}

function mailLog(App $app): array
{
    $file = $app->config()['var_dir'] . '/mail.log';

    return is_file($file) ? array_filter(explode("\n", (string) file_get_contents($file))) : [];
}

test('contact: a valid message is logged and redirects back', function (): void {
    $app = app();
    $before = count(mailLog($app));
    $r = post([], $app, '203.0.113.10');
    expect($r->status === 303 && $r->header('Location') === '/?sent=1#contact', 'expected 303 to /?sent=1#contact, got ' . $r->status);
    expect(count(mailLog($app)) === $before + 1, 'message was not delivered to the log');
    $es = post(['lang' => 'es'], $app, '203.0.113.11');
    expect($es->header('Location') === '/es/?sent=1#contact', 'Spanish form returns to /es/');
    $page = $app->handle('GET', '/?sent=1');
    expect(str_contains($page->body, 'notice--ok'), 'the sent page shows the confirmation');
});

test('contact: honeypot is accepted silently and sends nothing', function (): void {
    $app = app();
    $before = count(mailLog($app));
    $r = post(['website' => 'https://spam.example'], $app, '203.0.113.12');
    expect($r->status === 303, 'honeypot should look like success');
    expect(count(mailLog($app)) === $before, 'honeypot submission must not be delivered');
});

test('contact: validation errors keep the values and point at the fields', function (): void {
    $r = post(['name' => '', 'email' => 'not-an-email', 'message' => 'short'], null, '203.0.113.13');
    expect($r->status === 422, 'expected 422, got ' . $r->status);
    expect(str_contains($r->body, 'aria-invalid="true" aria-describedby="error-email"'), 'email field not marked invalid');
    expect(str_contains($r->body, 'id="error-message"'), 'message error not shown');
    expect(str_contains($r->body, 'value="not-an-email"'), 'entered values must be kept');
    expect($r->header('Cache-Control') === 'no-store', 'error pages are not cacheable');
});

test('contact: too fast, forged or expired tokens are refused', function (): void {
    $app = app();
    $contact = new Contact($app->config());
    expect(post(['token' => $contact->token()], $app)->status === 422, 'a token from this second is too fast');
    expect(post(['token' => (time() - 10) . '.' . str_repeat('0', 32)], $app)->status === 422, 'forged signature accepted');
    expect(post(['token' => $contact->token(time() - Contact::MAX_AGE - 5)], $app)->status === 422, 'expired token accepted');
    expect(post(['token' => ''], $app)->status === 422, 'missing token accepted');
});

test('contact: header injection is neutralized', function (): void {
    $app = app();
    $r = post(['name' => "Eve\r\nBcc: victim@example.test", 'email' => "eve@example.test\r\nBcc: x@example.test"], $app, '203.0.113.14');
    expect($r->status === 422, 'an email address with a line break must be rejected');
    $r = post(['name' => "Eve\r\nBcc: victim@example.test"], $app, '203.0.113.15');
    expect($r->status === 303, 'a name with a line break is cleaned, not rejected');
    $log = mailLog($app);
    $last = json_decode((string) end($log), true);
    expect(!str_contains($last['body'], "\nBcc:"), 'line break survived into the message header block');
});

test('contact: hourly limit per connection', function (): void {
    $app = app();
    $ip = '198.51.100.' . random_int(1, 254);
    for ($i = 0; $i < Contact::MAX_PER_HOUR; $i++) {
        expect(post([], $app, $ip)->status === 303, 'message ' . ($i + 1) . ' should pass');
    }
    $r = post([], $app, $ip);
    expect($r->status === 429 && $r->header('Retry-After') === '3600', 'the next message should be limited');
});

test('contact: disabled form says so and never pretends to send', function (): void {
    $app = app(['mail_mode' => 'off']);
    expect(str_contains($app->handle('GET', '/')->body, 'The form is offline right now'), 'disabled form should explain itself');
    expect(post([], $app)->status === 503, 'posting to a disabled form should be 503');
});

// ---------- Done ----------

$total = $passed + $failed;
fwrite(STDOUT, "\n{$passed}/{$total} passed\n");
exit($failed > 0 ? 1 : 0);
