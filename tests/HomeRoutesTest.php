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
}
