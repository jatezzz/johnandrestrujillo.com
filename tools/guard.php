<?php

declare(strict_types=1);

/*
 * Publication guard: fails when published material could tie this site to names
 * that must not appear on it.
 *
 * The term list is private and never committed. Pass it as PORTFOLIO_FORBIDDEN_TERMS
 * (one entry per line; a CI secret) or with --terms-file. Entries:
 *     word or phrase    matched case- and accent-insensitively, spaces match any whitespace
 *     /pattern/         a PCRE pattern, matched case-insensitively
 *     ~entry            checked in this site's material; on linked pages it only warns
 *     # comment
 * The guard never prints a term. Findings name the entry by its line number.
 *
 * Checks:
 *   terms     tracked files, commit messages and rendered pages contain no entry
 *   images    no tracked image carries EXIF, XMP, IPTC or text metadata (GPS, camera, author)
 *   authors   every commit is authored by an allowed address
 *   links     rendered pages link only to allowed external hosts
 *   --fetch   linked external pages (one hop) contain no entry
 *   --overlap FILE  report long phrases shared with the pages listed in FILE
 *                   (one URL per line; private, since the list itself says too much)
 *
 * Exit status: 0 clean, 1 findings, 2 usage or setup error.
 */

$root = dirname(__DIR__);
require $root . '/src/bootstrap.php';

const ALLOWED_HOSTS = ['github.com', 'schema.org', 'www.sitemaps.org', 'www.w3.org'];
const DEFAULT_AUTHORS = ['34223334+jatezzz@users.noreply.github.com'];
const ALLOWED_COMMITTERS = ['noreply@github.com'];
const ALLOWED_COAUTHORS = ['noreply@anthropic.com'];

$options = getopt('', ['terms-file:', 'fetch', 'overlap:', 'allow-missing-terms', 'skip-authors', 'help']);
if (isset($options['help'])) {
    fwrite(STDOUT, "usage: php tools/guard.php [--terms-file FILE] [--fetch] [--overlap FILE] [--skip-authors] [--allow-missing-terms]\n");
    exit(0);
}

$errors = 0;
$warnings = 0;
$fail = static function (string $message) use (&$errors): void {
    $errors++;
    fwrite(STDOUT, "FAIL  {$message}\n");
};
$warn = static function (string $message) use (&$warnings): void {
    $warnings++;
    fwrite(STDOUT, "WARN  {$message}\n");
};
$info = static fn (string $message) => fwrite(STDOUT, "ok    {$message}\n");

// ---------- Term list ----------

$rawTerms = getenv('PORTFOLIO_FORBIDDEN_TERMS') ?: '';
if (isset($options['terms-file'])) {
    $rawTerms = (string) @file_get_contents((string) $options['terms-file']);
    if ($rawTerms === '') {
        fwrite(STDERR, "guard: cannot read the terms file\n");
        exit(2);
    }
}

/** @var list<array{line: int, regex: string, soft: bool}> $terms */
$terms = [];
foreach (preg_split('/\R/', $rawTerms) ?: [] as $index => $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }
    $soft = str_starts_with($line, '~');
    if ($soft) {
        $line = ltrim(substr($line, 1));
    }
    if (strlen($line) > 2 && $line[0] === '/' && str_ends_with($line, '/')) {
        $regex = '/' . substr($line, 1, -1) . '/iu';
    } else {
        $parts = preg_split('/\s+/', normalize($line)) ?: [];
        $regex = '/' . implode('\s+', array_map(static fn (string $p): string => preg_quote($p, '/'), $parts)) . '/u';
    }
    if (@preg_match($regex, '') === false) {
        fwrite(STDERR, 'guard: entry on line ' . ($index + 1) . " is not a valid pattern\n");
        exit(2);
    }
    $terms[] = ['line' => $index + 1, 'regex' => $regex, 'soft' => $soft];
}

if ($terms === []) {
    if (!isset($options['allow-missing-terms'])) {
        fwrite(STDERR, "guard: no term list (set PORTFOLIO_FORBIDDEN_TERMS or pass --terms-file)\n");
        exit(2);
    }
    $warn('no term list given; term checks skipped');
}

