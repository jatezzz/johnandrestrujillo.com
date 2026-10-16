<?php

declare(strict_types=1);

namespace Portfolio;

final class App
{
    /** @var array<string, mixed> */
    private array $config;
    private View $view;
    private Metrics $metrics;

    /** @param array<string, mixed> $overrides */
    public function __construct(private readonly string $root, array $overrides = [])
    {
        $this->config = Config::load($root, $overrides);
        $this->view = new View($root . '/templates');
        $this->metrics = new Metrics($this->config);
    }

    public function metrics(): Metrics
    {
        return $this->metrics;
    }

    /** Work that should not delay the response: sending queued metrics. */
    public function finish(): void
    {
        $this->metrics->flush();
    }

    /** @return array<string, mixed> */
    public function config(): array
    {
        return $this->config;
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, mixed> $server
     */
    public function handle(string $method, string $uri, array $post = [], array $server = [], string $body = ''): Response
    {
        $method = strtoupper($method);
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) && $path !== '' ? $path : '/';
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);

        try {
            $response = match ($path) {
                '/' => $this->home('en', $method, $query),
                '/es/' => $this->home('es', $method, $query),
                '/es', '/index.php' => Response::redirect($path === '/es' ? '/es/' : '/', 301),
                '/contact' => $this->contact($method, $post, $server),
                '/contact/challenge' => $this->challenge($method, $server),
                '/e' => $this->beacon($method, $body, $server),
                '/robots.txt' => $this->robots(),
                '/sitemap.xml' => $this->sitemap(),
                '/healthz' => Response::text("ok\n")->withHeader('Cache-Control', 'no-store'),
                '/og/en', '/og/es' => $this->card(substr($path, 4), $path),
                default => $this->notFound($path),
            };
        } catch (\Throwable $error) {
            error_log(sprintf('portfolio: %s: %s at %s:%d', $error::class, $error->getMessage(), $error->getFile(), $error->getLine()));
            $this->metrics->capture('server_error', [
                '$pathname' => $path,
                'type' => $error::class,
                'where' => basename($error->getFile()) . ':' . $error->getLine(),
            ], (string) ($server['REMOTE_ADDR'] ?? ''), (string) ($server['HTTP_USER_AGENT'] ?? ''));
            $response = Response::text("Something went wrong on this page. Try again in a moment.\n", 500)
                ->withHeader('Cache-Control', 'no-store');
        }

