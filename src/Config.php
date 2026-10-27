<?php

declare(strict_types=1);

namespace Portfolio;

final class Config
{
    public const DEFAULTS = [
        'env' => 'production',          // production | staging | development
        'base_url' => 'https://johnandrestrujillo.com',
        'noindex' => false,
        'mail_mode' => 'off',           // mail | log | off
        'contact_to' => '',
        'contact_from' => '',
        'form_secret' => '',
        'var_dir' => '',
        'github_url' => 'https://github.com/jatezzz',
        'linkedin_url' => 'https://www.linkedin.com/in/john-andres-trujillo/',
        'instagram_url' => 'https://www.instagram.com/johnandrestrujillo/',
        'tiktok_url' => 'https://www.tiktok.com/@johnandrestrujillo',
        'source_url' => '',
        'revision' => '',
        'metrics_mode' => 'off',        // off | log | posthog
        'posthog_key' => '',            // project API key of a personal PostHog project
        'posthog_host' => 'https://us.i.posthog.com',
    ];

    /**
     * Defaults, then the local config file (never committed), then explicit overrides.
     * PORTFOLIO_CONFIG points at a config file outside the repository when set.
     *
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    public static function load(string $root, array $overrides = []): array
    {
        $file = getenv('PORTFOLIO_CONFIG') ?: $root . '/config/local.php';
        $local = is_file($file) ? require $file : [];
        if (!is_array($local)) {
            throw new \RuntimeException('The config file must return an array.');
        }

        $config = array_replace(self::DEFAULTS, $local, $overrides);
        $config['base_url'] = rtrim((string) $config['base_url'], '/');
        if ($config['var_dir'] === '') {
            $config['var_dir'] = $root . '/var';
        }
        if ($config['revision'] === '' && is_file($root . '/.revision')) {
            $config['revision'] = trim((string) file_get_contents($root . '/.revision'));
        }

        return $config;
    }
}