/** Lowercase, strip accents and invisible characters, decode entities and %-escapes. */
function normalize(string $text): string
{
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = rawurldecode($text);
    $text = (string) preg_replace('/[\x{00AD}\x{200B}-\x{200F}\x{2060}-\x{2064}\x{FEFF}]/u', '', $text);
    if (class_exists(Normalizer::class)) {
        $text = (string) Normalizer::normalize($text, Normalizer::FORM_KD);
        $text = (string) preg_replace('/\p{Mn}+/u', '', $text);
    } else {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = $ascii === false ? $text : $ascii;
    }

    return mb_strtolower($text, 'UTF-8');
}

/** @return list<array{line: int, soft: bool}> entries found in $text */
function matches(string $text, array $terms): array
{
    $found = [];
    $plain = normalize($text);
    foreach ($terms as $term) {
        if (preg_match($term['regex'], $plain) === 1 || preg_match($term['regex'], $text) === 1) {
            $found[] = ['line' => $term['line'], 'soft' => $term['soft']];
        }
    }

    return $found;
}

/** Prints a location only when it contains no entry itself. */
function safe(string $label, array $terms): string
{
    return matches($label, $terms) === [] ? $label : '[redacted: matches the term list]';
}

/** NUL-terminated git log records, trimmed (git adds a newline after each one). */
function records(string $output): array
{
    return array_values(array_filter(array_map('trim', explode("\0", $output)), static fn (string $r): bool => $r !== ''));
}

function git(string $root, string $args): string
{
    $output = shell_exec('git -C ' . escapeshellarg($root) . ' ' . $args . ' 2>/dev/null');

    return is_string($output) ? $output : '';
}

// ---------- Rendered pages ----------

$app = new Portfolio\App($root, [
    'env' => 'production',
    'noindex' => false,
    'mail_mode' => 'log',
    'form_secret' => 'guard',
    'var_dir' => sys_get_temp_dir() . '/portfolio-guard',
    'revision' => '',
]);
$pages = [];
foreach (['/', '/es/', '/robots.txt', '/sitemap.xml', '/does-not-exist', '/es/no-existe'] as $path) {
    $pages[$path] = $app->handle('GET', $path)->body;
}

// ---------- 1. Terms ----------

if ($terms !== []) {
    $hits = 0;
    $report = static function (string $where, string $text) use ($terms, $fail, &$hits): void {
        foreach (matches($text, $terms) as $match) {
            $hits++;
            $fail("term #{$match['line']} in " . safe($where, $terms));
        }
    };

    $tracked = array_filter(explode("\0", git($root, 'ls-files -z')));
    if ($tracked === []) {
        $fail('git ls-files returned nothing (run inside the repository)');
    }
    foreach ($tracked as $path) {
        $report('path ' . $path, $path);
        if (preg_match('/\.(woff2?|ttf|otf)$/i', $path)) {
            continue; // third-party font binaries
        }
        $data = @file_get_contents($root . '/' . $path);
        if ($data === false) {
            continue;
        }
        if (str_contains($data, "\0")) {
            // Binary (images, PDFs): metadata is stored as plain text, so scan printable runs.
            preg_match_all('/[\x20-\x7E]{4,}/', $data, $runs);
            $report($path . ' (metadata)', implode("\n", $runs[0]));
            continue;
        }
        foreach (explode("\n", $data) as $number => $line) {
            if ($line !== '') {
                $report($path . ':' . ($number + 1), $line);
            }
        }
    }

    foreach (records(git($root, 'log ' . escapeshellarg('--format=%H%x1f%B%x00'))) as $record) {
        [$hash, $message] = array_pad(explode("\x1f", $record, 2), 2, '');
        $report('commit ' . substr($hash, 0, 10) . ' message', $message);
    }
    foreach (array_filter(explode("\n", git($root, 'for-each-ref ' . escapeshellarg('--format=%(refname:short)')))) as $ref) {
        $report('ref ' . $ref, $ref);
    }

    foreach ($pages as $path => $body) {
        $report('rendered ' . $path, $body);
    }

    if ($hits === 0) {
        $info(count($terms) . ' entries checked against ' . count($tracked) . ' tracked files, commit messages and ' . count($pages) . ' rendered pages');
    }
}

