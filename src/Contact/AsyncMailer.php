<?php

declare(strict_types=1);

namespace App\Contact;

/**
 * Decorator that defers email delivery until after the HTTP response is sent.
 *
 * When PHP-FPM is in use, `fastcgi_finish_request()` is called at flush time
 * so the browser receives the response before the SMTP round-trip begins.
 * On other SAPIs the send happens in a shutdown function (still after output
 * is flushed).
 *
 * Body-building methods (`buildTextBody`, `buildHtmlBody`, `resolveFromAddress`)
 * are delegated synchronously — only `send()` is deferred.
 */
final class AsyncMailer implements MailerInterface
{
    public function __construct(private MailerInterface $inner) {}

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
    ): array {
        // Capture all arguments for the deferred closure.
        $inner = $this->inner;

        register_shutdown_function(static function () use (
            $inner,
            $to,
            $subject,
            $textBody,
            $htmlBody,
            $from,
            $replyTo,
            $data,
            $eventId,
            $requestId
        ): void {
            // On PHP-FPM: finish the response before doing I/O.
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }

            $inner->send($to, $subject, $textBody, $htmlBody, $from, $replyTo, $data, $eventId, $requestId);
        });

        // Return optimistic success — the caller records a "success" flash
        // immediately; the real result is fire-and-forget after the response.
        return ['ok' => true, 'reason' => '', 'user_message' => ''];
    }

    public function resolveFromAddress(): string
    {
        return $this->inner->resolveFromAddress();
    }

    public function buildTextBody(
        string $eventId,
        string $requestId,
        string $submittedAt,
        array $data,
        string $origin
    ): string {
        return $this->inner->buildTextBody($eventId, $requestId, $submittedAt, $data, $origin);
    }

    public function buildHtmlBody(
        string $eventId,
        string $requestId,
        string $submittedAt,
        array $data,
        string $origin,
        string $brandName
    ): string {
        return $this->inner->buildHtmlBody($eventId, $requestId, $submittedAt, $data, $origin, $brandName);
    }
}
