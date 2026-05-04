<?php

declare(strict_types=1);

namespace App\Contact;

/**
 * File-based IP rate limiter for the contact form.
 *
 * Config keys: rate_limit_max_attempts, rate_limit_window_seconds, storage_path
 */
final class ContactRateLimiter implements RateLimiterInterface
{
    public function __construct(private array $config) {}

    /**
     * Returns true if the IP has exhausted its allowed attempts within the window.
     * Always records the current attempt.
     */
    public function hasHit(string $ip): bool
    {
        $maxAttempts   = (int) ($this->config['rate_limit_max_attempts'] ?? 5);
        $windowSeconds = (int) ($this->config['rate_limit_window_seconds'] ?? 600);

        if ($maxAttempts <= 0 || $windowSeconds <= 0) {
            return false;
        }

        $dir  = $this->storagePath() . '/rate-limit';
        $file = $dir . '/contact-' . sha1($ip) . '.json';

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $now      = time();
        $attempts = [];

        if (is_file($file)) {
            $decoded = json_decode((string) file_get_contents($file), true);
            if (is_array($decoded)) {
                $attempts = array_values(array_filter(
                    $decoded,
                    static fn ($ts): bool => is_int($ts) && $ts >= ($now - $windowSeconds)
                ));
            }
        }

        if (count($attempts) >= $maxAttempts) {
            @file_put_contents($file, json_encode($attempts), LOCK_EX);
            return true;
        }

        $attempts[] = $now;
        @file_put_contents($file, json_encode($attempts), LOCK_EX);
        return false;
    }

    private function storagePath(): string
    {
        $configured = trim((string) ($this->config['storage_path'] ?? ''));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        return dirname(__DIR__, 2) . '/storage';
    }
}
