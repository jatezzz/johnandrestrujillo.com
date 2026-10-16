<?php

declare(strict_types=1);

namespace Portfolio;

/**
 * Anonymous, cookie-free usage and health metrics, relayed to PostHog.
 *
 * The browser posts small JSON events to /e on this site; PHP checks them against
 * an allow-list and forwards them after the response has been sent. Visitors never
 * talk to a third party, so the Content Security Policy stays 'self' only.
 *
 * Privacy: no cookies or storage, no person profiles, no IP or GeoIP sent. The
 * visitor id is a keyed hash of the day, IP and browser, so it can't follow anyone
 * across days and can't be reversed into an IP. Bots and headless browsers are dropped.
 *
 * Modes (config metrics_mode): off | log (var_dir/events.log, used on staging) |
 * posthog (needs posthog_key and posthog_host).
 */
final class Metrics
{
    /** Events the browser may send, each with the only properties it may carry. */
    private const BROWSER_EVENTS = [
        '$pageview' => ['$pathname', '$referring_domain', 'lang', 'theme', 'viewport', 'status'],
        '$web_vitals' => ['$pathname', '$web_vitals_LCP_value', '$web_vitals_CLS_value', '$web_vitals_FCP_value', '$web_vitals_INP_value'],
        '$exception' => ['$pathname', '$exception_type', '$exception_message', '$exception_source', '$exception_lineno'],
        'pin_selected' => ['$pathname', 'pin'],
        'outbound_click' => ['$pathname', 'host', 'path'],
        'theme_changed' => ['$pathname', 'theme'],
        'captcha_refreshed' => ['$pathname'],
    ];
    private const MAX_PER_HOUR = 300;
    private const BOTS = '/bot|crawl|spider|slurp|headless|lighthouse|preview|facebookexternalhit|embedly|curl|wget|python|go-http|java\//i';

    /** @var list<array<string, mixed>> */
    private array $queue = [];

    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config)
    {
    }

    public function enabled(): bool
    {
        return match ($this->config['metrics_mode']) {
            'log' => true,
            'posthog' => $this->config['posthog_key'] !== '' && $this->config['posthog_host'] !== '',
            default => false,
        };
    }

    /** Validates one event posted by the browser and queues it. Returns the HTTP status to answer with. */
    public function fromBrowser(string $body, string $ip, string $agent): int
    {
        if (!$this->enabled()) {
            return 204;
        }
        if (strlen($body) > 4096) {
            return 413;
        }
        $data = json_decode($body, true);
        $event = is_array($data) ? ($data['event'] ?? null) : null;
        if (!is_string($event) || !isset(self::BROWSER_EVENTS[$event]) || !is_array($data['props'] ?? [])) {
            return 400;
        }
        if (preg_match(self::BOTS, $agent) || $agent === '') {
            return 204; // answered, not recorded
        }
        $limit = new RateLimit((string) $this->config['var_dir'], (string) $this->config['form_secret']);
        if (!$limit->allow('metrics', $ip, self::MAX_PER_HOUR, 3600)) {
            return 429;
        }

        $props = [];
        foreach (self::BROWSER_EVENTS[$event] as $key) {
            $value = $data['props'][$key] ?? null;
            if (is_string($value)) {
                $props[$key] = mb_substr($value, 0, 200);
            } elseif ((is_int($value) || is_float($value)) && is_finite((float) $value)) {
                $props[$key] = $value;
            }
        }
        $this->capture($event, $props, $ip, $agent, 'browser');

        return 204;
    }

    /** Queues an event; it is sent by flush(), after the response has gone out. */
    public function capture(string $event, array $props, string $ip, string $agent, string $source = 'server'): void
    {
        if (!$this->enabled()) {
            return;
        }
        $path = is_string($props['$pathname'] ?? null) && str_starts_with($props['$pathname'], '/') ? $props['$pathname'] : '';
        $this->queue[] = [
            'event' => $event,
            'distinct_id' => $this->visitor($ip, $agent),
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'properties' => $props + [
                '$current_url' => $path !== '' ? $this->config['base_url'] . $path : null,
                '$lib' => 'portfolio-relay',
                'source' => $source,
                'env' => $this->config['env'],
                '$process_person_profile' => false,
                '$geoip_disable' => true,
            ],
        ];
    }

    /** Sends whatever was queued. Never throws: metrics must not break a page. */
    public function flush(): void
    {
        if ($this->queue === []) {
            return;
        }
        $events = $this->queue;
        $this->queue = [];
        try {
            if ($this->config['metrics_mode'] === 'log') {
                $lines = array_map(static fn (array $e): string => (string) json_encode($e, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $events);
                @file_put_contents($this->config['var_dir'] . '/events.log', implode("\n", $lines) . "\n", FILE_APPEND | LOCK_EX);

                return;
            }
            $this->post(rtrim((string) $this->config['posthog_host'], '/') . '/batch/', (string) json_encode([
                'api_key' => $this->config['posthog_key'],
                'batch' => $events,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $error) {
            error_log('portfolio: metrics not sent: ' . $error->getMessage());
        }
    }

    /** @return list<array<string, mixed>> queued events, for tests */
    public function pending(): array
    {
        return $this->queue;
    }

    private function visitor(string $ip, string $agent): string
    {
        return 'anon-' . substr(hash_hmac('sha256', gmdate('Y-m-d') . '|' . $ip . '|' . $agent, 'metrics|' . $this->config['form_secret']), 0, 20);
    }

    private function post(string $url, string $json): void
    {
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $json,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_CONNECTTIMEOUT => 2,
            ]);
            $result = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            curl_close($curl);
        } else {
            $result = @file_get_contents($url, false, stream_context_create(['http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $json,
                'timeout' => 3,
                'ignore_errors' => true,
            ]]));
            $status = isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m) ? (int) $m[1] : 0;
        }
        if ($result === false || $status < 200 || $status >= 300) {
            error_log("portfolio: PostHog answered {$status}");
        }
    }
}
