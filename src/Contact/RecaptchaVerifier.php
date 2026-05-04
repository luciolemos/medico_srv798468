<?php

declare(strict_types=1);

namespace App\Contact;

/**
 * Verifies Google reCAPTCHA v3 tokens.
 *
 * Config keys: recaptcha_enabled, recaptcha_site_key, recaptcha_secret_key,
 *              recaptcha_min_score, recaptcha_allowed_hostname, recaptcha_action,
 *              recaptcha_verifier (callable, optional override for HTTP call)
 */
final class RecaptchaVerifier implements RecaptchaVerifierInterface
{
    public function __construct(private array $config) {}

    public function isEnabled(): bool
    {
        return (bool) ($this->config['recaptcha_enabled'] ?? false);
    }

    public function isFrontendEnabled(): bool
    {
        return $this->isEnabled() && $this->siteKey() !== '';
    }

    public function siteKey(): string
    {
        return trim((string) ($this->config['recaptcha_site_key'] ?? ''));
    }

    public function action(): string
    {
        $action = trim((string) ($this->config['recaptcha_action'] ?? 'contact_submit'));
        return $action !== '' ? $action : 'contact_submit';
    }

    /**
     * @return array{ok: bool, disabled?: bool, error?: string, score?: float|null}
     */
    public function verify(string $token, string $remoteIp): array
    {
        if (!$this->isEnabled()) {
            return ['ok' => true, 'disabled' => true];
        }

        $token = trim($token);
        if ($token === '') {
            return ['ok' => false, 'error' => 'token ausente'];
        }

        $secret = trim((string) ($this->config['recaptcha_secret_key'] ?? ''));
        if ($secret === '') {
            return ['ok' => false, 'error' => 'secret ausente'];
        }

        $verifier = $this->config['recaptcha_verifier'] ?? null;
        if (is_callable($verifier)) {
            $result = $verifier([
                'secret'    => $secret,
                'token'     => $token,
                'remote_ip' => $remoteIp,
                'action'    => $this->action(),
            ]);
            if (!is_array($result)) {
                return ['ok' => false, 'error' => 'verificador retornou resposta invalida'];
            }
        } else {
            $result = $this->requestVerification($secret, $token, $remoteIp);
        }

        if (!($result['success'] ?? false)) {
            $codes = $result['error-codes'] ?? [];
            $error = is_array($codes) && $codes !== []
                ? implode(',', array_map('strval', $codes))
                : 'success=false';
            return ['ok' => false, 'error' => $error];
        }

        $expectedAction = $this->action();
        $actualAction   = (string) ($result['action'] ?? '');
        if ($expectedAction !== '' && $actualAction !== $expectedAction) {
            return ['ok' => false, 'error' => 'action inesperada'];
        }

        $minScore = (float) ($this->config['recaptcha_min_score'] ?? 0.5);
        $score    = $result['score'] ?? null;
        if ($minScore > 0) {
            if (!is_numeric($score)) {
                return ['ok' => false, 'error' => 'score ausente'];
            }
            if ((float) $score < $minScore) {
                return ['ok' => false, 'error' => 'score baixo'];
            }
        }

        $allowedHostnames = $this->allowedHostnames();
        if ($allowedHostnames !== []) {
            $actualHostname = strtolower(trim((string) ($result['hostname'] ?? '')));
            if ($actualHostname === '') {
                return ['ok' => false, 'error' => 'hostname ausente'];
            }
            if (!in_array($actualHostname, $allowedHostnames, true)) {
                return ['ok' => false, 'error' => 'hostname inesperado'];
            }
        }

        return ['ok' => true, 'score' => is_numeric($score) ? (float) $score : null];
    }

    private function allowedHostnames(): array
    {
        $raw = strtolower(trim((string) ($this->config['recaptcha_allowed_hostname'] ?? '')));
        if ($raw === '') {
            return [];
        }

        return array_values(array_unique(array_filter(
            preg_split('/[\s,]+/', $raw) ?: [],
            static fn (string $h): bool => $h !== ''
        )));
    }

    private function requestVerification(string $secret, string $token, string $remoteIp): array
    {
        $payload = http_build_query([
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => $remoteIp,
        ], '', '&');

        if (function_exists('curl_init')) {
            $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
            if ($ch === false) {
                return ['success' => false, 'error-codes' => ['curl-init-failed']];
            }

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_CONNECTTIMEOUT => (int) ($this->config['recaptcha_connect_timeout'] ?? 3),
                CURLOPT_TIMEOUT        => (int) ($this->config['recaptcha_timeout'] ?? 6),
            ]);

            $body   = curl_exec($ch);
            $error  = curl_error($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            if ($body === false || $status >= 400) {
                return ['success' => false, 'error-codes' => [
                    'request-failed:' . ($error !== '' ? $error : 'http-' . $status),
                ]];
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'content' => $payload,
                    'timeout' => (int) ($this->config['recaptcha_timeout'] ?? 6),
                ],
            ]);
            $body = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
            if ($body === false) {
                return ['success' => false, 'error-codes' => ['request-failed']];
            }
        }

        $decoded = json_decode((string) $body, true);
        if (!is_array($decoded)) {
            return ['success' => false, 'error-codes' => ['invalid-json']];
        }

        return $decoded;
    }
}
