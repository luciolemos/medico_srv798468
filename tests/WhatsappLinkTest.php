<?php

declare(strict_types=1);

namespace Tests;

use App\Core\WhatsappLink;
use PHPUnit\Framework\TestCase;

final class WhatsappLinkTest extends TestCase
{
    public function testBuildsLinkFromNumberAndMessage(): void
    {
        $url = WhatsappLink::fromConfig([
            'app_whatsapp_number' => '+55 (84) 99636-0721',
            'app_whatsapp_message' => 'Oi! Quero conversar sobre o projeto de uma landing page com a NatalCode.',
            'whatsapp_url' => 'https://wa.me/0000000000',
        ]);

        self::assertSame(
            'https://wa.me/5584996360721?text=Oi%21%20Quero%20conversar%20sobre%20o%20projeto%20de%20uma%20landing%20page%20com%20a%20NatalCode.',
            $url
        );
    }

    public function testFallsBackToLegacyUrl(): void
    {
        self::assertSame('https://wa.me/5584999031906', WhatsappLink::fromConfig([
            'whatsapp_url' => 'https://wa.me/5584999031906',
        ]));
    }
}
