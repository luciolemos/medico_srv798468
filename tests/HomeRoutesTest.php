<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Tests\Support\TestAppFactory;

final class HomeRoutesTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
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
        ]);

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/natalcode/');
        $response = $app->handle($request);
        $html = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Solicitacao recebida com sucesso.', $html);
        self::assertStringContainsString('data-form-result-event="lead_form_submit_success"', $html);
        self::assertArrayNotHasKey('form_flash', $_SESSION);
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
    }
}
