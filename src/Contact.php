<?php

declare(strict_types=1);

namespace Portfolio;

/**
 * The contact form: validation, spam traps and delivery.
 *
 * Spam handling without cookies or third-party captchas:
 *  - a honeypot field people never see (filled = silently dropped);
 *  - a signed render timestamp (sent in under MIN_SECONDS = rejected, too old = expired);
 *  - a per-connection hourly limit kept in files outside the web root.
 */
final class Contact
{
    public const MIN_SECONDS = 3;
    public const MAX_AGE = 86400;
    public const MAX_PER_HOUR = 5;
    public const MIN_MESSAGE = 20;

    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config)
    {
    }

    public function enabled(): bool
    {
        if ($this->config['form_secret'] === '') {
            return false;
        }

        return match ($this->config['mail_mode']) {
            'log' => true,
            'mail' => $this->config['contact_to'] !== '' && $this->config['contact_from'] !== '',
            default => false,
        };
    }

    public function token(?int $at = null): string
    {
        $at ??= time();

        return $at . '.' . $this->sign((string) $at);
    }

    /**
     * @param array<string, mixed> $post
     * @return array{ok: bool, status: int, errors: array<string, string>, values: array<string, string>}
     */
    public function submit(array $post, string $ip): array
    {
        $values = [
            'name' => self::line($post['name'] ?? '', 120),
            'email' => self::line($post['email'] ?? '', 254),
            'org' => self::line($post['org'] ?? '', 160),
            'message' => self::text($post['message'] ?? '', 5000),
        ];

        // Only bots fill the hidden field. Answer as if it worked and send nothing.
        if (trim((string) ($post['website'] ?? '')) !== '') {
            return ['ok' => true, 'status' => 303, 'errors' => [], 'values' => []];
        }
        if (!$this->enabled()) {
            return ['ok' => false, 'status' => 503, 'errors' => ['form' => 'unavailable'], 'values' => $values];
        }

        $errors = [];
        if ($values['name'] === '') {
            $errors['name'] = 'name';
        }
        if (filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'email';
        }
        if (mb_strlen($values['message']) < self::MIN_MESSAGE) {
            $errors['message'] = 'message';
        }
        $age = $this->tokenAge(is_string($post['token'] ?? null) ? $post['token'] : '');
        if ($age === null || $age > self::MAX_AGE) {
            $errors['form'] = 'expired';
        } elseif ($age < self::MIN_SECONDS) {
            $errors['form'] = 'fast';
        }
        if ($errors !== []) {
            return ['ok' => false, 'status' => 422, 'errors' => $errors, 'values' => $values];
        }

        if (!$this->allow($ip)) {
            return ['ok' => false, 'status' => 429, 'errors' => ['form' => 'rate'], 'values' => $values];
        }
        if (!$this->deliver($values)) {
            return ['ok' => false, 'status' => 503, 'errors' => ['form' => 'failed'], 'values' => $values];
        }

        return ['ok' => true, 'status' => 303, 'errors' => [], 'values' => []];
    }

    private function sign(string $payload): string
    {
        return substr(hash_hmac('sha256', 'contact-form|' . $payload, (string) $this->config['form_secret']), 0, 32);
    }

    private function tokenAge(string $token): ?int
    {
        if (!preg_match('/^(\d{9,12})\.([0-9a-f]{32})$/', $token, $parts)) {
            return null;
        }
        if (!hash_equals($this->sign($parts[1]), $parts[2])) {
            return null;
        }

        return time() - (int) $parts[1];
    }

    private function allow(string $ip): bool
    {
        $dir = $this->config['var_dir'] . '/ratelimit';
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            error_log('portfolio: rate-limit directory is not writable');

            return true;
        }

        $file = $dir . '/' . hash_hmac('sha256', $ip, (string) $this->config['form_secret']);
        $handle = @fopen($file, 'c+');
        if ($handle === false) {
            error_log('portfolio: rate-limit file is not writable');

            return true;
        }

        try {
            flock($handle, LOCK_EX);
            $now = time();
            $recent = array_values(array_filter(
                array_map('intval', explode(',', (string) stream_get_contents($handle))),
                static fn (int $t): bool => $t > $now - 3600,
            ));
            if (count($recent) >= self::MAX_PER_HOUR) {
                return false;
            }
            $recent[] = $now;
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, implode(',', $recent));

            return true;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /** @param array<string, string> $values */
    private function deliver(array $values): bool
    {
        $body = "Name: {$values['name']}\nEmail: {$values['email']}\n"
            . ($values['org'] !== '' ? "Company or project: {$values['org']}\n" : '')
            . "\n{$values['message']}\n\n-- \nSent from the contact form\n";

        if ($this->config['mail_mode'] === 'log') {
            $dir = $this->config['var_dir'];
            if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
                return false;
            }
            $entry = json_encode(['at' => gmdate('c'), 'reply_to' => $values['email'], 'body' => $body], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return file_put_contents($dir . '/mail.log', $entry . "\n", FILE_APPEND | LOCK_EX) !== false;
        }

        $from = (string) $this->config['contact_from'];
        $headers = [
            'From' => $from,
            'Reply-To' => $values['email'],
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => '8bit',
        ];
        $subject = mb_encode_mimeheader('Portfolio contact: ' . $values['name'], 'UTF-8', 'Q');

        return mail((string) $this->config['contact_to'], $subject, $body, $headers, '-f' . $from);
    }

    /** One line of text: no control characters (so no header injection), trimmed and capped. */
    private static function line(mixed $value, int $max): string
    {
        $value = is_string($value) ? $value : '';
        $value = (string) preg_replace('/[\p{Cc}\p{Zl}\p{Zp}]+/u', ' ', $value);

        return mb_substr(trim($value), 0, $max);
    }

    /** Free text: keeps line breaks and tabs, drops other control characters. */
    private static function text(mixed $value, int $max): string
    {
        $value = is_string($value) ? str_replace(["\r\n", "\r"], "\n", $value) : '';
        $value = (string) preg_replace('/[^\P{Cc}\n\t]+/u', '', $value);

        return mb_substr(trim($value), 0, $max);
    }
}
