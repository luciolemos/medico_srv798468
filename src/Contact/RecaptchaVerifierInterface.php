<?php

declare(strict_types=1);

namespace App\Contact;

interface RecaptchaVerifierInterface
{
    public function isEnabled(): bool;

    public function isFrontendEnabled(): bool;

    public function siteKey(): string;

    public function action(): string;

    /**
     * @return array{ok: bool, disabled?: bool, error?: string, score?: float|null}
     */
    public function verify(string $token, string $remoteIp): array;
}
