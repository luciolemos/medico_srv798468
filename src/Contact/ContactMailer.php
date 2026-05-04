<?php

declare(strict_types=1);

namespace App\Contact;

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Handles email delivery (SMTP / native mail / custom sender) and body building.
 *
 * Config keys: mail_driver, smtp_host, smtp_port, smtp_user, smtp_pass,
 *              smtp_encryption, smtp_auth, smtp_timeout, app_name,
 *              contact_from, mail_sender (callable, optional)
 */
final class ContactMailer implements MailerInterface
{
    public function __construct(private array $config) {}

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
    ): array {
        $customSender = $this->config['mail_sender'] ?? null;
        if (is_callable($customSender)) {
            $result = $customSender([
                'to'         => $to,
                'subject'    => $subject,
                'text_body'  => $textBody,
                'html_body'  => $htmlBody,
                'from'       => $from,
                'reply_to'   => $replyTo,
                'data'       => $data,
                'event_id'   => $eventId,
                'request_id' => $requestId,
            ]);

            if (!is_array($result) || !($result['ok'] ?? false)) {
                $err = is_array($result) ? (string) ($result['error'] ?? 'erro desconhecido') : 'erro desconhecido';
                return [
                    'ok'           => false,
                    'reason'       => 'mail_sender falhou: ' . $err,
                    'user_message' => 'Recebemos sua solicitação de agendamento, mas o envio de e-mail falhou no servidor. Entre em contato também pelo WhatsApp.',
                ];
            }

            return ['ok' => true, 'reason' => '', 'user_message' => ''];
        }

        if ($this->useSmtpDriver()) {
            if (!$this->isSmtpConfigured()) {
                return [
                    'ok'           => false,
                    'reason'       => 'SMTP selecionado, mas incompleto no .env',
                    'user_message' => 'Recebemos sua solicitação de agendamento, mas o SMTP está incompleto no servidor. Entre em contato também pelo WhatsApp.',
                ];
            }

            $smtpResult = $this->sendViaSmtp($to, $subject, $textBody, $htmlBody, $from, $replyTo);
            if (!$smtpResult['ok']) {
                return [
                    'ok'           => false,
                    'reason'       => 'SMTP falhou: ' . ($smtpResult['error'] ?? 'erro desconhecido'),
                    'user_message' => 'Recebemos sua solicitação de agendamento, mas o envio de e-mail falhou no SMTP. Entre em contato também pelo WhatsApp.',
                ];
            }

            return ['ok' => true, 'reason' => '', 'user_message' => ''];
        }

        if (!$this->canUseNativeMail()) {
            return [
                'ok'           => false,
                'reason'       => 'Transporte de email indisponível (sendmail_path inválido)',
                'user_message' => 'Recebemos sua solicitação de agendamento, mas o servidor de e-mail não está configurado. Entre em contato também pelo WhatsApp.',
            ];
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $from,
        ];
        if ($replyTo !== null) {
            $headers[] = 'Reply-To: ' . $replyTo;
        }

        $sent = mail($to, $subject, $textBody, implode("\r\n", $headers));
        if (!$sent) {
            return [
                'ok'           => false,
                'reason'       => 'mail() retornou false',
                'user_message' => 'Recebemos sua solicitação de agendamento, mas o envio de e-mail falhou no servidor. Entre em contato também pelo WhatsApp.',
            ];
        }

        return ['ok' => true, 'reason' => '', 'user_message' => ''];
    }

    public function resolveFromAddress(): string
    {
        $from = (string) ($this->config['contact_from'] ?? '');
        if ($from !== '') {
            return $from;
        }

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $host = preg_replace('/:\d+$/', '', (string) $host) ?? 'localhost';
        return 'no-reply@' . $host;
    }

    public function buildTextBody(
        string $eventId,
        string $requestId,
        string $submittedAt,
        array $data,
        string $origin
    ): string {
        $lines = [
            'NOVA SOLICITAÇÃO DE AGENDAMENTO',
            str_repeat('=', 34),
            'Protocolo: ' . $requestId,
            'ID do evento: ' . $eventId,
            'Data/Hora: ' . $submittedAt,
            'Origem: ' . $origin,
            '',
            'DADOS DE CONTATO',
            '- Nome: ' . $data['nome'],
            '- Telefone/WhatsApp: ' . $data['telefone'],
            '- Email: ' . ($data['email'] !== '' ? $data['email'] : '-'),
            '- Convênio/Observações: ' . ($data['empresa'] !== '' ? $data['empresa'] : '-'),
            '',
            'MOTIVO DA CONSULTA',
            str_repeat('-', 34),
            $data['mensagem'],
        ];

        return implode("\n", $lines);
    }

    public function buildHtmlBody(
        string $eventId,
        string $requestId,
        string $submittedAt,
        array $data,
        string $origin,
        string $brandName
    ): string {
        $name           = htmlspecialchars((string) ($data['nome'] ?? '-'), ENT_QUOTES, 'UTF-8');
        $phone          = htmlspecialchars((string) ($data['telefone'] ?? '-'), ENT_QUOTES, 'UTF-8');
        $email          = htmlspecialchars((string) (($data['email'] ?? '') !== '' ? $data['email'] : '-'), ENT_QUOTES, 'UTF-8');
        $notes          = htmlspecialchars((string) (($data['empresa'] ?? '') !== '' ? $data['empresa'] : '-'), ENT_QUOTES, 'UTF-8');
        $message        = nl2br(htmlspecialchars((string) ($data['mensagem'] ?? ''), ENT_QUOTES, 'UTF-8'));
        $safeEventId    = htmlspecialchars($eventId, ENT_QUOTES, 'UTF-8');
        $safeRequestId  = htmlspecialchars($requestId, ENT_QUOTES, 'UTF-8');
        $safeSubmittedAt = htmlspecialchars($submittedAt, ENT_QUOTES, 'UTF-8');
        $safeOrigin     = htmlspecialchars($origin, ENT_QUOTES, 'UTF-8');
        $safeBrandName  = htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8');

        $normalizedWhatsapp = $this->normalizeWhatsappNumber((string) ($data['telefone'] ?? ''));
        $whatsMessage = rawurlencode('Olá! Recebemos sua solicitação de agendamento. Protocolo: ' . $requestId . '. Vamos continuar por aqui.');
        $whatsHref    = $normalizedWhatsapp !== null ? 'https://wa.me/' . $normalizedWhatsapp . '?text=' . $whatsMessage : '#';
        $safeWhatsHref = htmlspecialchars($whatsHref, ENT_QUOTES, 'UTF-8');
        $safeReplyHref = htmlspecialchars('mailto:' . (($data['email'] ?? '') !== '' ? $data['email'] : ''), ENT_QUOTES, 'UTF-8');

        return <<<HTML
<div style="background:#f6f8fb;padding:16px;font-family:Arial,sans-serif;color:#111827;">
  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:680px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
    <tr>
      <td style="padding:16px;background:#ffffff;color:#111827;border-bottom:1px solid #e5e7eb;">
        <div style="font-size:20px;line-height:1.3;font-weight:800;">{$safeBrandName}</div>
        <div style="font-size:18px;line-height:1.3;font-weight:700;margin-top:8px;">Nova solicitação de agendamento</div>
        <div style="font-size:13px;color:#4b5563;margin-top:4px;">Contato recebido pelo site da clínica</div>
      </td>
    </tr>

    <tr>
      <td style="padding:16px;">
        <div style="font-size:13px;color:#4b5563;margin-bottom:14px;line-height:1.45;">
          <strong>Protocolo:</strong>
          <span style="display:inline-block;padding:3px 8px;border:1px solid #d1d5db;border-radius:999px;background:#f3f4f6;color:#111827;font-family:Consolas,'Courier New',monospace;font-size:12px;">{$safeRequestId}</span><br>
          <strong>ID:</strong> {$safeEventId}<br>
          <strong>Data/Hora:</strong> {$safeSubmittedAt}<br>
          <strong>Origem:</strong> {$safeOrigin}
        </div>

        <div style="margin-bottom:12px;padding:12px;border:1px solid #eef2f7;border-radius:8px;background:#ffffff;">
          <div style="font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">Nome</div>
          <div style="font-size:15px;line-height:1.4;">{$name}</div>
        </div>

        <div style="margin-bottom:12px;padding:12px;border:1px solid #eef2f7;border-radius:8px;background:#ffffff;">
          <div style="font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">Telefone/WhatsApp</div>
          <div style="font-size:15px;line-height:1.4;">{$phone}</div>
        </div>

        <div style="margin-bottom:12px;padding:12px;border:1px solid #eef2f7;border-radius:8px;background:#ffffff;">
          <div style="font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">Email</div>
          <div style="font-size:15px;line-height:1.4;word-break:break-word;">{$email}</div>
        </div>

        <div style="margin-bottom:16px;padding:12px;border:1px solid #eef2f7;border-radius:8px;background:#ffffff;">
          <div style="font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">Convênio/Observações</div>
          <div style="font-size:15px;line-height:1.4;">{$notes}</div>
        </div>

        <div style="font-size:14px;font-weight:700;margin-bottom:8px;">Motivo da consulta</div>
        <div style="padding:12px;border:1px solid #e5e7eb;border-radius:8px;background:#f8fafc;line-height:1.6;font-size:14px;word-break:break-word;">
          {$message}
        </div>

        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin-top:16px;border-collapse:collapse;">
          <tr>
            <td style="padding:0 0 10px 0;">
              <a href="{$safeReplyHref}" style="display:block;width:100%;box-sizing:border-box;padding:12px 14px;background:#111827;color:#ffffff;text-decoration:none;border-radius:8px;font-size:14px;font-weight:700;text-align:center;">Responder por email</a>
            </td>
          </tr>
          <tr>
            <td style="padding:0;">
              <a href="{$safeWhatsHref}" style="display:block;width:100%;box-sizing:border-box;padding:12px 14px;background:#16a34a;color:#ffffff;text-decoration:none;border-radius:8px;font-size:14px;font-weight:700;text-align:center;">Abrir WhatsApp</a>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</div>
HTML;
    }

    private function normalizeWhatsappNumber(string $rawPhone): ?string
    {
        $digits = preg_replace('/\D+/', '', $rawPhone);
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '55') && strlen($digits) >= 12) {
            return $digits;
        }

        if (strlen($digits) === 10 || strlen($digits) === 11) {
            return '55' . $digits;
        }

        return strlen($digits) >= 12 ? $digits : null;
    }

    private function useSmtpDriver(): bool
    {
        return strtolower(trim((string) ($this->config['mail_driver'] ?? 'mail'))) === 'smtp';
    }

    private function isSmtpConfigured(): bool
    {
        $host = trim((string) ($this->config['smtp_host'] ?? ''));
        $user = trim((string) ($this->config['smtp_user'] ?? ''));
        $pass = (string) ($this->config['smtp_pass'] ?? '');
        $port = (int) ($this->config['smtp_port'] ?? 0);
        return $host !== '' && $user !== '' && $pass !== '' && $port > 0;
    }

    private function sendViaSmtp(
        string $to,
        string $subject,
        string $textBody,
        string $htmlBody,
        string $from,
        ?string $replyTo
    ): array {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host     = (string) $this->config['smtp_host'];
            $mail->Port     = (int) $this->config['smtp_port'];
            $mail->SMTPAuth = (bool) $this->config['smtp_auth'];
            $mail->Username = (string) $this->config['smtp_user'];
            $mail->Password = (string) $this->config['smtp_pass'];
            $mail->Timeout  = (int) $this->config['smtp_timeout'];
            $mail->CharSet  = 'UTF-8';

            $enc = strtolower(trim((string) ($this->config['smtp_encryption'] ?? 'tls')));
            if ($enc === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($enc === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure  = '';
                $mail->SMTPAutoTLS = false;
            }

            $mail->setFrom($from, (string) ($this->config['app_name'] ?? 'Clínica Médica'));
            $mail->addAddress($to);
            if ($replyTo !== null) {
                $mail->addReplyTo($replyTo);
            }

            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $textBody;
            $mail->isHTML(true);

            $mail->send();
            return ['ok' => true];
        } catch (MailException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function canUseNativeMail(): bool
    {
        if (\PHP_OS_FAMILY === 'Windows') {
            return true;
        }

        $sendmailPath = trim((string) ini_get('sendmail_path'));
        if ($sendmailPath === '') {
            return false;
        }

        $parts  = preg_split('/\s+/', $sendmailPath);
        $binary = $parts[0] ?? '';
        return $binary !== '' && is_executable($binary);
    }
}
