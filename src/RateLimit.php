<?php

declare(strict_types=1);

namespace Portfolio;

/**
 * Sliding-window counter per key, kept in small files outside the web root (the
 * shared host has no APCu or shared memory between PHP workers). The key is
 * hashed with the site secret, so no IP address is ever written to disk.
 */
final class RateLimit
{
    public function __construct(private readonly string $dir, private readonly string $secret)
    {
    }

    /** Records one hit for $key and says whether it stays within $max per $window seconds. */
    public function allow(string $bucket, string $key, int $max, int $window): bool
    {
        $dir = $this->dir . '/ratelimit';
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            error_log('portfolio: rate-limit directory is not writable');

            return true; // fail open: the other spam checks still apply
        }

        $file = $dir . '/' . $bucket . '-' . hash_hmac('sha256', $key, $this->secret);
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
                static fn (int $t): bool => $t > $now - $window,
            ));
            if (count($recent) >= $max) {
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
}
