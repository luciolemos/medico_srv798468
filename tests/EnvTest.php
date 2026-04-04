<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Env;
use PHPUnit\Framework\TestCase;

final class EnvTest extends TestCase
{
    private string $fixturePath;

    protected function setUp(): void
    {
        $this->fixturePath = sys_get_temp_dir() . '/natalcode-env-test.env';
        file_put_contents($this->fixturePath, <<<ENV
APP_NAME="NatalCode Test"
APP_BASE="/natalcode"
APP_PAGE_TITLE="NatalCode | Teste"
ENV);

        unset($_ENV['APP_NAME'], $_ENV['APP_BASE'], $_ENV['APP_PAGE_TITLE']);
        putenv('APP_NAME');
        putenv('APP_BASE');
        putenv('APP_PAGE_TITLE');
    }

    protected function tearDown(): void
    {
        @unlink($this->fixturePath);
        unset($_ENV['APP_NAME'], $_ENV['APP_BASE'], $_ENV['APP_PAGE_TITLE']);
        putenv('APP_NAME');
        putenv('APP_BASE');
        putenv('APP_PAGE_TITLE');
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
}
