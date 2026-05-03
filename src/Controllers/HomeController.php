<?php

namespace App\Controllers;

use App\Core\SeoMetadata;
use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class HomeController
{
    private const ALLOWED_PALETTES = ['blue', 'red', 'emerald', 'amber', 'violet'];
    private const CSRF_SESSION_KEY = 'contact_csrf_token';
    private const PALETTE_COOKIE_NAME = 'palette';
    private const PALETTE_COOKIE_TTL = 31536000;

    public function __construct(private Twig $twig, private array $config)
    {
    }

    public function home(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $hasSeoVariantQuery = $this->hasSeoVariantQuery($queryParams);

        $paletteFromQuery = strtolower((string) ($queryParams['palette'] ?? ''));
        $paletteFromCookie = strtolower((string) ($_COOKIE[self::PALETTE_COOKIE_NAME] ?? ''));
        $paletteFromConfig = strtolower((string) ($this->config['palette'] ?? 'blue'));

        if (in_array($paletteFromQuery, self::ALLOWED_PALETTES, true)) {
            $palette = $paletteFromQuery;
        } elseif (in_array($paletteFromCookie, self::ALLOWED_PALETTES, true)) {
            $palette = $paletteFromCookie;
        } elseif (in_array($paletteFromConfig, self::ALLOWED_PALETTES, true)) {
            $palette = $paletteFromConfig;
        } else {
            $palette = 'blue';
        }

        $this->persistPaletteCookie($palette);

        $flash = $this->pullFormFlash();
        if ($hasSeoVariantQuery) {
            $response = $response->withHeader('X-Robots-Tag', 'noindex, nofollow');
        }

        $landingContent = $this->landingContent();
        $seo = new SeoMetadata($this->config, $landingContent);
        $seoMeta = $seo->meta();

        return $this->twig->render($response, 'pages/home.twig', [
            'app_name' => $this->config['app_name'] ?? 'Clínica Médica',
            'app_mark' => $this->config['app_mark'] ?? 'M',
            'page_title' => $this->config['page_title'] ?? null,
            'landing_content' => $landingContent,
            'seo_meta' => $seoMeta,
            'structured_data_json' => $seo->structuredDataJson($seoMeta),
            'palette' => $palette,
            'show_palette_selector' => (bool) ($this->config['show_palette_selector'] ?? false),
            'recaptcha_enabled' => $this->isRecaptchaFrontendEnabled(),
            'recaptcha_site_key' => (string) ($this->config['recaptcha_site_key'] ?? ''),
            'recaptcha_action' => $this->recaptchaAction(),
            'canonical_url' => $seoMeta['canonical_url'],
            'should_noindex' => $hasSeoVariantQuery,
            'csrf_token' => $this->issueContactCsrfToken(),
            'allowed_palettes' => self::ALLOWED_PALETTES,
            'form_status' => $flash['status'] ?? null,
            'form_data' => $flash['data'] ?? [
                'nome' => '',
                'telefone' => '',
                'email' => '',
                'empresa' => '',
                'mensagem' => '',
            ],
            'form_errors' => $flash['errors'] ?? [],
        ]);
    }

    public function contact(Request $request, Response $response): Response
    {
        $parsed = $request->getParsedBody();
        $post = is_array($parsed) ? $parsed : [];

        if (!$this->hasValidCsrfToken((string) ($post['csrf_token'] ?? ''))) {
            $this->setFormFlash([
                'type' => 'error',
                'message' => 'Sua sessão expirou ou o formulário ficou inválido. Atualize a página e tente novamente.',
            ]);
            return $this->redirectToForm($response);
        }

        if ($this->isBotSubmission($post)) {
            $this->setFormFlash([
                'type' => 'warning',
                'message' => 'Não foi possível processar o envio. Revise os campos e tente novamente.',
            ]);
            return $this->redirectToForm($response);
        }

        $data = [
            'nome' => trim((string) ($post['nome'] ?? '')),
            'telefone' => trim((string) ($post['telefone'] ?? '')),
            'email' => trim((string) ($post['email'] ?? '')),
            'empresa' => trim((string) ($post['empresa'] ?? '')),
            'mensagem' => trim((string) ($post['mensagem'] ?? '')),
        ];

        if ($this->hasHitContactRateLimit()) {
            $this->setFormFlash([
                'type' => 'warning',
                'message' => 'Recebemos muitas tentativas seguidas desta origem. Aguarde alguns minutos e tente novamente.',
            ], $data);
            return $this->redirectToForm($response);
        }

        $errors = $this->validateContact($data);
        if ($errors !== []) {
            $this->setFormFlash(
                ['type' => 'error', 'message' => 'Revise os campos e tente novamente.'],
                $data,
                $errors
            );
            return $this->redirectToForm($response);
        }

        $eventId = $this->newTrackingEventId();
        $requestId = $this->newRequestId();

        $recaptchaResult = $this->verifyRecaptchaToken((string) ($post['recaptcha_token'] ?? ''));
        if (!($recaptchaResult['ok'] ?? false)) {
            $reason = 'reCAPTCHA falhou: ' . (string) ($recaptchaResult['error'] ?? 'erro desconhecido');
            $this->persistLeadEvent($eventId, $requestId, 'failure', $reason, $data);
            $this->setFormFlash([
                'type' => 'warning',
                'message' => 'Não foi possível validar o reCAPTCHA. Atualize a página e tente novamente.',
                'event_id' => $eventId,
                'request_id' => $requestId,
                'tracking_event' => 'lead_form_submit_failure',
            ], $data);
            return $this->redirectToForm($response);
        }

        $to = $this->config['contact_to'] ?? null;
        if (!$to) {
            $reason = 'CONTACT_TO ausente no .env';
            $this->persistFallbackLead($data, $reason);
            $this->persistLeadEvent($eventId, $requestId, 'failure', $reason, $data);
            $this->setFormFlash([
                'type' => 'warning',
                'message' => 'Recebemos sua solicitação de agendamento, mas o e-mail de destino não está configurado no servidor. Entre em contato também pelo WhatsApp.',
                'event_id' => $eventId,
                'request_id' => $requestId,
                'tracking_event' => 'lead_form_submit_failure',
            ]);
            return $this->redirectToForm($response);
        }

        $submittedAt = date('d/m/Y H:i:s');
        $subject = $this->brandName() . ' | Nova solicitação de agendamento - Protocolo ' . $requestId;
        $textBody = $this->buildLeadTextBody($eventId, $requestId, $submittedAt, $data);
        $htmlBody = $this->buildLeadHtmlBody($eventId, $requestId, $submittedAt, $data);

        $from = $this->resolveFromAddress();
        $replyTo = ($data['email'] !== '' && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) ? $data['email'] : null;

        $customSender = $this->config['mail_sender'] ?? null;
        if (is_callable($customSender)) {
            $result = $customSender([
                'to' => $to,
                'subject' => $subject,
                'text_body' => $textBody,
                'html_body' => $htmlBody,
                'from' => $from,
                'reply_to' => $replyTo,
                'data' => $data,
                'event_id' => $eventId,
                'request_id' => $requestId,
            ]);

            if (!is_array($result) || !($result['ok'] ?? false)) {
                $reason = 'mail_sender falhou: ' . (($result['error'] ?? 'erro desconhecido'));
                $this->persistFallbackLead($data, $reason);
                $this->persistLeadEvent($eventId, $requestId, 'failure', $reason, $data);
                $this->setFormFlash([
                    'type' => 'warning',
                    'message' => 'Recebemos sua solicitação de agendamento, mas o envio de e-mail falhou no servidor. Entre em contato também pelo WhatsApp.',
                    'event_id' => $eventId,
                    'request_id' => $requestId,
                    'tracking_event' => 'lead_form_submit_failure',
                ]);
                return $this->redirectToForm($response);
            }
        } elseif ($this->useSmtpDriver()) {
            if (!$this->isSmtpConfigured()) {
                $reason = 'SMTP selecionado, mas incompleto no .env';
                $this->persistFallbackLead($data, $reason);
                $this->persistLeadEvent($eventId, $requestId, 'failure', $reason, $data);
                $this->setFormFlash([
                    'type' => 'warning',
                    'message' => 'Recebemos sua solicitação de agendamento, mas o SMTP está incompleto no servidor. Entre em contato também pelo WhatsApp.',
                    'event_id' => $eventId,
                    'request_id' => $requestId,
                    'tracking_event' => 'lead_form_submit_failure',
                ]);
                return $this->redirectToForm($response);
            }

            $smtpResult = $this->sendViaSmtp($to, $subject, $textBody, $htmlBody, $from, $replyTo);
            if (!$smtpResult['ok']) {
                $reason = 'SMTP falhou: ' . ($smtpResult['error'] ?? 'erro desconhecido');
                $this->persistFallbackLead($data, $reason);
                $this->persistLeadEvent($eventId, $requestId, 'failure', $reason, $data);
                $this->setFormFlash([
                    'type' => 'warning',
                    'message' => 'Recebemos sua solicitação de agendamento, mas o envio de e-mail falhou no SMTP. Entre em contato também pelo WhatsApp.',
                    'event_id' => $eventId,
                    'request_id' => $requestId,
                    'tracking_event' => 'lead_form_submit_failure',
                ]);
                return $this->redirectToForm($response);
            }
        } else {
            if (!$this->canUseNativeMail()) {
                $reason = 'Transporte de email indisponível (sendmail_path inválido)';
                $this->persistFallbackLead($data, $reason);
                $this->persistLeadEvent($eventId, $requestId, 'failure', $reason, $data);
                $this->setFormFlash([
                    'type' => 'warning',
                    'message' => 'Recebemos sua solicitação de agendamento, mas o servidor de e-mail não está configurado. Entre em contato também pelo WhatsApp.',
                    'event_id' => $eventId,
                    'request_id' => $requestId,
                    'tracking_event' => 'lead_form_submit_failure',
                ]);
                return $this->redirectToForm($response);
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
                $reason = 'mail() retornou false';
                $this->persistFallbackLead($data, $reason);
                $this->persistLeadEvent($eventId, $requestId, 'failure', $reason, $data);
                $this->setFormFlash([
                    'type' => 'warning',
                    'message' => 'Recebemos sua solicitação de agendamento, mas o envio de e-mail falhou no servidor. Entre em contato também pelo WhatsApp.',
                    'event_id' => $eventId,
                    'request_id' => $requestId,
                    'tracking_event' => 'lead_form_submit_failure',
                ]);
                return $this->redirectToForm($response);
            }
        }

        $this->persistLeadEvent($eventId, $requestId, 'success', 'Email enviado com sucesso', $data);
        $this->setFormFlash([
            'type' => 'success',
            'message' => 'Recebemos sua solicitação de agendamento. Protocolo: ' . $requestId . '. Em breve retornaremos no WhatsApp/e-mail.',
            'event_id' => $eventId,
            'request_id' => $requestId,
            'tracking_event' => 'lead_form_submit_success',
        ]);

        return $this->redirectToForm($response);
    }

    private function issueContactCsrfToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return '';
        }

        $token = $_SESSION[self::CSRF_SESSION_KEY] ?? null;
        if (is_string($token) && $token !== '') {
            return $token;
        }

        try {
            $token = bin2hex(random_bytes(32));
        } catch (\Throwable $e) {
            return '';
        }

        $_SESSION[self::CSRF_SESSION_KEY] = $token;
        return $token;
    }

    private function hasValidCsrfToken(string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $expected = $_SESSION[self::CSRF_SESSION_KEY] ?? null;
        return is_string($expected) && $expected !== '' && $token !== '' && hash_equals($expected, $token);
    }

    private function isBotSubmission(array $post): bool
    {
        $honeypot = trim((string) ($post['website'] ?? ''));
        return $honeypot !== '';
    }

    private function isRecaptchaEnabled(): bool
    {
        return (bool) ($this->config['recaptcha_enabled'] ?? false);
    }

    private function isRecaptchaFrontendEnabled(): bool
    {
        return $this->isRecaptchaEnabled() && trim((string) ($this->config['recaptcha_site_key'] ?? '')) !== '';
    }

    private function recaptchaAction(): string
    {
        $action = trim((string) ($this->config['recaptcha_action'] ?? 'contact_submit'));
        return $action !== '' ? $action : 'contact_submit';
    }

    private function verifyRecaptchaToken(string $token): array
    {
        if (!$this->isRecaptchaEnabled()) {
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
                'secret' => $secret,
                'token' => $token,
                'remote_ip' => $this->resolveClientIp(),
                'action' => $this->recaptchaAction(),
            ]);
            if (!is_array($result)) {
                return ['ok' => false, 'error' => 'verificador retornou resposta invalida'];
            }
        } else {
            $result = $this->requestRecaptchaVerification($secret, $token, $this->resolveClientIp());
        }

        if (!($result['success'] ?? false)) {
            $codes = $result['error-codes'] ?? [];
            $error = is_array($codes) && $codes !== [] ? implode(',', array_map('strval', $codes)) : 'success=false';
            return ['ok' => false, 'error' => $error];
        }

        $expectedAction = $this->recaptchaAction();
        $actualAction = (string) ($result['action'] ?? '');
        if ($expectedAction !== '' && $actualAction !== $expectedAction) {
            return ['ok' => false, 'error' => 'action inesperada'];
        }

        $minScore = (float) ($this->config['recaptcha_min_score'] ?? 0.5);
        $score = $result['score'] ?? null;
        if ($minScore > 0) {
            if (!is_numeric($score)) {
                return ['ok' => false, 'error' => 'score ausente'];
            }
            if ((float) $score < $minScore) {
                return ['ok' => false, 'error' => 'score baixo'];
            }
        }

        $allowedHostnames = $this->allowedRecaptchaHostnames();
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

    private function allowedRecaptchaHostnames(): array
    {
        $raw = strtolower(trim((string) ($this->config['recaptcha_allowed_hostname'] ?? '')));
        if ($raw === '') {
            return [];
        }

        return array_values(array_unique(array_filter(
            preg_split('/[\s,]+/', $raw) ?: [],
            static fn (string $hostname): bool => $hostname !== ''
        )));
    }

    private function requestRecaptchaVerification(string $secret, string $token, string $remoteIp): array
    {
        $payload = http_build_query([
            'secret' => $secret,
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
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT => 6,
            ]);

            $body = curl_exec($ch);
            $error = curl_error($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            if ($body === false || $status >= 400) {
                return ['success' => false, 'error-codes' => ['request-failed:' . ($error !== '' ? $error : 'http-' . $status)]];
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'content' => $payload,
                    'timeout' => 6,
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

    private function hasHitContactRateLimit(): bool
    {
        $maxAttempts = (int) ($this->config['rate_limit_max_attempts'] ?? 5);
        $windowSeconds = (int) ($this->config['rate_limit_window_seconds'] ?? 600);

        if ($maxAttempts <= 0 || $windowSeconds <= 0) {
            return false;
        }

        $ip = $this->resolveClientIp();
        $dir = $this->storagePath() . '/rate-limit';
        $file = $dir . '/contact-' . sha1($ip) . '.json';

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $now = time();
        $attempts = [];

        if (is_file($file)) {
            $decoded = json_decode((string) file_get_contents($file), true);
            if (is_array($decoded)) {
                $attempts = array_values(array_filter($decoded, static function ($timestamp) use ($now, $windowSeconds): bool {
                    return is_int($timestamp) && $timestamp >= ($now - $windowSeconds);
                }));
            }
        }

        if (count($attempts) >= $maxAttempts) {
            @file_put_contents($file, json_encode($attempts), LOCK_EX);
            return true;
        }

        $attempts[] = $now;
        @file_put_contents($file, json_encode($attempts), LOCK_EX);
        return false;
    }

    private function validateContact(array $data): array
    {
        $errors = [];
        if ($data['nome'] === '') {
            $errors['nome'] = true;
        }
        if ($data['telefone'] === '') {
            $errors['telefone'] = true;
        }
        if ($data['mensagem'] === '') {
            $errors['mensagem'] = true;
        }
        if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = true;
        }
        return $errors;
    }

    private function resolveFromAddress(): string
    {
        $from = $this->config['contact_from'] ?? '';
        if ($from !== '') {
            return $from;
        }
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $host = preg_replace('/:\\d+$/', '', $host);
        return 'no-reply@' . $host;
    }

    private function resolveClientIp(): string
    {
        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        return $ip !== '' ? $ip : 'unknown';
    }

    private function setFormFlash(array $status, ?array $data = null, ?array $errors = null): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION['form_flash'] = [
            'status' => $status,
            'data' => $data ?? [
                'nome' => '',
                'telefone' => '',
                'email' => '',
                'empresa' => '',
                'mensagem' => '',
            ],
            'errors' => $errors ?? [],
        ];
    }

    private function pullFormFlash(): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }

        $flash = $_SESSION['form_flash'] ?? null;
        if ($flash !== null) {
            unset($_SESSION['form_flash']);
        }
        return is_array($flash) ? $flash : null;
    }

    private function redirectToForm(Response $response): Response
    {
        $base = $this->config['base_url'] ?? '';
        return $response
            ->withHeader('Location', rtrim($base, '/') . '/#form-orcamento')
            ->withStatus(302);
    }

    private function persistPaletteCookie(string $palette): void
    {
        if (!in_array($palette, self::ALLOWED_PALETTES, true)) {
            return;
        }

        if ((string) ($_COOKIE[self::PALETTE_COOKIE_NAME] ?? '') === $palette) {
            return;
        }

        setcookie(self::PALETTE_COOKIE_NAME, $palette, [
            'expires' => time() + self::PALETTE_COOKIE_TTL,
            'path' => $this->resolveCookiePath(),
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
    }

    private function resolveCookiePath(): string
    {
        $base = trim((string) ($this->config['base_url'] ?? ''));
        if ($base === '' || $base === '/') {
            return '/';
        }

        $normalized = '/' . trim($base, '/');
        return $normalized . '/';
    }

    private function hasSeoVariantQuery(array $queryParams): bool
    {
        return array_key_exists('palette', $queryParams);
    }

    private function landingContent(): array
    {
        $content = $this->config['landing_content'] ?? [];
        return is_array($content) ? $content : [];
    }

    private function useSmtpDriver(): bool
    {
        $driver = strtolower(trim((string) ($this->config['mail_driver'] ?? 'mail')));
        return $driver === 'smtp';
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
            $mail->Host = (string) $this->config['smtp_host'];
            $mail->Port = (int) $this->config['smtp_port'];
            $mail->SMTPAuth = (bool) $this->config['smtp_auth'];
            $mail->Username = (string) $this->config['smtp_user'];
            $mail->Password = (string) $this->config['smtp_pass'];
            $mail->Timeout = (int) $this->config['smtp_timeout'];
            $mail->CharSet = 'UTF-8';

            $enc = strtolower(trim((string) ($this->config['smtp_encryption'] ?? 'tls')));
            if ($enc === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($enc === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            }

            $mail->setFrom($from, (string) ($this->config['app_name'] ?? 'Clínica Médica'));
            $mail->addAddress($to);
            if ($replyTo !== null) {
                $mail->addReplyTo($replyTo);
            }

            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody;
            $mail->isHTML(true);

            $mail->send();
            return ['ok' => true];
        } catch (MailException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function buildLeadTextBody(string $eventId, string $requestId, string $submittedAt, array $data): string
    {
        $lines = [
            'NOVA SOLICITAÇÃO DE AGENDAMENTO',
            str_repeat('=', 34),
            'Protocolo: ' . $requestId,
            'ID do evento: ' . $eventId,
            'Data/Hora: ' . $submittedAt,
            'Origem: ' . $this->resolveOrigin(),
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

    private function buildLeadHtmlBody(string $eventId, string $requestId, string $submittedAt, array $data): string
    {
        $name = htmlspecialchars((string) ($data['nome'] ?? '-'), ENT_QUOTES, 'UTF-8');
        $phone = htmlspecialchars((string) ($data['telefone'] ?? '-'), ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars((string) (($data['email'] ?? '') !== '' ? $data['email'] : '-'), ENT_QUOTES, 'UTF-8');
        $notes = htmlspecialchars((string) (($data['empresa'] ?? '') !== '' ? $data['empresa'] : '-'), ENT_QUOTES, 'UTF-8');
        $message = nl2br(htmlspecialchars((string) ($data['mensagem'] ?? ''), ENT_QUOTES, 'UTF-8'));
        $safeEventId = htmlspecialchars($eventId, ENT_QUOTES, 'UTF-8');
        $safeRequestId = htmlspecialchars($requestId, ENT_QUOTES, 'UTF-8');
        $safeSubmittedAt = htmlspecialchars($submittedAt, ENT_QUOTES, 'UTF-8');
        $safeOrigin = htmlspecialchars($this->resolveOrigin(), ENT_QUOTES, 'UTF-8');

        $normalizedWhatsapp = $this->normalizeWhatsappNumber((string) ($data['telefone'] ?? ''));
        $whatsMessage = rawurlencode('Olá! Recebemos sua solicitação de agendamento. Protocolo: ' . $requestId . '. Vamos continuar por aqui.');
        $whatsHref = $normalizedWhatsapp !== null ? 'https://wa.me/' . $normalizedWhatsapp . '?text=' . $whatsMessage : '#';
        $safeWhatsHref = htmlspecialchars($whatsHref, ENT_QUOTES, 'UTF-8');
        $safeReplyHref = htmlspecialchars('mailto:' . (($data['email'] ?? '') !== '' ? $data['email'] : ''), ENT_QUOTES, 'UTF-8');

        $brandName = htmlspecialchars((string) ($this->config['app_name'] ?? 'Clínica Médica'), ENT_QUOTES, 'UTF-8');

        return <<<HTML
<div style="background:#f6f8fb;padding:16px;font-family:Arial,sans-serif;color:#111827;">
  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:680px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
    <tr>
      <td style="padding:16px;background:#ffffff;color:#111827;border-bottom:1px solid #e5e7eb;">
        <div style="font-size:20px;line-height:1.3;font-weight:800;">{$brandName}</div>
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

    private function resolveOrigin(): string
    {
        $forwardedProto = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
        $scheme = $forwardedProto === 'https' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = rtrim((string) ($this->config['base_url'] ?? ''), '/');
        return $scheme . '://' . $host . ($base !== '' ? $base : '');
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

    private function canUseNativeMail(): bool
    {
        if (\PHP_OS_FAMILY === 'Windows') {
            return true;
        }

        $sendmailPath = trim((string) ini_get('sendmail_path'));
        if ($sendmailPath === '') {
            return false;
        }

        $parts = preg_split('/\s+/', $sendmailPath);
        $binary = $parts[0] ?? '';
        return $binary !== '' && is_executable($binary);
    }

    private function persistFallbackLead(array $data, string $reason): void
    {
        $logDir = $this->storagePath() . '/logs';
        $logFile = $logDir . '/contatos-fallback.log';

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $entry = [
            'timestamp' => date('c'),
            'reason' => $reason,
            'nome' => $data['nome'] ?? '',
            'telefone' => $data['telefone'] ?? '',
            'email' => $data['email'] ?? '',
            'empresa' => $data['empresa'] ?? '',
            'mensagem' => $data['mensagem'] ?? '',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ];

        @file_put_contents($logFile, json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    }

    private function newTrackingEventId(): string
    {
        $prefix = $this->eventPrefix();
        try {
            return $prefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(6));
        } catch (\Throwable $e) {
            return $prefix . '_' . date('YmdHis') . '_' . substr(sha1((string) microtime(true)), 0, 12);
        }
    }

    private function newRequestId(): string
    {
        $prefix = $this->requestPrefix();
        try {
            return $prefix . '-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        } catch (\Throwable $e) {
            return $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(sha1((string) microtime(true)), 0, 4));
        }
    }

    private function brandName(): string
    {
        $name = trim((string) ($this->config['app_name'] ?? ''));
        return $name !== '' ? $name : 'Clínica Médica';
    }

    private function eventPrefix(): string
    {
        $slug = strtolower(trim((string) ($this->config['app_slug'] ?? '')));
        if ($slug === '') {
            $slug = trim((string) ($this->config['base_url'] ?? ''), '/');
        }

        $slug = preg_replace('/[^a-z0-9]+/', '_', strtolower($slug)) ?? '';
        $slug = trim($slug, '_');
        return $slug !== '' ? $slug : 'landing';
    }

    private function requestPrefix(): string
    {
        $configured = strtoupper(trim((string) ($this->config['request_prefix'] ?? '')));
        $configured = preg_replace('/[^A-Z0-9]/', '', $configured) ?? '';
        if ($configured !== '') {
            return substr($configured, 0, 12);
        }

        $slug = strtoupper(str_replace('_', '', $this->eventPrefix()));
        return substr($slug !== '' ? $slug : 'LANDING', 0, 6);
    }

    private function persistLeadEvent(string $eventId, string $requestId, string $result, string $reason, array $data): void
    {
        $logDir = $this->storagePath() . '/logs';
        $logFile = $logDir . '/lead-events.log';

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $entry = [
            'timestamp' => date('c'),
            'event_id' => $eventId,
            'request_id' => $requestId,
            'result' => $result,
            'reason' => $reason,
            'nome' => $data['nome'] ?? '',
            'telefone' => $data['telefone'] ?? '',
            'email' => $data['email'] ?? '',
            'empresa' => $data['empresa'] ?? '',
            'mensagem' => $data['mensagem'] ?? '',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];

        @file_put_contents($logFile, json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
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
