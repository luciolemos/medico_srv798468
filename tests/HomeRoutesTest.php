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

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/natalcode/');
        $response = $app->handle($request);
        $html = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('<title>NatalCode | Teste de Home</title>', $html);
        self::assertStringContainsString('/natalcode/assets/css/landing.css', $html);
    }

    public function testHomeRendersCorrectAssetPathsWhenBasePathIsEmpty(): void
    {
        $app = TestAppFactory::create([
            'page_title' => 'NatalCode | Root',
            'base_url' => '',
        ]);

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $response = $app->handle($request);
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

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/natalcode/?palette=invalida');
        $response = $app->handle($request);
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

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/natalcode/?copy=growth&palette=red');
        $response = $app->handle($request);
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

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/natalcode/');
        $response = $app->handle($request);
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

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/natalcode/');
        $response = $app->handle($request);
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
        ]);

        $request = (new ServerRequestFactory())->createServerRequest('POST', '/natalcode/contato')
            ->withParsedBody([
                'nome' => '',
                'telefone' => '',
                'email' => 'email-invalido',
                'empresa' => '',
                'mensagem' => '',
            ]);

        $response = $app->handle($request);

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

        $request = (new ServerRequestFactory())->createServerRequest('POST', '/natalcode/contato')
            ->withParsedBody([
                'nome' => 'Lucio Lemos',
                'telefone' => '(84) 99999-9999',
                'email' => 'lucio@example.com',
                'empresa' => 'NatalCode',
                'mensagem' => 'Quero publicar uma nova landing institucional.',
            ]);

        $response = $app->handle($request);

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

        $request = (new ServerRequestFactory())->createServerRequest('POST', '/natalcode/contato')
            ->withParsedBody([
                'nome' => 'Lucio Lemos',
                'telefone' => '(84) 99999-9999',
                'email' => 'lucio@example.com',
                'empresa' => 'NatalCode',
                'mensagem' => 'Quero publicar uma nova landing institucional.',
            ]);

        $response = $app->handle($request);

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

        $request = (new ServerRequestFactory())->createServerRequest('POST', '/natalcode/contato')
            ->withParsedBody([
                'nome' => 'Lucio Lemos',
                'telefone' => '(84) 99999-9999',
                'email' => 'lucio@example.com',
                'empresa' => 'NatalCode',
                'mensagem' => 'Quero publicar uma nova landing institucional.',
            ]);

        $response = $app->handle($request);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('warning', $_SESSION['form_flash']['status']['type'] ?? null);
        self::assertStringContainsString('lead_form_submit_failure', $_SESSION['form_flash']['status']['tracking_event'] ?? '');
        self::assertStringContainsString('mail_sender falhou: smtp offline', file_get_contents($this->storagePath . '/logs/lead-events.log') ?: '');
        self::assertStringContainsString('mail_sender falhou: smtp offline', file_get_contents($this->storagePath . '/logs/contatos-fallback.log') ?: '');
    }

    public function testContatoRedirectsToRootAnchorWhenBasePathIsEmpty(): void
    {
        $app = TestAppFactory::create([
            'base_url' => '',
            'storage_path' => $this->storagePath,
        ]);

        $request = (new ServerRequestFactory())->createServerRequest('POST', '/contato')
            ->withParsedBody([
                'nome' => '',
                'telefone' => '',
                'email' => 'email-invalido',
                'empresa' => '',
                'mensagem' => '',
            ]);

        $response = $app->handle($request);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/#form-orcamento', $response->getHeaderLine('Location'));
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
