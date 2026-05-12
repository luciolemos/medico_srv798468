<?php

declare(strict_types=1);

namespace App\Contact;

/**
 * Validates contact form field data.
 *
 * All fields are expected to already be trimmed strings.
 */
final class ContactValidator
{
    private const NAME_MIN_LEN = 3;
    private const NAME_MAX_LEN = 120;
    private const PHONE_MIN_DIGITS = 10;
    private const PHONE_MAX_DIGITS = 15;
    private const EMAIL_MAX_LEN = 160;
    private const MESSAGE_MIN_LEN = 10;
    private const MESSAGE_MAX_LEN = 2000;
    private const COMPANY_MAX_LEN = 120;

    private function strLen(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }

    /**
     * Returns an associative array of field names that failed validation.
     * An empty array means the data is valid.
     *
     * @param array{nome: string, telefone: string, email: string, empresa: string, mensagem: string} $data
     * @return array<string, true>
     */
    public function validate(array $data): array
    {
        $errors = [];
        $name = $data['nome'];
        $email = $data['email'];
        $message = $data['mensagem'];
        $company = $data['empresa'];
        $phoneDigits = preg_replace('/\D+/', '', $data['telefone']) ?? '';

        if (
            $name === ''
            || $this->strLen($name) < self::NAME_MIN_LEN
            || $this->strLen($name) > self::NAME_MAX_LEN
        ) {
            $errors['nome'] = true;
        }

        if (
            $phoneDigits === ''
            || strlen($phoneDigits) < self::PHONE_MIN_DIGITS
            || strlen($phoneDigits) > self::PHONE_MAX_DIGITS
        ) {
            $errors['telefone'] = true;
        }

        if (
            $message === ''
            || $this->strLen($message) < self::MESSAGE_MIN_LEN
            || $this->strLen($message) > self::MESSAGE_MAX_LEN
        ) {
            $errors['mensagem'] = true;
        }

        if (
            $email === ''
            || $this->strLen($email) > self::EMAIL_MAX_LEN
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            $errors['email'] = true;
        }

        if ($company !== '' && $this->strLen($company) > self::COMPANY_MAX_LEN) {
            $errors['empresa'] = true;
        }

        return $errors;
    }
}
