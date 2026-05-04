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

        if ($data['nome'] === '') {
            $errors['nome'] = true;
        }

        if ($data['telefone'] === '') {
            $errors['telefone'] = true;
        }

        if ($data['mensagem'] === '') {
            $errors['mensagem'] = true;
        }

        if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = true;
        }

        return $errors;
    }
}
