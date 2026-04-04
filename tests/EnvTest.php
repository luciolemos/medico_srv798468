<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Env;
use PHPUnit\Framework\TestCase;

final class EnvTest extends TestCase
{
    private string $fixturePath;
    private string $missingFixturePath;

    protected function setUp(): void
    {
        $this->fixturePath = sys_get_temp_dir() . '/natalcode-env-test.env';
        $this->missingFixturePath = sys_get_temp_dir() . '/natalcode-env-missing.env';
        file_put_contents($this->fixturePath, <<<ENV
APP_NAME="NatalCode Test"
APP_BASE="/natalcode"
APP_PAGE_TITLE="NatalCode | Teste"
ENV);

        @unlink($this->missingFixturePath);

        unset($_ENV['APP_NAME'], $_ENV['APP_BASE'], $_ENV['APP_PAGE_TITLE'], $_ENV['APP_THEME'], $_ENV['APP_TAGLINE'], $_ENV['EMPTY_VALUE']);
        putenv('APP_NAME');
        putenv('APP_BASE');
        putenv('APP_PAGE_TITLE');
        putenv('APP_THEME');
        putenv('APP_TAGLINE');
        putenv('EMPTY_VALUE');
    }

    protected function tearDown(): void
    {
        @unlink($this->fixturePath);
        @unlink($this->missingFixturePath);
        unset($_ENV['APP_NAME'], $_ENV['APP_BASE'], $_ENV['APP_PAGE_TITLE'], $_ENV['APP_THEME'], $_ENV['APP_TAGLINE'], $_ENV['EMPTY_VALUE']);
        putenv('APP_NAME');
        putenv('APP_BASE');
        putenv('APP_PAGE_TITLE');
        putenv('APP_THEME');
        putenv('APP_TAGLINE');
        putenv('EMPTY_VALUE');
    }

    public function testLoadsEnvValuesFromFile(): void
    {
        Env::load($this->fixturePath);

        self::assertSame('NatalCode Test', $_ENV['APP_NAME'] ?? null);
        self::assertSame('/natalcode', $_ENV['APP_BASE'] ?? null);
        self::assertSame('NatalCode | Teste', $_ENV['APP_PAGE_TITLE'] ?? null);
    }

    public function testDoesNotOverrideExistingValues(): void
    {
        $_ENV['APP_NAME'] = 'Valor Existente';
        putenv('APP_NAME=Valor Existente');

        Env::load($this->fixturePath);

        self::assertSame('Valor Existente', $_ENV['APP_NAME']);
    }

    public function testIgnoresMissingEnvFile(): void
    {
        Env::load($this->missingFixturePath);

        self::assertArrayNotHasKey('APP_NAME', $_ENV);
        self::assertArrayNotHasKey('APP_BASE', $_ENV);
    }

    public function testSkipsCommentLinesAndTrimsQuotesAndWhitespace(): void
    {
        file_put_contents($this->fixturePath, <<<ENV
# comentario inicial
APP_THEME = " midnight "
APP_TAGLINE='PHP + Slim + Twig'
EMPTY_VALUE=

ENV);

        Env::load($this->fixturePath);

        self::assertSame('midnight', $_ENV['APP_THEME'] ?? null);
        self::assertSame("'PHP + Slim + Twig'", $_ENV['APP_TAGLINE'] ?? null);
        self::assertSame('', $_ENV['EMPTY_VALUE'] ?? null);
    }
}
