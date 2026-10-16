<?php

declare(strict_types=1);

namespace Portfolio;

final class App
{
    /** @var array<string, mixed> */
    private array $config;
    private View $view;

    /** @param array<string, mixed> $overrides */
    public function __construct(private readonly string $root, array $overrides = [])
    {
        $this->config = Config::load($root, $overrides);
        $this->view = new View($root . '/templates');
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
    public function handle(string $method, string $uri, array $post = [], array $server = []): Response
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
                '/robots.txt' => $this->robots(),
                '/sitemap.xml' => $this->sitemap(),
                '/healthz' => Response::text("ok\n")->withHeader('Cache-Control', 'no-store'),
                default => $this->notFound($path),
            };
        } catch (\Throwable $error) {
            error_log(sprintf('portfolio: %s: %s at %s:%d', $error::class, $error->getMessage(), $error->getFile(), $error->getLine()));
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
        $form += ['values' => [], 'errors' => [], 'sent' => isset($query['sent'])];

        return Response::html($this->page($lang, 'home', ['form' => $form]), $status)
            ->withHeader('Cache-Control', $this->cacheControl($status === 200 && !$form['sent']));
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

        $result = (new Contact($this->config))->submit($post, (string) ($server['REMOTE_ADDR'] ?? ''));
        if ($result['ok']) {
            return Response::redirect($home . '?sent=1#contact', 303);
        }

        $response = $this->home($lang, 'GET', [], ['values' => $result['values'], 'errors' => $result['errors']], $result['status']);

        return $result['status'] === 429 ? $response->withHeader('Retry-After', '3600') : $response;
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

        return Response::html($this->page($lang, '404', []), 404)->withHeader('Cache-Control', 'no-store');
    }

    /** @param array<string, mixed> $vars */
    private function page(string $lang, string $template, array $vars): string
    {
        $c = Content::load($this->root, $lang);
        $base = $this->config['base_url'];
        $urls = ['en' => $base . '/', 'es' => $base . '/es/'];
        $contact = new Contact($this->config);

        $vars += [
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
            ],
            'asset' => $this->asset(...),
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
