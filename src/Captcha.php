<?php

declare(strict_types=1);

namespace Portfolio;

use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;

/**
 * Image CAPTCHA for the contact form: a short random phrase drawn as a distorted
 * PNG (gregwar/captcha), so the answer never appears as text in the page.
 *
 * Stateless: the token carries a keyed hash of the phrase plus issue and expiry
 * times, signed with the site secret. A solved token is marked as used with a small
 * file, so it can't be replayed. What it stops: scripts that post the form without
 * rendering images. What it doesn't: paid solving services or vision-capable agents.
 */
final class Captcha
{
    public const TTL = 900;
    public const MIN_SECONDS = 2;
    private const WIDTH = 160;
    private const HEIGHT = 50;

    public function __construct(private readonly string $secret, private readonly string $varDir)
    {
    }

    public static function available(): bool
    {
        return function_exists('imagettftext') && function_exists('imagepng');
    }

    /** @return array{image: string, token: string} image is a data: URL */
    public function issue(): array
    {
        return $this->issueFor(null);
    }

    /**
     * Same as issue(), with a known phrase. Tests use it; the page never does.
     *
     * @return array{image: string, token: string}
     */
    public function issueFor(?string $phrase, ?int $at = null): array
    {
        $builder = new CaptchaBuilder($phrase);
        $builder->build(self::WIDTH, self::HEIGHT);
        $at ??= time();
        $payload = rtrim(strtr(base64_encode((string) json_encode([
            'h' => $this->hashPhrase($builder->getPhrase()),
            'iat' => $at,
            'exp' => $at + self::TTL,
        ])), '+/', '-_'), '=');

        return [
            'image' => $builder->setImageType('png')->inline(),
            'token' => $payload . '.' . $this->sign($payload),
        ];
    }

    public function verify(string $token, string $answer): bool
    {
        [$payload, $signature] = array_pad(explode('.', $token, 2), 2, '');
        if ($payload === '' || !hash_equals($this->sign($payload), $signature)) {
            return false;
        }
        $data = json_decode((string) base64_decode(strtr($payload, '-_', '+/')), true);
        if (!is_array($data) || !isset($data['h'], $data['iat'], $data['exp'])) {
            return false;
        }
        $now = time();
        if ($data['iat'] > $now - self::MIN_SECONDS || $data['exp'] < $now) {
            return false; // answered implausibly fast, or expired
        }
        // Same folding as the library's own comparison: case-insensitive, 0/O and 1/l alike.
        if (!hash_equals((string) $data['h'], $this->hashPhrase(trim($answer)))) {
            return false;
        }

        return $this->consume($signature, (int) $data['exp']);
    }

    private function sign(string $payload): string
    {
        return hash_hmac('sha256', 'captcha|' . $payload, $this->secret);
    }

    private function hashPhrase(string $phrase): string
    {
        return hash_hmac('sha256', 'phrase|' . PhraseBuilder::doNiceize($phrase), $this->secret);
    }

    /** Marks a solved token as used; exactly one of two racing requests wins. */
    private function consume(string $signature, int $expiresAt): bool
    {
        $dir = $this->varDir . '/captcha-used';
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            error_log('portfolio: captcha directory is not writable');

            return false;
        }
        foreach (glob($dir . '/*') ?: [] as $file) {
            if ((int) @file_get_contents($file) < time()) {
                @unlink($file);
            }
        }
        $handle = @fopen($dir . '/' . hash('sha256', $signature), 'x');
        if ($handle === false) {
            return false; // already used
        }
        fwrite($handle, (string) $expiresAt);
        fclose($handle);

        return true;
    }
}
