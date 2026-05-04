<?php

declare(strict_types=1);

namespace App\Contact;

interface RateLimiterInterface
{
    /**
     * Returns true if the IP has exhausted its allowed attempts.
     * Implementations must record the current attempt.
     */
    public function hasHit(string $ip): bool;
}
