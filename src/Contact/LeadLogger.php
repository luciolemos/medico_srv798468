<?php

declare(strict_types=1);

namespace App\Contact;

/**
 * Persists lead events and fallback contacts to append-only NDJSON log files.
 *
 * Config keys: lead_log_retention_days, lead_log_hash_salt, lead_encrypt_key,
 *              storage_path, app_slug, app_name, base_url (for hash salt fallback)
 */
final class LeadLogger implements LeadLoggerInterface
{
    public function __construct(private array $config) {}

    public function persistFallbackLead(
        array $data,
        string $reason,
        string $eventId = '',
        string $requestId = '',
        string $clientIp = ''
    ): void {
        $logDir  = $this->storagePath() . '/logs';
        $logFile = $logDir . '/contatos-fallback.log';

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $entry = [
            'timestamp'            => date('c'),
            'event_id'             => $eventId,
            'request_id'           => $requestId,
            'reason'               => $reason,
            'contains_personal_data' => true,
            'retention_days'       => $this->retentionDays(),
            'nome'                 => $this->encryptField($data['nome'] ?? ''),
            'telefone'             => $this->encryptField($data['telefone'] ?? ''),
            'email'                => $this->encryptField($data['email'] ?? ''),
            'empresa'              => $this->encryptField($data['empresa'] ?? ''),
            'mensagem'             => $this->encryptField($data['mensagem'] ?? ''),
            'ip_hash'              => $this->hashForLog($clientIp),
        ];

        @file_put_contents($logFile, json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    }

    public function persistEvent(
        string $eventId,
        string $requestId,
        string $result,
        string $reason,
        array $data,
        string $clientIp = '',
        string $userAgent = ''
    ): void {
        $logDir  = $this->storagePath() . '/logs';
        $logFile = $logDir . '/lead-events.log';

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $entry = [
            'timestamp'            => date('c'),
            'event_id'             => $eventId,
            'request_id'           => $requestId,
            'result'               => $result,
            'reason'               => $reason,
            'contains_personal_data' => false,
            'retention_days'       => $this->retentionDays(),
            'lead'                 => $this->eventSummary($data),
            'ip_hash'              => $this->hashForLog($clientIp),
            'user_agent_hash'      => $this->hashForLog($userAgent),
        ];

        @file_put_contents($logFile, json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    }

    private function eventSummary(array $data): array
    {
        $message = trim((string) ($data['mensagem'] ?? ''));

        return [
            'name_masked'    => $this->maskName((string) ($data['nome'] ?? '')),
            'phone_masked'   => $this->maskPhone((string) ($data['telefone'] ?? '')),
            'email_masked'   => $this->maskEmail((string) ($data['email'] ?? '')),
            'company_present' => trim((string) ($data['empresa'] ?? '')) !== '',
            'message_length' => mb_strlen($message),
            'message_hash'   => $message !== '' ? $this->hashForLog($message) : '',
        ];
    }

    private function maskName(string $name): string
    {
        $words  = preg_split('/\s+/', trim($name)) ?: [];
        $masked = [];
        foreach ($words as $word) {
            $first = mb_substr($word, 0, 1);
            if ($first === '') {
                continue;
            }
            $masked[] = $first . '***';
        }

        return implode(' ', $masked);
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return '';
        }

        $last = substr($digits, -4);
        return str_repeat('*', max(strlen($digits) - 4, 0)) . $last;
    }

    private function maskEmail(string $email): string
    {
        $email = trim($email);
        if ($email === '' || !str_contains($email, '@')) {
            return '';
        }

        [$local, $domain] = explode('@', $email, 2);
        $first = mb_substr($local, 0, 1);
        return ($first !== '' ? $first : '*') . '***@' . $domain;
    }

    private function encryptField(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $keyRaw = trim((string) ($this->config['lead_encrypt_key'] ?? ''));
        if ($keyRaw === '' || !extension_loaded('sodium')) {
            return $value;
        }

        $key = hash('sha256', $keyRaw, true);

        try {
            $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        } catch (\Throwable) {
            $nonce = substr(hash('sha256', uniqid('', true), true), 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        }

        $ciphertext = sodium_crypto_secretbox($value, $nonce, $key);
        sodium_memzero($key);

        return 'enc:' . base64_encode($nonce . $ciphertext);
    }

    private function hashForLog(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return hash_hmac('sha256', $value, $this->hashSalt());
    }

    private function hashSalt(): string
    {
        $configured = trim((string) ($this->config['lead_log_hash_salt'] ?? ''));
        if ($configured !== '') {
            return $configured;
        }

        return implode('|', [
            (string) ($this->config['app_slug'] ?? 'landing'),
            (string) ($this->config['base_url'] ?? ''),
            (string) ($this->config['app_name'] ?? ''),
        ]);
    }

    private function retentionDays(): int
    {
        $days = (int) ($this->config['lead_log_retention_days'] ?? 30);
        return $days > 0 ? $days : 30;
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
