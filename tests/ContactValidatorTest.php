<?php

declare(strict_types=1);

namespace Tests;

use App\Contact\ContactValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ContactValidator.
 */
final class ContactValidatorTest extends TestCase
{
    private ContactValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ContactValidator();
    }

    // ------------------------------------------------------------------
    // Valid submissions
    // ------------------------------------------------------------------

    public function testValidDataReturnsNoErrors(): void
    {
        $errors = $this->validator->validate($this->validData());

        self::assertSame([], $errors);
    }

    // ------------------------------------------------------------------
    // Required fields
    // ------------------------------------------------------------------

    public function testEmptyNomeProducesError(): void
    {
        $errors = $this->validator->validate($this->validData(['nome' => '']));

        self::assertArrayHasKey('nome', $errors);
        self::assertCount(1, $errors);
    }

    public function testEmptyTelefoneProducesError(): void
    {
        $errors = $this->validator->validate($this->validData(['telefone' => '']));

        self::assertArrayHasKey('telefone', $errors);
        self::assertCount(1, $errors);
    }

    public function testEmptyMensagemProducesError(): void
    {
        $errors = $this->validator->validate($this->validData(['mensagem' => '']));

        self::assertArrayHasKey('mensagem', $errors);
        self::assertCount(1, $errors);
    }

    // ------------------------------------------------------------------
    // Email validation
    // ------------------------------------------------------------------

    public function testEmptyEmailProducesError(): void
    {
        $errors = $this->validator->validate($this->validData(['email' => '']));

        self::assertArrayHasKey('email', $errors);
    }

    /** @param non-empty-string $invalidEmail */
    #[DataProvider('invalidEmailProvider')]
    public function testInvalidEmailProducesError(string $invalidEmail): void
    {
        $errors = $this->validator->validate($this->validData(['email' => $invalidEmail]));

        self::assertArrayHasKey('email', $errors);
    }

    /** @return array<string, array{0: non-empty-string}> */
    public static function invalidEmailProvider(): array
    {
        return [
            'missing at-sign'   => ['not-an-email'],
            'missing domain'    => ['user@'],
            'missing local'     => ['@example.com'],
            'double dots'       => ['user..name@example.com'],
            'spaces'            => ['user name@example.com'],
            'bare ip'           => ['user@256.256.256.256'],
        ];
    }

    #[DataProvider('validEmailProvider')]
    public function testValidEmailProducesNoEmailError(string $validEmail): void
    {
        $errors = $this->validator->validate($this->validData(['email' => $validEmail]));

        self::assertArrayNotHasKey('email', $errors);
    }

    /** @return array<string, array{0: string}> */
    public static function validEmailProvider(): array
    {
        return [
            'simple'           => ['user@example.com'],
            'subdomain'        => ['user@mail.example.com'],
            'plus addressing'  => ['user+tag@example.com'],
            'numeric domain'   => ['user@example123.com'],
        ];
    }

    // ------------------------------------------------------------------
    // Multiple errors at once
    // ------------------------------------------------------------------

    public function testAllFieldsEmptyReturnsAllErrors(): void
    {
        $errors = $this->validator->validate([
            'nome'     => '',
            'telefone' => '',
            'email'    => '',
            'empresa'  => '',
            'mensagem' => '',
        ]);

        self::assertArrayHasKey('nome', $errors);
        self::assertArrayHasKey('telefone', $errors);
        self::assertArrayHasKey('email', $errors);
        self::assertArrayHasKey('mensagem', $errors);
        self::assertCount(4, $errors);
    }

    public function testEmpresaIsOptional(): void
    {
        // empresa (company/notes) is not required — empty value must not produce an error
        $errors = $this->validator->validate($this->validData(['empresa' => '']));

        self::assertSame([], $errors);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /** @param array<string, string> $overrides */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'nome'     => 'Maria Silva',
            'telefone' => '(84) 99999-9999',
            'email'    => 'maria@example.com',
            'empresa'  => 'Clínica X',
            'mensagem' => 'Gostaria de agendar uma consulta.',
        ], $overrides);
    }
}
