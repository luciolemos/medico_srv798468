<?php

declare(strict_types=1);

namespace App\Contact;

/**
 * APCu-backed IP rate limiter for the contact form.
 *
 * Uses a sliding-window counter stored in shared memory.
 * Automatically falls back to the file-based limiter when APCu is not
 * available (e.g. CLI environment or when the extension is not loaded).
 *
 * Config keys: rate_limit_max_attempts, rate_limit_window_seconds, storage_path
 */
final class ApcuRateLimiter implements RateLimiterInterface
{
    private RateLimiterInterface $fallback;

    public function __construct(private array $config)
    {
        $this->fallback = new ContactRateLimiter($config);
    }

    public function hasHit(string $ip): bool
    {
        if (!$this->apcuAvailable()) {
            return $this->fallback->hasHit($ip);
        }

        $maxAttempts   = (int) ($this->config['rate_limit_max_attempts'] ?? 5);
        $windowSeconds = (int) ($this->config['rate_limit_window_seconds'] ?? 600);

        if ($maxAttempts <= 0 || $windowSeconds <= 0) {
            return false;
        }

        $key = 'rl_contact_' . sha1($ip);

        // Atomically increment; create with TTL = window on first hit.
        $count = apcu_inc($key, 1, $success);

        if (!$success) {
            // Key did not exist yet — create it.
            apcu_store($key, 1, $windowSeconds);
            return false;
        }

        return $count > $maxAttempts;
    }

    private function apcuAvailable(): bool
    {
        return extension_loaded('apcu')
            && function_exists('apcu_inc')
            && (bool) ini_get('apc.enabled')
            // apcu_enabled() returns false in CLI unless apc.enable_cli=1
            && (PHP_SAPI !== 'cli' || (bool) ini_get('apc.enable_cli'));
    }
}
