<?php

declare(strict_types=1);

/**
 * Tells Bing, Yandex and the other IndexNow engines that the site changed: every URL in the live sitemap, in one
 * request. Run after a publish (deploy/Jenkinsfile). The key is public by design: IndexNow checks that the file
 * named after it is served by this site (public/<key>.txt).
 *
 *   php tools/indexnow.php https://johnandrestrujillo.com
 *
 * A failed notification never fails a publish; it is reported and the engines catch up from the sitemap.
 */

$base = rtrim($argv[1] ?? '', '/');
if (!preg_match('#^https://[a-z0-9.-]+$#', $base)) {
    fwrite(STDERR, "usage: php tools/indexnow.php https://host\n");
    exit(2);
}
$keys = array_values(array_filter(
    array_map('basename', glob(__DIR__ . '/../public/*.txt') ?: []),
    static fn (string $f): bool => (bool) preg_match('/^[0-9a-f]{32}\.txt$/', $f),
));
if (count($keys) !== 1) {
    fwrite(STDERR, "indexnow: expected one key file in public/, found " . count($keys) . "\n");
    exit(1);
}
$key = substr($keys[0], 0, -4);

$http = static function (string $url, ?string $body = null): array {
    $context = stream_context_create(['http' => [
        'method' => $body === null ? 'GET' : 'POST',
        'header' => $body === null ? "User-Agent: portfolio-indexnow\r\n" : "Content-Type: application/json; charset=utf-8\r\nUser-Agent: portfolio-indexnow\r\n",
        'content' => $body ?? '',
        'timeout' => 20,
        'ignore_errors' => true,
    ]]);
    $data = @file_get_contents($url, false, $context);
    $status = isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $m) ? (int) $m[1] : 0;

    return [$status, (string) $data];
};

[$status, $xml] = $http($base . '/sitemap.xml');
preg_match_all('#<loc>([^<]+)</loc>#', $xml, $m);
$urls = array_values(array_unique($m[1]));
if ($status !== 200 || $urls === []) {
    fwrite(STDERR, "indexnow: could not read the sitemap (HTTP $status); nothing sent\n");
    exit(0);
}
[$sent] = $http('https://api.indexnow.org/indexnow', (string) json_encode([
    'host' => parse_url($base, PHP_URL_HOST),
    'key' => $key,
    'keyLocation' => "$base/$key.txt",
    'urlList' => $urls,
], JSON_UNESCAPED_SLASHES));
// 200 and 202 mean accepted; anything else is reported but does not fail the publish
echo 'indexnow: ' . count($urls) . " URLs, HTTP $sent\n";
exit(0);
