<?php

declare(strict_types=1);

namespace App\Contact;

interface MailerInterface
{
    /**
     * Dispatches the email via the configured transport.
     *
     * @return array{ok: bool, reason: string, user_message: string}
     */
    public function send(
        string $to,
        string $subject,
        string $textBody,
        string $htmlBody,
        string $from,
        ?string $replyTo,
        array $data,
        string $eventId,
        string $requestId
    ): array;

    public function resolveFromAddress(): string;

    public function buildTextBody(
        string $eventId,
        string $requestId,
        string $submittedAt,
        array $data,
        string $origin
    ): string;

    public function buildHtmlBody(
        string $eventId,
        string $requestId,
        string $submittedAt,
        array $data,
        string $origin,
        string $brandName
    ): string;
}
