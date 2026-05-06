<?php

declare(strict_types=1);

namespace App\Core;

final class WhatsappLink
{
    public static function fromConfig(array $config): string
    {
        $number = preg_replace('/\D+/', '', (string) ($config['app_whatsapp_number'] ?? ''));
        $message = trim((string) ($config['app_whatsapp_message'] ?? ''));

        if ($number !== '') {
            $url = 'https://wa.me/' . $number;
            if ($message !== '') {
                $url .= '?text=' . rawurlencode($message);
            }

            return $url;
        }

        $legacyUrl = trim((string) ($config['whatsapp_url'] ?? ''));
        return $legacyUrl !== '' ? $legacyUrl : '#';
    }
}
