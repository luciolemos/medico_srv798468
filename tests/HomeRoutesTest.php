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
        $this->storagePath = sys_get_temp_dir() . '/natalcode-tests-' . bin2hex(random_bytes(4));
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
            'page_title' => 'NatalCode | Teste de Home',
            'base_url' => '/natalcode',
        ]);

        $response = $this->request($app, 'GET', '/natalcode/');
        $html = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('<title>NatalCode | Teste de Home</title>', $html);
        self::assertStringContainsString('/natalcode/assets/css/landing.css', $html);
        self::assertMatchesRegularExpression('/name="csrf_token" value="[a-f0-9]{64}"/', $html);
    }

    public function testHomeRendersCorrectAssetPathsWhenBasePathIsEmpty(): void
    {
        $app = TestAppFactory::create([
            'page_title' => 'NatalCode | Root',
            'base_url' => '',
        ]);

        $response = $this->request($app, 'GET', '/');
        $html = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('<title>NatalCode | Root</title>', $html);
        self::assertStringContainsString('href="/assets/css/landing.css"', $html);
        self::assertStringContainsString('src="/assets/img/img_default.webp"', $html);
        self::assertStringNotContainsString('//assets/', $html);
    }

    public function testHomeFallsBackToBluePaletteWhenQueryPaletteIsInvalid(): void
    {
        $app = TestAppFactory::create([
            'palette' => 'emerald',
            'base_url' => '/natalcode',
        ]);

        $response = $this->request($app, 'GET', '/natalcode/?palette=invalida');
        $html = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('/natalcode/assets/css/palettes/blue.css', $html);
    }

    public function testHomeRendersGrowthCopyAndPaletteStateFromQueryString(): void
    {
        $app = TestAppFactory::create([
            'palette' => 'blue',
            'base_url' => '/natalcode',
        ]);

        $response = $this->request($app, 'GET', '/natalcode/?copy=growth&palette=red');
        $html = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Lance sua landing em', $html);
        self::assertStringContainsString('Copy: Growth', $html);
        self::assertStringContainsString('/natalcode/assets/css/palettes/red.css', $html);
    }

    public function testHomeConsumesFlashStatusAndClearsItFromSession(): void
    {
        $_SESSION['form_flash'] = [
            'status' => [
                'type' => 'success',
                'message' => 'Solicitacao recebida com sucesso.',
                'tracking_event' => 'lead_form_submit_success',
                'event_id' => 'evt_123',
                'request_id' => 'NAT-20260404-ABCD',
            ],
            'data' => [
                'nome' => 'Lucio',
                'telefone' => '(84) 99999-9999',
                'email' => 'lucio@example.com',
                'empresa' => 'NatalCode',
                'mensagem' => 'Quero uma landing.',
            ],
            'errors' => [],
        ];

        $app = TestAppFactory::create([
            'base_url' => '/natalcode',
            'storage_path' => $this->storagePath,
        ]);

        $response = $this->request($app, 'GET', '/natalcode/');
        $html = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Solicitacao recebida com sucesso.', $html);
        self::assertStringContainsString('data-form-result-event="lead_form_submit_success"', $html);
        self::assertArrayNotHasKey('form_flash', $_SESSION);
    }

    public function testHomeRendersCoreSectionsAsSmokeCoverage(): void
    {
        $app = TestAppFactory::create([
            'base_url' => '/natalcode',
        ]);

        $response = $this->request($app, 'GET', '/natalcode/');
        $html = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('id="features"', $html);
        self::assertStringContainsString('id="projects"', $html);
        self::assertStringContainsString('id="depoimentos"', $html);
        self::assertStringContainsString('id="how"', $html);
        self::assertStringContainsString('id="docs"', $html);
        self::assertStringContainsString('id="form-orcamento"', $html);
        self::assertStringContainsString('id="faq"', $html);
    }

    public function testContatoWithInvalidPayloadRedirectsBackToFormAndStoresErrors(): void
    {
        $app = TestAppFactory::create([
            'base_url' => '/natalcode',
            'storage_path' => $this->storagePath,
        ]);

        $response = $this->submitContactForm($app, '/natalcode', [
            'nome' => '',
            'telefone' => '',
            'email' => 'email-invalido',
            'empresa' => '',
            'mensagem' => '',
        ]);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/natalcode/#form-orcamento', $response->getHeaderLine('Location'));
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
            'base_url' => '/natalcode',
            'storage_path' => $this->storagePath,
            'mail_sender' => static function (array $payload) use (&$capturedMessage): array {
                $capturedMessage = $payload;
                return ['ok' => true];
            },
        ]);

        $response = $this->submitContactForm($app, '/natalcode', [
            'nome' => 'Lucio Lemos',
            'telefone' => '(84) 99999-9999',
            'email' => 'lucio@example.com',
            'empresa' => 'NatalCode',
            'mensagem' => 'Quero publicar uma nova landing institucional.',
        ]);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/natalcode/#form-orcamento', $response->getHeaderLine('Location'));
        self::assertSame('success', $_SESSION['form_flash']['status']['type'] ?? null);
        self::assertSame('lead_form_submit_success', $_SESSION['form_flash']['status']['tracking_event'] ?? null);
        self::assertStringContainsString('Recebemos sua solicitação. Protocolo:', $_SESSION['form_flash']['status']['message'] ?? '');
        self::assertSame('contato@example.com', $capturedMessage['to'] ?? null);
        self::assertSame('lucio@example.com', $capturedMessage['reply_to'] ?? null);
        self::assertStringContainsString('Nova solicitação comercial', $capturedMessage['html_body'] ?? '');
        self::assertFileDoesNotExist($this->storagePath . '/logs/contatos-fallback.log');
        self::assertFileExists($this->storagePath . '/logs/lead-events.log');
    }

    public function testContatoWithoutConfiguredRecipientCreatesWarningAndFallbackLogs(): void
    {
        $app = TestAppFactory::create([
            'base_url' => '/natalcode',
            'storage_path' => $this->storagePath,
            'contact_to' => null,
        ]);

        $response = $this->submitContactForm($app, '/natalcode', [
            'nome' => 'Lucio Lemos',
            'telefone' => '(84) 99999-9999',
            'email' => 'lucio@example.com',
            'empresa' => 'NatalCode',
            'mensagem' => 'Quero publicar uma nova landing institucional.',
        ]);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('warning', $_SESSION['form_flash']['status']['type'] ?? null);
        self::assertFileExists($this->storagePath . '/logs/contatos-fallback.log');
        self::assertFileExists($this->storagePath . '/logs/lead-events.log');
        self::assertStringContainsString('CONTACT_TO ausente no .env', file_get_contents($this->storagePath . '/logs/contatos-fallback.log') ?: '');
        self::assertStringContainsString('"result":"failure"', file_get_contents($this->storagePath . '/logs/lead-events.log') ?: '');
    }

    public function testContatoWithCustomSenderFailureCreatesWarningAndFailureLogs(): void
    {
        $app = TestAppFactory::create([
            'base_url' => '/natalcode',
            'storage_path' => $this->storagePath,
            'mail_sender' => static function (): array {
                return ['ok' => false, 'error' => 'smtp offline'];
            },
        ]);

        $response = $this->submitContactForm($app, '/natalcode', [
            'nome' => 'Lucio Lemos',
            'telefone' => '(84) 99999-9999',
            'email' => 'lucio@example.com',
            'empresa' => 'NatalCode',
            'mensagem' => 'Quero publicar uma nova landing institucional.',
        ]);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('warning', $_SESSION['form_flash']['status']['type'] ?? null);
        self::assertStringContainsString('lead_form_submit_failure', $_SESSION['form_flash']['status']['tracking_event'] ?? '');
        self::assertStringContainsString('mail_sender falhou: smtp offline', file_get_contents($this->storagePath . '/logs/lead-events.log') ?: '');
        self::assertStringContainsString('mail_sender falhou: smtp offline', file_get_contents($this->storagePath . '/logs/contatos-fallback.log') ?: '');
    }

    public function testContatoRejectsInvalidCsrfToken(): void
    {
        $app = TestAppFactory::create([
            'base_url' => '/natalcode',
            'storage_path' => $this->storagePath,
        ]);

        $this->request($app, 'GET', '/natalcode/');

        $request = (new ServerRequestFactory())->createServerRequest('POST', '/natalcode/contato')
            ->withParsedBody([
                'csrf_token' => 'token-invalido',
                'website' => '',
                'nome' => 'Lucio Lemos',
                'telefone' => '(84) 99999-9999',
                'email' => 'lucio@example.com',
                'empresa' => 'NatalCode',
                'mensagem' => 'Quero publicar uma nova landing institucional.',
            ]);

        $response = $app->handle($request);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/natalcode/#form-orcamento', $response->getHeaderLine('Location'));
        self::assertSame('error', $_SESSION['form_flash']['status']['type'] ?? null);
        self::assertStringContainsString('Sua sessao expirou', $_SESSION['form_flash']['status']['message'] ?? '');
    }

    public function testContatoRejectsHoneypotSubmission(): void
    {
        $app = TestAppFactory::create([
            'base_url' => '/natalcode',
            'storage_path' => $this->storagePath,
        ]);

        $response = $this->submitContactForm($app, '/natalcode', [
            'nome' => 'Lucio Lemos',
            'telefone' => '(84) 99999-9999',
            'email' => 'lucio@example.com',
            'empresa' => 'NatalCode',
            'mensagem' => 'Quero publicar uma nova landing institucional.',
            'website' => 'https://bot.example.com',
        ]);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/natalcode/#form-orcamento', $response->getHeaderLine('Location'));
        self::assertSame('warning', $_SESSION['form_flash']['status']['type'] ?? null);
        self::assertStringContainsString('Nao foi possivel processar o envio', $_SESSION['form_flash']['status']['message'] ?? '');
        self::assertFileDoesNotExist($this->storagePath . '/logs/lead-events.log');
    }

    public function testContatoAppliesRateLimitAfterConfiguredNumberOfAttempts(): void
    {
        $app = TestAppFactory::create([
            'base_url' => '/natalcode',
            'storage_path' => $this->storagePath,
            'rate_limit_max_attempts' => 2,
            'rate_limit_window_seconds' => 3600,
        ]);

        $first = $this->submitContactForm($app, '/natalcode', [
            'nome' => '',
            'telefone' => '',
            'email' => 'email-invalido',
            'empresa' => '',
            'mensagem' => '',
        ]);

        $second = $this->submitContactForm($app, '/natalcode', [
            'nome' => '',
            'telefone' => '',
            'email' => 'email-invalido',
            'empresa' => '',
            'mensagem' => '',
        ]);

        $third = $this->submitContactForm($app, '/natalcode', [
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
