<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Tests\Support\TestAppFactory;

final class HomeRoutesTest extends TestCase
{
    private string $storagePath;

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        $this->storagePath = sys_get_temp_dir() . '/medico-tests-' . bin2hex(random_bytes(4));
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
        unset($_SERVER['HTTPS']);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->storagePath);
    }

    public function testHomeRendersConfiguredPageTitleOnSubpath(): void
    {
        $app = TestAppFactory::create([
            'page_title' => 'Clínica Médica | Teste de Home',
            'base_url' => '/medico',
        ]);

        $response = $this->request($app, 'GET', '/medico/');
        $html = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('<title>Clínica Médica | Teste de Home</title>', $html);
        self::assertStringContainsString('/medico/assets/css/landing.css', $html);
        self::assertMatchesRegularExpression('/name="csrf_token" value="[a-f0-9]{64}"/', $html);
    }

    public function testHomeRendersCorrectAssetPathsWhenBasePathIsEmpty(): void
    {
        $app = TestAppFactory::create([
            'page_title' => 'Clínica Médica | Root',
            'base_url' => '',
        ]);

        $response = $this->request($app, 'GET', '/');
        $html = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('<title>Clínica Médica | Root</title>', $html);
        self::assertStringContainsString('href="/assets/css/landing.css?v=', $html);
        self::assertStringContainsString('src="/assets/img/hero/medico-640.webp"', $html);
        self::assertStringContainsString('media="(max-width: 576px)"', $html);
        self::assertStringContainsString('/assets/img/hero/medico-mobile-640.webp', $html);
        self::assertStringNotContainsString('//assets/', $html);
    }

    public function testHomeAddsBaselineSecurityHeaders(): void
    {
        $app = TestAppFactory::create([
            'base_url' => '/medico',
        ]);

        $response = $this->request($app, 'GET', '/medico/');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        self::assertSame('SAMEORIGIN', $response->getHeaderLine('X-Frame-Options'));
        self::assertSame('strict-origin-when-cross-origin', $response->getHeaderLine('Referrer-Policy'));
        self::assertStringContainsString('geolocation=()', $response->getHeaderLine('Permissions-Policy'));
    }

    public function testHomeRendersStructuredSeoMetadata(): void
    {
        $app = TestAppFactory::create([
            'base_url' => '/medico',
            'whatsapp_url' => 'https://wa.me/5584999031906',
            'facebook_url' => 'https://facebook.com/clinica-medica',
            'instagram_url' => 'https://instagram.com',
            'x_url' => 'https://x.com',
        ]);

        $response = $this->request($app, 'GET', '/medico/');
        $html = (string) $response->getBody();
        $decodedHtml = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $structuredData = $this->extractStructuredData($html);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('<link rel="canonical" href="http://localhost/medico/">', $decodedHtml);
        self::assertStringContainsString('<meta property="og:type" content="website">', $decodedHtml);
        self::assertStringContainsString('<meta property="og:title" content="Clínica Médica | Teste">', $decodedHtml);
        self::assertStringContainsString('<meta property="og:image" content="http://localhost/medico/assets/img/social/medico-og.jpg">', $decodedHtml);
        self::assertStringContainsString('<meta name="twitter:card" content="summary_large_image">', $decodedHtml);

        self::assertSame('https://schema.org', $structuredData['@context'] ?? null);
        self::assertSame('MedicalClinic', $structuredData['@graph'][0]['@type'] ?? null);
        self::assertSame('http://localhost/medico/', $structuredData['@graph'][0]['url'] ?? null);
        self::assertSame('+5584999031906', $structuredData['@graph'][0]['telephone'] ?? null);
        self::assertSame(['https://facebook.com/clinica-medica'], $structuredData['@graph'][0]['sameAs'] ?? []);
        self::assertSame('OfferCatalog', $structuredData['@graph'][0]['hasOfferCatalog']['@type'] ?? null);
        self::assertSame('FAQPage', $structuredData['@graph'][1]['@type'] ?? null);
    }

    public function testHomeUsesConfiguredCanonicalUrlForSeoMetadata(): void
    {
        $app = TestAppFactory::create([
            'base_url' => '/medico',
            'canonical_url' => 'https://example.com/medico',
        ]);

        $response = $this->request($app, 'GET', '/medico/');
        $html = (string) $response->getBody();
        $decodedHtml = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $structuredData = $this->extractStructuredData($html);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('<link rel="canonical" href="https://example.com/medico/">', $decodedHtml);
        self::assertStringContainsString('<meta property="og:image" content="https://example.com/medico/assets/img/social/medico-og.jpg">', $decodedHtml);
        self::assertSame('https://example.com/medico/', $structuredData['@graph'][0]['url'] ?? null);
    }

    public function testHomeFallsBackToBluePaletteWhenQueryPaletteIsInvalid(): void
    {
        $app = TestAppFactory::create([
            'palette' => 'emerald',
            'base_url' => '/medico',
        ]);

        $response = $this->request($app, 'GET', '/medico/?palette=invalida');
        $html = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('/medico/assets/css/palettes/blue.css', $html);
        self::assertSame('noindex, nofollow', $response->getHeaderLine('X-Robots-Tag'));
        self::assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $html);
    }

    public function testHomeRendersClinicCopyAndPaletteStateFromQueryString(): void
    {
        $app = TestAppFactory::create([
            'palette' => 'blue',
            'base_url' => '/medico',
        ]);

        $response = $this->request($app, 'GET', '/medico/?palette=red');
        $html = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('data-typography="clinical"', $html);
        self::assertStringContainsString('Cuidado médico', $html);
        self::assertStringContainsString('Serviços da clínica', $html);
        self::assertStringContainsString('Desenvolvido por NatalCode - Soluções Digitais', $html);
        self::assertStringNotContainsString('id="copyModeToggle"', $html);
        self::assertStringNotContainsString('id="paletteFabToggle"', $html);
        self::assertStringNotContainsString('data-palette-btn="red"', $html);
        self::assertStringContainsString('/medico/assets/css/palettes/red.css', $html);
    }

    public function testHomeRendersLandingContentOverrides(): void
    {
        $app = TestAppFactory::create([
            'page_title' => null,
            'base_url' => '/pediatria',
            'landing_content' => [
                'seo' => [
                    'title' => 'Clínica Pediátrica | Consulta infantil',
                    'description' => 'Pediatria com agenda organizada, acompanhamento infantil e retorno claro para responsáveis.',
                    'schema' => [
                        'type' => 'MedicalClinic',
                        'area_served' => 'Natal',
                        'include_faq' => false,
                    ],
                ],
                'nav' => [
                    'badge' => 'Pediatria',
                    'cta' => 'Agendar',
                ],
                'typography' => [
                    'profile' => 'family',
                ],
                'hero' => [
                    'badge' => 'Pediatria com escuta para a família',
                    'title_parts' => ['Pediatria', 'leve e segura', 'para cada fase da infância.'],
                    'lead' => 'Consultas pediátricas, puericultura e acompanhamento do desenvolvimento com orientação clara para responsáveis.',
                ],
                'services' => [
                    'title' => 'Serviços pediátricos',
                    'text' => 'Rotina de cuidado infantil com prevenção, acompanhamento e orientação.',
                    'items' => [
                        ['icon' => 'heart-pulse', 'title' => 'Puericultura', 'text' => 'Acompanhamento de crescimento, desenvolvimento e rotina de saúde.'],
                    ],
                ],
                'faq' => [
                    'title' => 'Dúvidas pediátricas',
                    'text' => 'Perguntas comuns antes do agendamento.',
                    'items' => [
                        ['question' => 'Atende recém-nascidos?', 'answer' => 'Sim, a equipe confirma disponibilidade e orientações no retorno.'],
                    ],
                ],
                'footer' => [
                    'label' => 'Pediatria',
                ],
            ],
        ]);

        $response = $this->request($app, 'GET', '/pediatria/');
        $html = (string) $response->getBody();
        $structuredData = $this->extractStructuredData($html);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('data-typography="family"', $html);
        self::assertStringContainsString('<title>Clínica Pediátrica | Consulta infantil</title>', $html);
        self::assertStringContainsString('Pediatria com agenda organizada', $html);
        self::assertStringContainsString('Pediatria com escuta para a família', $html);
        self::assertStringContainsString('Serviços pediátricos', $html);
        self::assertStringContainsString('Puericultura', $html);
        self::assertStringContainsString('Dúvidas pediátricas', $html);
        self::assertStringNotContainsString('Serviços da clínica', $html);
        self::assertSame('Natal', $structuredData['@graph'][0]['areaServed'] ?? null);
        self::assertCount(1, $structuredData['@graph']);
    }

    public function testHomeRendersPaletteSelectorWhenEnabled(): void
    {
        $app = TestAppFactory::create([
            'palette' => 'blue',
            'base_url' => '/medico',
            'show_palette_selector' => true,
        ]);

        $response = $this->request($app, 'GET', '/medico/?palette=emerald');
        $html = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('id="paletteFabToggle"', $html);
        self::assertStringContainsString('data-palette-btn="emerald"', $html);
        self::assertStringContainsString('palette-dot-emerald active', $html);
    }

    public function testHomeRendersRecaptchaAssetsWhenEnabled(): void
    {
        $app = TestAppFactory::create([
            'base_url' => '/medico',
            'recaptcha_enabled' => true,
            'recaptcha_site_key' => 'site-key-123',
            'recaptcha_action' => 'contact_submit',
        ]);

        $response = $this->request($app, 'GET', '/medico/');
        $html = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('https://www.google.com/recaptcha/api.js?render=site-key-123', $html);
        self::assertStringContainsString('class="bg-body text-body antialiased has-recaptcha"', $html);
        self::assertStringContainsString('name="recaptcha_token"', $html);
        self::assertStringContainsString('data-recaptcha-site-key="site-key-123"', $html);
        self::assertStringContainsString('data-recaptcha-action="contact_submit"', $html);
        self::assertStringContainsString('Ao enviar, você autoriza o uso dos dados informados', $html);
        self::assertStringContainsString('Política de Privacidade', $html);
    }

    public function testHomeConsumesFlashStatusAndClearsItFromSession(): void
    {
        $_SESSION['form_flash'] = [
            'status' => [
                'type' => 'success',
                'message' => 'Solicitacao recebida com sucesso.',
                'tracking_event' => 'lead_form_submit_success',
                'event_id' => 'evt_123',
                'request_id' => 'MED-20260404-ABCD',
            ],
            'data' => [
                'nome' => 'Lucio',
                'telefone' => '(84) 99999-9999',
                'email' => 'lucio@example.com',
                'empresa' => 'Particular',
                'mensagem' => 'Gostaria de agendar uma consulta clínica.',
            ],
            'errors' => [],
        ];

        $app = TestAppFactory::create([
            'base_url' => '/medico',
            'storage_path' => $this->storagePath,
        ]);

        $response = $this->request($app, 'GET', '/medico/');
        $html = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Solicitacao recebida com sucesso.', $html);
        self::assertStringContainsString('data-form-result-event="lead_form_submit_success"', $html);
        self::assertArrayNotHasKey('form_flash', $_SESSION);
    }

    public function testHomeRendersCoreSectionsAsSmokeCoverage(): void
    {
        $app = TestAppFactory::create([
            'base_url' => '/medico',
        ]);

        $response = $this->request($app, 'GET', '/medico/');
        $html = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('id="features"', $html);
        self::assertStringContainsString('id="how"', $html);
        self::assertStringContainsString('id="estrutura"', $html);
        self::assertStringContainsString('id="cta"', $html);
        self::assertStringContainsString('id="form-orcamento"', $html);
        self::assertStringContainsString('id="faq"', $html);
        self::assertStringNotContainsString('id="projects"', $html);
        self::assertStringNotContainsString('id="depoimentos"', $html);
    }

    public function testContatoWithInvalidPayloadRedirectsBackToFormAndStoresErrors(): void
    {
        $app = TestAppFactory::create([
            'base_url' => '/medico',
            'storage_path' => $this->storagePath,
        ]);

        $response = $this->submitContactForm($app, '/medico', [
            'nome' => '',
            'telefone' => '',
            'email' => 'email-invalido',
            'empresa' => '',
            'mensagem' => '',
        ]);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/medico/#form-orcamento', $response->getHeaderLine('Location'));
        self::assertSame('error', $_SESSION['form_flash']['status']['type'] ?? null);
        self::assertArrayHasKey('nome', $_SESSION['form_flash']['errors'] ?? []);
        self::assertArrayHasKey('telefone', $_SESSION['form_flash']['errors'] ?? []);
        self::assertArrayHasKey('email', $_SESSION['form_flash']['errors'] ?? []);
        self::assertArrayHasKey('mensagem', $_SESSION['form_flash']['errors'] ?? []);
    }

    public function testContatoWithValidPayloadStoresSuccessFlash(): void
    {
        $capturedMessage = [];

        $app = TestAppFactory::create([
            'app_name' => 'Clínica Pediátrica',
            'app_slug' => 'pediatria',
            'request_prefix' => 'PED',
            'base_url' => '/pediatria',
            'storage_path' => $this->storagePath,
            'mail_sender' => static function (array $payload) use (&$capturedMessage): array {
                $capturedMessage = $payload;
                return ['ok' => true];
            },
        ]);

        $response = $this->submitContactForm($app, '/pediatria', [
            'nome' => 'Lucio Lemos',
            'telefone' => '(84) 99999-9999',
            'email' => 'lucio@example.com',
            'empresa' => 'Particular',
            'mensagem' => 'Gostaria de agendar uma consulta clínica.',
        ]);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/pediatria/#form-orcamento', $response->getHeaderLine('Location'));
        self::assertSame('success', $_SESSION['form_flash']['status']['type'] ?? null);
        self::assertSame('lead_form_submit_success', $_SESSION['form_flash']['status']['tracking_event'] ?? null);
        self::assertMatchesRegularExpression('/Protocolo: PED-\d{8}-[A-F0-9]{4}/', $_SESSION['form_flash']['status']['message'] ?? '');
        self::assertSame('contato@example.com', $capturedMessage['to'] ?? null);
        self::assertSame('lucio@example.com', $capturedMessage['reply_to'] ?? null);
        self::assertMatchesRegularExpression('/^PED-\d{8}-[A-F0-9]{4}$/', $capturedMessage['request_id'] ?? '');
        self::assertMatchesRegularExpression('/^pediatria_\d{14}_[a-f0-9]{12}$/', $capturedMessage['event_id'] ?? '');
        self::assertStringContainsString('Clínica Pediátrica | Nova solicitação de agendamento', $capturedMessage['subject'] ?? '');
        self::assertStringContainsString('Nova solicitação de agendamento', $capturedMessage['html_body'] ?? '');
        self::assertFileDoesNotExist($this->storagePath . '/logs/contatos-fallback.log');
        self::assertFileExists($this->storagePath . '/logs/lead-events.log');
        $leadLog = file_get_contents($this->storagePath . '/logs/lead-events.log') ?: '';
        self::assertStringContainsString('"contains_personal_data":false', $leadLog);
        self::assertStringContainsString('"email_masked":"l***@example.com"', $leadLog);
        self::assertStringContainsString('"phone_masked":"*******9999"', $leadLog);
        self::assertStringContainsString('"message_length":41', $leadLog);
        self::assertStringNotContainsString('Lucio Lemos', $leadLog);
        self::assertStringNotContainsString('lucio@example.com', $leadLog);
        self::assertStringNotContainsString('Gostaria de agendar uma consulta clínica.', $leadLog);
        self::assertStringNotContainsString('127.0.0.1', $leadLog);
    }

    public function testContatoWithRecaptchaEnabledAcceptsValidToken(): void
    {
        $capturedMessage = [];

        $app = TestAppFactory::create([
            'base_url' => '/medico',
            'storage_path' => $this->storagePath,
            'recaptcha_enabled' => true,
            'recaptcha_site_key' => 'site-key-123',
            'recaptcha_secret_key' => 'secret-key-123',
            'recaptcha_min_score' => 0.5,
            'recaptcha_allowed_hostname' => 'localhost',
            'recaptcha_action' => 'contact_submit',
            'recaptcha_verifier' => static function (array $payload): array {
                return [
                    'success' => $payload['token'] === 'valid-token',
                    'score' => 0.9,
                    'hostname' => 'localhost',
                    'action' => 'contact_submit',
                ];
            },
            'mail_sender' => static function (array $payload) use (&$capturedMessage): array {
                $capturedMessage = $payload;
                return ['ok' => true];
            },
        ]);

        $response = $this->submitContactForm($app, '/medico', [
            'nome' => 'Lucio Lemos',
            'telefone' => '(84) 99999-9999',
            'email' => 'lucio@example.com',
            'empresa' => 'Particular',
            'mensagem' => 'Gostaria de agendar uma consulta clínica.',
            'recaptcha_token' => 'valid-token',
        ]);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('success', $_SESSION['form_flash']['status']['type'] ?? null);
        self::assertSame('lucio@example.com', $capturedMessage['reply_to'] ?? null);
    }

    public function testContatoWithRecaptchaEnabledRejectsMissingToken(): void
    {
        $sent = false;

        $app = TestAppFactory::create([
            'base_url' => '/medico',
            'storage_path' => $this->storagePath,
            'recaptcha_enabled' => true,
            'recaptcha_site_key' => 'site-key-123',
            'recaptcha_secret_key' => 'secret-key-123',
            'recaptcha_allowed_hostname' => 'localhost',
            'mail_sender' => static function () use (&$sent): array {
                $sent = true;
                return ['ok' => true];
            },
        ]);

        $response = $this->submitContactForm($app, '/medico', [
            'nome' => 'Lucio Lemos',
            'telefone' => '(84) 99999-9999',
            'email' => 'lucio@example.com',
            'empresa' => 'Particular',
            'mensagem' => 'Gostaria de agendar uma consulta clínica.',
        ]);

        self::assertSame(302, $response->getStatusCode());
        self::assertFalse($sent);
        self::assertSame('warning', $_SESSION['form_flash']['status']['type'] ?? null);
        self::assertStringContainsString('Não foi possível validar o reCAPTCHA', $_SESSION['form_flash']['status']['message'] ?? '');
        self::assertStringContainsString('reCAPTCHA falhou: token ausente', file_get_contents($this->storagePath . '/logs/lead-events.log') ?: '');
    }

    public function testContatoWithoutConfiguredRecipientCreatesWarningAndFallbackLogs(): void
    {
        $app = TestAppFactory::create([
            'base_url' => '/medico',
            'storage_path' => $this->storagePath,
            'contact_to' => null,
        ]);

        $response = $this->submitContactForm($app, '/medico', [
            'nome' => 'Lucio Lemos',
            'telefone' => '(84) 99999-9999',
            'email' => 'lucio@example.com',
            'empresa' => 'Particular',
            'mensagem' => 'Gostaria de agendar uma consulta clínica.',
        ]);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('warning', $_SESSION['form_flash']['status']['type'] ?? null);
        self::assertFileExists($this->storagePath . '/logs/contatos-fallback.log');
        self::assertFileExists($this->storagePath . '/logs/lead-events.log');
        $fallbackLog = file_get_contents($this->storagePath . '/logs/contatos-fallback.log') ?: '';
        $leadLog = file_get_contents($this->storagePath . '/logs/lead-events.log') ?: '';
        self::assertStringContainsString('CONTACT_TO ausente no .env', $fallbackLog);
        self::assertStringContainsString('"contains_personal_data":true', $fallbackLog);
        self::assertStringContainsString('Lucio Lemos', $fallbackLog);
        self::assertStringContainsString('"result":"failure"', $leadLog);
        self::assertStringContainsString('"contains_personal_data":false', $leadLog);
        self::assertStringNotContainsString('Lucio Lemos', $leadLog);
        self::assertStringNotContainsString('lucio@example.com', $leadLog);
    }

    public function testContatoWithCustomSenderFailureCreatesWarningAndFailureLogs(): void
    {
        $app = TestAppFactory::create([
            'base_url' => '/medico',
            'storage_path' => $this->storagePath,
            'mail_sender' => static function (): array {
                return ['ok' => false, 'error' => 'smtp offline'];
            },
        ]);

        $response = $this->submitContactForm($app, '/medico', [
            'nome' => 'Lucio Lemos',
            'telefone' => '(84) 99999-9999',
            'email' => 'lucio@example.com',
            'empresa' => 'Particular',
            'mensagem' => 'Gostaria de agendar uma consulta clínica.',
        ]);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('warning', $_SESSION['form_flash']['status']['type'] ?? null);
        self::assertStringContainsString('lead_form_submit_failure', $_SESSION['form_flash']['status']['tracking_event'] ?? '');
        $leadLog = file_get_contents($this->storagePath . '/logs/lead-events.log') ?: '';
        $fallbackLog = file_get_contents($this->storagePath . '/logs/contatos-fallback.log') ?: '';
        self::assertStringContainsString('mail_sender falhou: smtp offline', $leadLog);
        self::assertStringContainsString('mail_sender falhou: smtp offline', $fallbackLog);
        self::assertStringNotContainsString('lucio@example.com', $leadLog);
        self::assertStringContainsString('lucio@example.com', $fallbackLog);
    }

    public function testContatoRejectsInvalidCsrfToken(): void
    {
        $app = TestAppFactory::create([
            'base_url' => '/medico',
            'storage_path' => $this->storagePath,
        ]);

        $this->request($app, 'GET', '/medico/');

        $request = (new ServerRequestFactory())->createServerRequest('POST', '/medico/contato')
            ->withParsedBody([
                'csrf_token' => 'token-invalido',
                'website' => '',
                'nome' => 'Lucio Lemos',
                'telefone' => '(84) 99999-9999',
                'email' => 'lucio@example.com',
                'empresa' => 'Particular',
                'mensagem' => 'Gostaria de agendar uma consulta clínica.',
            ]);

        $response = $app->handle($request);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/medico/#form-orcamento', $response->getHeaderLine('Location'));
        self::assertSame('error', $_SESSION['form_flash']['status']['type'] ?? null);
        self::assertStringContainsString('Sua sessão expirou', $_SESSION['form_flash']['status']['message'] ?? '');
    }

    public function testContatoRejectsHoneypotSubmission(): void
    {
        $app = TestAppFactory::create([
            'base_url' => '/medico',
            'storage_path' => $this->storagePath,
        ]);

        $response = $this->submitContactForm($app, '/medico', [
            'nome' => 'Lucio Lemos',
            'telefone' => '(84) 99999-9999',
            'email' => 'lucio@example.com',
            'empresa' => 'Particular',
            'mensagem' => 'Gostaria de agendar uma consulta clínica.',
            'website' => 'https://bot.example.com',
        ]);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/medico/#form-orcamento', $response->getHeaderLine('Location'));
        self::assertSame('warning', $_SESSION['form_flash']['status']['type'] ?? null);
        self::assertStringContainsString('Não foi possível processar o envio', $_SESSION['form_flash']['status']['message'] ?? '');
        self::assertFileDoesNotExist($this->storagePath . '/logs/lead-events.log');
    }

    public function testContatoAppliesRateLimitAfterConfiguredNumberOfAttempts(): void
    {
        $app = TestAppFactory::create([
            'base_url' => '/medico',
            'storage_path' => $this->storagePath,
            'rate_limit_max_attempts' => 2,
            'rate_limit_window_seconds' => 3600,
        ]);

        $first = $this->submitContactForm($app, '/medico', [
            'nome' => '',
            'telefone' => '',
            'email' => 'email-invalido',
            'empresa' => '',
            'mensagem' => '',
        ]);

        $second = $this->submitContactForm($app, '/medico', [
            'nome' => '',
            'telefone' => '',
            'email' => 'email-invalido',
            'empresa' => '',
            'mensagem' => '',
        ]);

        $third = $this->submitContactForm($app, '/medico', [
            'nome' => '',
            'telefone' => '',
            'email' => 'email-invalido',
            'empresa' => '',
            'mensagem' => '',
        ]);

        self::assertSame(302, $first->getStatusCode());
        self::assertSame(302, $second->getStatusCode());
        self::assertSame(302, $third->getStatusCode());
        self::assertSame('warning', $_SESSION['form_flash']['status']['type'] ?? null);
        self::assertStringContainsString('Recebemos muitas tentativas seguidas', $_SESSION['form_flash']['status']['message'] ?? '');
    }

    public function testContatoRedirectsToRootAnchorWhenBasePathIsEmpty(): void
    {
        $app = TestAppFactory::create([
            'base_url' => '',
            'storage_path' => $this->storagePath,
        ]);

        $response = $this->submitContactForm($app, '', [
            'nome' => '',
            'telefone' => '',
            'email' => 'email-invalido',
            'empresa' => '',
            'mensagem' => '',
        ]);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/#form-orcamento', $response->getHeaderLine('Location'));
    }

    private function submitContactForm(object $app, string $basePath, array $data)
    {
        $path = $basePath === '' ? '/' : $basePath . '/';
        $this->request($app, 'GET', $path);

        $payload = array_merge([
            'csrf_token' => $_SESSION['contact_csrf_token'] ?? '',
            'website' => '',
        ], $data);

        $submitPath = $basePath === '' ? '/contato' : $basePath . '/contato';
        $request = (new ServerRequestFactory())->createServerRequest('POST', $submitPath)
            ->withParsedBody($payload);

        return $app->handle($request);
    }

    private function request(object $app, string $method, string $uri)
    {
        $request = (new ServerRequestFactory())->createServerRequest($method, $uri);
        return $app->handle($request);
    }

    private function extractStructuredData(string $html): array
    {
        $matched = preg_match('/<script type="application\\/ld\\+json">(.*?)<\\/script>/s', $html, $matches);
        self::assertSame(1, $matched, 'JSON-LD script tag was not found.');
        $decoded = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . '/' . $item;
            if (is_dir($fullPath)) {
                $this->removeDirectory($fullPath);
                continue;
            }

            @unlink($fullPath);
        }

        @rmdir($path);
    }
}