// ---------- 2. Image metadata ----------

/** Name of the first metadata block found in an image, or null when it has none. */
function imageMetadata(string $data, string $ext): ?string
{
    switch ($ext) {
        case 'png':
            for ($at = 8; $at + 8 <= strlen($data);) {
                $length = unpack('N', substr($data, $at, 4))[1];
                $type = substr($data, $at + 4, 4);
                if (in_array($type, ['tEXt', 'zTXt', 'iTXt', 'eXIf'], true)) {
                    return "PNG {$type} chunk";
                }
                if ($type === 'IEND') {
                    return null;
                }
                $at += 12 + $length;
            }

            return null;
        case 'jpg':
        case 'jpeg':
            for ($at = 2; $at + 4 <= strlen($data) && $data[$at] === "\xFF";) {
                $marker = ord($data[$at + 1]);
                if ($marker === 0xD9 || $marker === 0xDA) {
                    return null; // end of image, or start of the pixel data
                }
                $length = unpack('n', substr($data, $at + 2, 2))[1];
                $segment = substr($data, $at + 4, $length - 2);
                if ($marker === 0xE1 && str_starts_with($segment, "Exif\0")) {
                    return 'EXIF';
                }
                if ($marker === 0xE1 && str_starts_with($segment, 'http://ns.adobe.com/xap/')) {
                    return 'XMP';
                }
                if ($marker === 0xED) {
                    return 'IPTC';
                }
                if ($marker === 0xFE) {
                    return 'JPEG comment';
                }
                $at += 2 + $length;
            }

            return null;
        case 'webp':
            for ($at = 12; $at + 8 <= strlen($data);) {
                $type = substr($data, $at, 4);
                $size = unpack('V', substr($data, $at + 4, 4))[1];
                if ($type === 'EXIF' || $type === 'XMP ') {
                    return 'WebP ' . trim($type) . ' chunk';
                }
                $at += 8 + $size + ($size & 1);
            }

            return null;
        case 'svg':
            return preg_match('/<metadata\b|xmlns:(rdf|dc|cc)=/i', $data) ? 'SVG metadata' : null;
        default:
            return str_contains($data, "Exif\0\0") || str_contains($data, '<x:xmpmeta') ? 'EXIF or XMP' : null;
    }
}

$images = array_filter(
    array_filter(explode("\0", git($root, 'ls-files -z'))),
    static fn (string $path): bool => (bool) preg_match('/\.(png|jpe?g|webp|gif|avif|svg)$/i', $path),
);
$dirty = 0;
foreach ($images as $path) {
    $found = imageMetadata((string) file_get_contents($root . '/' . $path), strtolower(pathinfo($path, PATHINFO_EXTENSION)));
    if ($found !== null) {
        $dirty++;
        $fail(safe($path, $terms) . " carries metadata ({$found}); strip it before publishing");
    }
}
if ($dirty === 0) {
    $info(count($images) . ' tracked images without EXIF, XMP, IPTC or text metadata');
}

// ---------- 3. Authors ----------

