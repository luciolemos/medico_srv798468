<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contact\MailerInterface;

/**
 * In-memory mailer double for unit and integration tests.
 *
 * By default every send() call succeeds. Pass false to the constructor to
 * simulate a delivery failure, or call $fake->failNext() before the assertion.
 */
final class FakeMailer implements MailerInterface
{
    /** @var list<array<string, mixed>> */
    private array $sent = [];

    private bool $nextFails = false;

    private string $nextFailReason = 'fake failure';

    public function __construct(private bool $succeeds = true) {}

    public function failNext(string $reason = 'fake failure'): void
    {
        $this->nextFails     = true;
        $this->nextFailReason = $reason;
    }

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
        $fails  = $this->nextFails || !$this->succeeds;
        $reason = $fails ? $this->nextFailReason : '';

        $this->nextFails     = false;
        $this->nextFailReason = 'fake failure';

        $this->sent[] = compact('to', 'subject', 'textBody', 'htmlBody', 'from', 'replyTo', 'data', 'eventId', 'requestId');

        if ($fails) {
            return [
                'ok'           => false,
                'reason'       => $reason,
                'user_message' => 'Recebemos sua solicitação de agendamento, mas o envio de e-mail falhou no servidor. Entre em contato também pelo WhatsApp.',
            ];
        }

        return ['ok' => true, 'reason' => '', 'user_message' => ''];
    }

    public function resolveFromAddress(): string
    {
        return 'no-reply@example.com';
    }

    public function buildTextBody(
        string $eventId,
        string $requestId,
        string $submittedAt,
        array $data,
        string $origin
    ): string {
        return "Text body – {$requestId}";
    }

    public function buildHtmlBody(
        string $eventId,
        string $requestId,
        string $submittedAt,
        array $data,
        string $origin,
        string $brandName
    ): string {
        return "<p>HTML body – {$requestId}</p>";
    }

    // -----------------------------------------------------------------
    // Assertions helpers
    // -----------------------------------------------------------------

    public function sentCount(): int
    {
        return count($this->sent);
    }

    public function wasSent(): bool
    {
        return $this->sent !== [];
    }

    /** @return array<string, mixed>|null */
    public function lastSent(): ?array
    {
        return $this->sent !== [] ? $this->sent[count($this->sent) - 1] : null;
    }

    /** @return list<array<string, mixed>> */
    public function allSent(): array
    {
        return $this->sent;
    }

    public function reset(): void
    {
        $this->sent       = [];
        $this->nextFails  = false;
        $this->succeeds   = true;
    }
}
