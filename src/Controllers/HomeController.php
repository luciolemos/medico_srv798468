<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Contact\ContactValidator;
use App\Contact\LeadLoggerInterface;
use App\Contact\MailerInterface;
use App\Contact\RateLimiterInterface;
use App\Contact\RecaptchaVerifierInterface;
use App\Core\SeoMetadata;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class HomeController
{
    private const ALLOWED_PALETTES     = ['blue', 'red', 'emerald', 'amber', 'violet'];
    private const CSRF_SESSION_KEY     = 'contact_csrf_token';
    private const FORM_FLASH_KEY       = 'form_flash';
    private const HONEYPOT_FIELD       = 'website';
    private const PALETTE_COOKIE_NAME  = 'palette';
    private const PALETTE_COOKIE_TTL   = 31536000;

    public function __construct(
        private Twig $twig,
        private array $config,
        private RecaptchaVerifierInterface $recaptchaVerifier,
        private RateLimiterInterface $rateLimiter,
        private MailerInterface $mailer,
        private LeadLoggerInterface $leadLogger,
    ) {
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
        } elseif (in_array($paletteFromConfig, self::ALLOWED_PALETTES, true)) {
            $palette = $paletteFromConfig;
        } elseif (in_array($paletteFromCookie, self::ALLOWED_PALETTES, true)) {
            $palette = $paletteFromCookie;
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
            'app_slug' => $this->config['app_slug'] ?? 'medico',
            'page_title' => $this->config['page_title'] ?? null,
            'landing_content' => $landingContent,
            'seo_meta' => $seoMeta,
            'structured_data_json' => $seo->structuredDataJson($seoMeta),
            'palette' => $palette,
            'show_palette_selector' => (bool) ($this->config['show_palette_selector'] ?? false),
            'recaptcha_enabled' => $this->recaptchaVerifier->isFrontendEnabled(),
            'recaptcha_site_key' => $this->recaptchaVerifier->siteKey(),
            'recaptcha_action' => $this->recaptchaVerifier->action(),
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
        $post   = is_array($parsed) ? $parsed : [];

        if (!$this->hasValidCsrfToken((string) ($post['csrf_token'] ?? ''))) {
            $this->setFormFlash([
                'type'    => 'error',
                'message' => 'Sua sessão expirou ou o formulário ficou inválido. Atualize a página e tente novamente.',
            ]);
            return $this->redirectToForm($response);
        }

        if ($this->isBotSubmission($post)) {
            $this->setFormFlash([
                'type'    => 'warning',
                'message' => 'Não foi possível processar o envio. Revise os campos e tente novamente.',
            ]);
            return $this->redirectToForm($response);
        }

        $data = [
            'nome'     => trim((string) ($post['nome'] ?? '')),
            'telefone' => trim((string) ($post['telefone'] ?? '')),
            'email'    => trim((string) ($post['email'] ?? '')),
            'empresa'  => trim((string) ($post['empresa'] ?? '')),
            'mensagem' => trim((string) ($post['mensagem'] ?? '')),
        ];

        $clientIp  = $this->resolveClientIp();
        $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

        if ($this->rateLimiter->hasHit($clientIp)) {
            $this->setFormFlash([
                'type'    => 'warning',
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

        $eventId   = $this->newTrackingEventId();
        $requestId = $this->newRequestId();

        $recaptchaResult = $this->recaptchaVerifier->verify((string) ($post['recaptcha_token'] ?? ''), $clientIp);
        if (!($recaptchaResult['ok'] ?? false)) {
            $reason = 'reCAPTCHA falhou: ' . (string) ($recaptchaResult['error'] ?? 'erro desconhecido');
            $this->leadLogger->persistEvent($eventId, $requestId, 'failure', $reason, $data, $clientIp, $userAgent);
            $this->setFormFlash([
                'type'           => 'warning',
                'message'        => 'Não foi possível validar o reCAPTCHA. Atualize a página e tente novamente.',
                'event_id'       => $eventId,
                'request_id'     => $requestId,
                'tracking_event' => 'lead_form_submit_failure',
            ], $data);
            return $this->redirectToForm($response);
        }

        $to = $this->config['contact_to'] ?? null;
        if (!$to) {
            $reason = 'CONTACT_TO ausente no .env';
            $this->leadLogger->persistFallbackLead($data, $reason, $eventId, $requestId, $clientIp);
            $this->leadLogger->persistEvent($eventId, $requestId, 'failure', $reason, $data, $clientIp, $userAgent);
            $this->setFormFlash([
                'type'           => 'warning',
                'message'        => 'Recebemos sua solicitação de agendamento, mas o e-mail de destino não está configurado no servidor. Entre em contato também pelo WhatsApp.',
                'event_id'       => $eventId,
                'request_id'     => $requestId,
                'tracking_event' => 'lead_form_submit_failure',
            ]);
            return $this->redirectToForm($response);
        }

        $submittedAt = date('d/m/Y H:i:s');
        $origin      = $this->resolveOrigin();
        $subject     = $this->brandName() . ' | Nova solicitação de agendamento - Protocolo ' . $requestId;
        $textBody    = $this->mailer->buildTextBody($eventId, $requestId, $submittedAt, $data, $origin);
        $htmlBody    = $this->mailer->buildHtmlBody($eventId, $requestId, $submittedAt, $data, $origin, $this->brandName());
        $from        = $this->mailer->resolveFromAddress();
        $replyTo     = ($data['email'] !== '' && filter_var($data['email'], FILTER_VALIDATE_EMAIL))
            ? $data['email']
            : null;

        $sendResult = $this->mailer->send($to, $subject, $textBody, $htmlBody, $from, $replyTo, $data, $eventId, $requestId);
        if (!$sendResult['ok']) {
            $this->leadLogger->persistFallbackLead($data, $sendResult['reason'], $eventId, $requestId, $clientIp);
            $this->leadLogger->persistEvent($eventId, $requestId, 'failure', $sendResult['reason'], $data, $clientIp, $userAgent);
            $this->setFormFlash([
                'type'           => 'warning',
                'message'        => $sendResult['user_message'],
                'event_id'       => $eventId,
                'request_id'     => $requestId,
                'tracking_event' => 'lead_form_submit_failure',
            ]);
            return $this->redirectToForm($response);
        }

        $this->leadLogger->persistEvent($eventId, $requestId, 'success', 'Email enviado com sucesso', $data, $clientIp, $userAgent);
        $this->setFormFlash([
            'type'           => 'success',
            'message'        => 'Recebemos sua solicitação de agendamento. Protocolo: ' . $requestId . '. Em breve retornaremos no WhatsApp/e-mail.',
            'event_id'       => $eventId,
            'request_id'     => $requestId,
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

    private function validateContact(array $data): array
    {
        return (new ContactValidator())->validate($data);
    }


    private function resolveClientIp(): string
    {
        $remoteAddr = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        $trustedProxies = $this->trustedProxies();

        if ($remoteAddr !== '' && in_array($remoteAddr, $trustedProxies, true)) {
            $candidates = [
                trim((string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? '')),
                trim((string) ($_SERVER['HTTP_X_REAL_IP'] ?? '')),
            ];

            $forwardedFor = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
            if ($forwardedFor !== '') {
                $parts = explode(',', $forwardedFor);
                $first = trim((string) ($parts[0] ?? ''));
                if ($first !== '') {
                    $candidates[] = $first;
                }
            }

            foreach ($candidates as $candidate) {
                if ($this->isValidIp($candidate)) {
                    return $candidate;
                }
            }
        }

        return $this->isValidIp($remoteAddr) ? $remoteAddr : 'unknown';
    }

    /**
     * @return array<int, string>
     */
    private function trustedProxies(): array
    {
        $raw = $this->config['trusted_proxies'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        $valid = [];
        foreach ($raw as $ip) {
            if (is_string($ip) && $this->isValidIp($ip)) {
                $valid[] = $ip;
            }
        }

        return array_values(array_unique($valid));
    }

    private function isValidIp(string $value): bool
    {
        return $value !== '' && filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    private function setFormFlash(array $status, ?array $data = null, ?array $errors = null): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION[self::FORM_FLASH_KEY] = [
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

        $flash = $_SESSION[self::FORM_FLASH_KEY] ?? null;
        if ($flash !== null) {
            unset($_SESSION[self::FORM_FLASH_KEY]);
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

    private function resolveOrigin(): string
    {
        $publicOrigin = trim((string) ($this->config['public_origin'] ?? ''));
        if ($publicOrigin !== '') {
            return rtrim($publicOrigin, '/');
        }

        $forwardedProto = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
        $scheme = $forwardedProto === 'https' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $configuredHost = trim((string) ($this->config['public_host'] ?? ''));
        $host = $configuredHost !== '' ? $configuredHost : (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $base = rtrim((string) ($this->config['base_url'] ?? ''), '/');
        return $scheme . '://' . $host . ($base !== '' ? $base : '');
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
}