if (!isset($options['skip-authors'])) {
    $allowed = array_map('strtolower', array_filter(array_map('trim', explode(',', getenv('PORTFOLIO_AUTHOR_EMAILS') ?: implode(',', DEFAULT_AUTHORS)))));
    $bad = 0;
    $commits = records(git($root, 'log ' . escapeshellarg('--format=%H%x1f%ae%x1f%ce%x1f%(trailers:key=Co-authored-by,valueonly,separator=%x1e)%x00')));
    foreach ($commits as $record) {
        [$hash, $author, $committer, $coauthors] = array_pad(explode("\x1f", $record), 4, '');
        $short = substr($hash, 0, 10);
        if (!in_array(strtolower($author), $allowed, true)) {
            $bad++;
            $fail("commit {$short}: author address is not an allowed one");
        }
        if (!in_array(strtolower($committer), array_merge($allowed, ALLOWED_COMMITTERS), true)) {
            $bad++;
            $fail("commit {$short}: committer address is not an allowed one");
        }
        foreach (array_filter(explode("\x1e", $coauthors)) as $coauthor) {
            if (!preg_match('/<([^>]+)>/', $coauthor, $email) || !in_array(strtolower($email[1]), array_merge($allowed, ALLOWED_COAUTHORS), true)) {
                $bad++;
                $fail("commit {$short}: co-author address is not an allowed one");
            }
        }
    }
    if ($bad === 0) {
        $info(count($commits) . ' commits with allowed author, committer and co-author addresses');
    }
}

// ---------- 4. Outbound links ----------

$base = parse_url((string) $app->config()['base_url'], PHP_URL_HOST);
$external = [];
foreach ($pages as $path => $body) {
    preg_match_all('#https?://[^\s"\'<>)]+#i', html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $found);
    foreach ($found[0] as $url) {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '' || $host === $base) {
            continue;
        }
        if (!in_array($host, ALLOWED_HOSTS, true)) {
            $fail('unexpected external host ' . safe($host, $terms) . ' on ' . $path);
            continue;
        }
        if ($host === 'github.com') {
            $external[rtrim($url, '/.,')] = true;
        }
    }
}
$info(count($external) . ' outbound links, all to allowed hosts');

// ---------- 5. One hop ----------

/** @return string|null page text, or null when it cannot be fetched */
function fetch(string $url): ?string
{
    $context = stream_context_create(['http' => [
        'timeout' => 15,
        'follow_location' => 1,
        'max_redirects' => 3,
        'ignore_errors' => true,
        'user_agent' => 'portfolio-guard',
    ]]);
    $body = @file_get_contents($url, false, $context, 0, 4 * 1024 * 1024);

    return $body === false ? null : $body;
}

/** Visible text of an HTML page, as lowercase words. */
function visibleWords(string $html): array
{
    $html = (string) preg_replace('#<(script|style|noscript|svg)\b.*?</\1>#is', ' ', $html);
    $text = normalize(strip_tags($html));

    return preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
}

if (isset($options['fetch']) && $terms !== []) {
    foreach (array_keys($external) as $url) {
        $body = fetch($url);
        if ($body === null) {
            $warn('could not fetch ' . safe($url, $terms));
            continue;
        }
        $found = matches($body, $terms);
        foreach ($found as $match) {
            $message = "term #{$match['line']} on linked page " . safe($url, $terms);
            $match['soft'] ? $warn($message) : $fail($message);
        }
        if ($found === []) {
            $info('linked page clean: ' . safe($url, $terms));
        }
    }
}

// ---------- 6. Shared phrases ----------

if (isset($options['overlap'])) {
    $sites = array_values(array_filter(array_map('trim', file((string) $options['overlap']) ?: []), static fn (string $l): bool => $l !== '' && !str_starts_with($l, '#')));
    $shingles = static function (array $words, int $size = 8): array {
        $set = [];
        for ($i = 0, $n = count($words) - $size; $i <= $n; $i++) {
            $set[implode(' ', array_slice($words, $i, $size))] = true;
        }

        return $set;
    };
    $ours = $shingles(array_merge(visibleWords($pages['/']), ['|'], visibleWords($pages['/es/'])));
    foreach ($sites as $index => $url) {
        $body = fetch($url);
        if ($body === null) {
            $warn('site #' . ($index + 1) . ': could not fetch');
            continue;
        }
        $shared = count(array_intersect_key($ours, $shingles(visibleWords($body))));
        $shared > 0
            ? $warn('site #' . ($index + 1) . ": {$shared} shared 8-word phrases")
            : $info('site #' . ($index + 1) . ': no shared 8-word phrases');
    }
}

fwrite(STDOUT, sprintf("\n%d failure(s), %d warning(s)\n", $errors, $warnings));
exit($errors > 0 ? 1 : 0);