        return $this->secure($response);
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $form
     */
    private function home(string $lang, string $method, array $query, array $form = [], int $status = 200): Response
    {
        if ($method !== 'GET' && $method !== 'HEAD') {
            return Response::text("Method not allowed.\n", 405)->withHeader('Allow', 'GET, HEAD');
        }
        $form += ['values' => [], 'errors' => [], 'sent' => isset($query['sent']), 'captcha' => null];
        // A rendered challenge makes the page personal: never cache it.
        if ($form['captcha'] === null && isset($query['challenge']) && !$form['sent']) {
            $form['captcha'] = (new Contact($this->config))->captcha()?->issue();
        }
        $cacheable = $status === 200 && !$form['sent'] && $form['captcha'] === null;

        return Response::html($this->page($lang, 'home', ['form' => $form]), $status)
            ->withHeader('Cache-Control', $this->cacheControl($cacheable));
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, mixed> $server
     */
    private function contact(string $method, array $post, array $server): Response
    {
        if ($method !== 'POST') {
            return Response::redirect('/#contact', 303);
        }
        $lang = ($post['lang'] ?? '') === 'es' ? 'es' : 'en';
        $home = $lang === 'es' ? '/es/' : '/';

        $contact = new Contact($this->config);
        $result = $contact->submit($post, (string) ($server['REMOTE_ADDR'] ?? ''));
        $this->metrics->capture('contact_submitted', [
            '$pathname' => $home,
            'result' => $result['ok'] ? 'sent' : (string) (reset($result['errors']) ?: 'error'),
            'status' => $result['status'],
        ], (string) ($server['REMOTE_ADDR'] ?? ''), (string) ($server['HTTP_USER_AGENT'] ?? ''));
        if ($result['ok']) {
            return Response::redirect($home . '?sent=1#contact', 303);
        }

        $response = $this->home($lang, 'GET', [], [
            'values' => $result['values'],
            'errors' => $result['errors'],
            'captcha' => $contact->captcha()?->issue(),
        ], $result['status']);

        return $result['status'] === 429 ? $response->withHeader('Retry-After', '3600') : $response;
    }

    /** A fresh image challenge for the contact form, fetched by site.js. */
    private function challenge(string $method, array $server): Response
    {
        if ($method !== 'GET') {
            return Response::text("Method not allowed.\n", 405)->withHeader('Allow', 'GET');
        }
        $captcha = (new Contact($this->config))->captcha();
        if ($captcha === null) {
            return Response::text("Not available.\n", 404)->withHeader('Cache-Control', 'no-store');
        }
        $limit = new RateLimit((string) $this->config['var_dir'], (string) $this->config['form_secret']);
        if (!$limit->allow('challenge', (string) ($server['REMOTE_ADDR'] ?? ''), 60, 3600)) {
            return Response::text("Too many requests.\n", 429)->withHeader('Retry-After', '3600');
        }

        return Response::text((string) json_encode($captcha->issue(), JSON_UNESCAPED_SLASHES), 200, 'application/json')
            ->withHeader('Cache-Control', 'no-store');
    }

    /** Anonymous metrics posted by site.js (see Metrics). */
    private function beacon(string $method, string $body, array $server): Response
    {
        if ($method !== 'POST') {
            return Response::text("Method not allowed.\n", 405)->withHeader('Allow', 'POST');
        }
        $status = $this->metrics->fromBrowser($body, (string) ($server['REMOTE_ADDR'] ?? ''), (string) ($server['HTTP_USER_AGENT'] ?? ''));

        return new Response($status, ['Cache-Control' => 'no-store']);
    }

    private function robots(): Response
    {
        $body = $this->config['noindex']
            ? "User-agent: *\nDisallow: /\n"
            : "User-agent: *\nAllow: /\n\nSitemap: {$this->config['base_url']}/sitemap.xml\n";

        return Response::text($body)->withHeader('Cache-Control', $this->cacheControl(true));
    }

    private function sitemap(): Response
    {
        $base = $this->config['base_url'];
        $updated = Content::load($this->root, 'en')['updated'];
        $urls = ['en' => $base . '/', 'es' => $base . '/es/'];
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
        foreach ($urls as $url) {
            $xml .= "  <url>\n    <loc>" . View::e($url) . "</loc>\n    <lastmod>" . View::e($updated) . "</lastmod>\n";
            foreach ($urls as $alt => $altUrl) {
                $xml .= '    <xhtml:link rel="alternate" hreflang="' . $alt . '" href="' . View::e($altUrl) . '"/>' . "\n";
            }
            $xml .= "  </url>\n";
        }
        $xml .= "</urlset>\n";

        return Response::text($xml, 200, 'application/xml; charset=utf-8')->withHeader('Cache-Control', $this->cacheControl(true));
    }

    private function notFound(string $path): Response
    {
        $lang = str_starts_with($path, '/es/') ? 'es' : 'en';

        return Response::html($this->page($lang, '404', ['status' => 404]), 404)->withHeader('Cache-Control', 'no-store');
    }

    /**
     * The social preview card, captured to public/assets/img/og-<lang>.png with a
     * headless browser. Only served outside production.
     */
    private function card(string $lang, string $path): Response
    {
        if ($this->config['env'] === 'production') {
            return $this->notFound($path);
        }
        $c = Content::load($this->root, $lang);
        $html = $this->view->render('og', [
            'c' => $c,
            'lang' => $lang,
            'sides' => Content::pinSides($c),
            'asset' => $this->asset(...),
        ]);

        return Response::html($html)->withHeader('Cache-Control', 'no-store');
    }

    /** @param array<string, mixed> $vars */
    private function page(string $lang, string $template, array $vars): string
    {
        $c = Content::load($this->root, $lang);
        $base = $this->config['base_url'];
        $urls = ['en' => $base . '/', 'es' => $base . '/es/'];
        $contact = new Contact($this->config);

        $vars += [
            'status' => 200,
            'c' => $c,
            'lang' => $lang,
            'urls' => $urls,
            'canonical' => $urls[$lang],
            'site' => [
                'github_url' => $this->config['github_url'],
                'linkedin_url' => $this->config['linkedin_url'],
                'source_url' => $this->config['source_url'],
                'staging' => $this->config['env'] !== 'production',
                'revision' => $this->config['revision'],
                'form_enabled' => $contact->enabled(),
                'form_token' => $contact->token(),
                'captcha' => $contact->captcha() !== null,
                'metrics' => $this->metrics->enabled(),
            ],
            'asset' => $this->asset(...),
            'og_image' => is_file($this->root . "/public/assets/img/og-{$lang}.png") ? $base . $this->asset("img/og-{$lang}.png") : '',
            'touch_icon' => is_file($this->root . '/public/assets/img/apple-touch-icon.png'),
            'evidence' => Content::evidence($c),
            'sides' => Content::pinSides($c),
        ];
        $vars['json_ld'] = $this->jsonLd($c, $lang, $urls);
        $vars['body'] = $this->view->render($template, $vars);

        return $this->view->render('layout', $vars);
    }

    /**
     * @param array<string, mixed> $c
     * @param array<string, string> $urls
     */
    private function jsonLd(array $c, string $lang, array $urls): string
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            'url' => $urls[$lang],
            'inLanguage' => $lang,
            'dateModified' => $c['updated'],
            'mainEntity' => [
                '@type' => 'Person',
                'name' => $c['person']['name'],
                'jobTitle' => $c['person']['role'],
                'url' => $urls['en'],
                'homeLocation' => ['@type' => 'Place', 'name' => $c['person']['location']],
                'knowsLanguage' => ['en', 'es'],
                'knowsAbout' => $c['person']['knows_about'],
                'sameAs' => array_values(array_filter([$this->config['github_url'], $this->config['linkedin_url']])),
            ],
        ];

        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
    }

    /** Public URL of a file under public/assets, versioned by content so it can be cached for a year. */
    private function asset(string $path): string
    {
        static $versions = [];
        $file = $this->root . '/public/assets/' . $path;
        $versions[$file] ??= is_file($file) ? substr(hash_file('sha256', $file), 0, 10) : 'missing';

        return '/assets/' . $path . '?v=' . $versions[$file];
    }

    private function cacheControl(bool $cacheable): string
    {
        return $cacheable && $this->config['env'] === 'production' ? 'public, max-age=300' : 'no-store';
    }

    private function secure(Response $response): Response
    {
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'X-Frame-Options' => 'DENY',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
            'Content-Security-Policy' => "default-src 'none'; script-src 'self'; style-src 'self'; img-src 'self' data:; "
                . "font-src 'self'; connect-src 'self'; manifest-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'",
        ];
        if ($this->config['env'] === 'production' && str_starts_with($this->config['base_url'], 'https://')) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000';
        }
        if ($this->config['noindex']) {
            $headers['X-Robots-Tag'] = 'noindex, nofollow';
        }
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }
}
