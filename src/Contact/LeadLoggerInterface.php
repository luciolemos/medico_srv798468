<?php

declare(strict_types=1);

namespace App\Contact;

interface LeadLoggerInterface
{
    public function persistFallbackLead(
        array $data,
        string $reason,
        string $eventId = '',
        string $requestId = '',
        string $clientIp = ''
    ): void;

    public function persistEvent(
        string $eventId,
        string $requestId,
        string $result,
        string $reason,
        array $data,
        string $clientIp = '',
        string $userAgent = ''
    ): void;
}
