<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class CreateLandingScriptTest extends TestCase
{
    private string $rootPath;

    protected function setUp(): void
    {
        $this->rootPath = sys_get_temp_dir() . '/create-landing-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->rootPath);
    }

    public function testCreateLandingListsPresetSlugs(): void
    {
        $script = dirname(__DIR__) . '/scripts/create-landing.sh';
        $command = 'bash ' . escapeshellarg($script) . ' --list-presets';

        exec($command, $output, $exitCode);
        $text = implode("\n", $output);

        self::assertSame(0, $exitCode, $text);
        self::assertStringContainsString('slug', $text);
        self::assertStringContainsString('pediatria', $text);
        self::assertStringContainsString('odontologia', $text);
        self::assertStringContainsString('veterinaria', $text);
        self::assertStringContainsString('premium', $text);
        self::assertStringContainsString('VeterinaryCare', $text);
    }

    public function testCreateLandingUsesSlugContentAndPrunesOtherNiches(): void
    {
        $projectRoot = dirname(__DIR__);
        $target = $this->rootPath . '/odontologia';
        $script = $projectRoot . '/scripts/create-landing.sh';

        $command = 'bash ' . escapeshellarg($script)
            . ' odontologia --target ' . escapeshellarg($target);

        exec($command, $output, $exitCode);

        self::assertSame(0, $exitCode, implode("\n", $output));
        $createOutput = implode("\n", $output);
        self::assertStringContainsString('composer install --no-dev --optimize-autoloader', $createOutput);
        self::assertStringContainsString('chown -R www-data:www-data', $createOutput);
        self::assertStringContainsString('audit-apache-subdir-vhost.sh', $createOutput);
        self::assertFileExists($target . '/config/content/landing.php');
        self::assertFileExists($target . '/config/content/odontologia.php');
        self::assertFileDoesNotExist($target . '/config/content/pediatria.php');
        self::assertFileDoesNotExist($target . '/config/content/veterinaria.php');
        self::assertStringContainsString('APP_CONTENT_FILE="odontologia"', (string) file_get_contents($target . '/.env'));
        self::assertStringContainsString('APP_SLUG="odontologia"', (string) file_get_contents($target . '/.env'));
        self::assertStringContainsString('APP_WHATSAPP_NUMBER="5584996360721"', (string) file_get_contents($target . '/.env'));
        self::assertStringContainsString('APP_WHATSAPP_MESSAGE="Oi! Quero conversar sobre o projeto de uma landing page com a NatalCode."', (string) file_get_contents($target . '/.env'));
        self::assertStringContainsString('Desenvolvido por <a href="https://natalcode.com.br/" target="_blank" rel="noopener noreferrer">NatalCode</a> - Soluções Digitais', (string) file_get_contents($target . '/config/content/landing.php'));
        self::assertStringNotContainsString('footer_content.credit|raw', (string) file_get_contents($target . '/views/partials/footer.twig'));
        self::assertStringContainsString('footer_content.credit }}</span>', (string) file_get_contents($target . '/views/partials/footer.twig'));
        self::assertStringContainsString("'href' => '#cta'", (string) file_get_contents($target . '/config/content/landing.php'));
        self::assertStringNotContainsString('data-cta-id="nav_schedule"', (string) file_get_contents($target . '/views/partials/navbar.twig'));
        self::assertStringContainsString('location-map-banner', (string) file_get_contents($target . '/views/pages/home.twig'));
        self::assertStringContainsString('location-map-banner', (string) file_get_contents($target . '/public/assets/css/landing.css'));
        self::assertStringContainsString("'location' => [", (string) file_get_contents($target . '/config/content/landing.php'));
        self::assertStringContainsString("'map_embed_url' => 'https://maps.google.com/maps?", (string) file_get_contents($target . '/config/content/landing.php'));
        self::assertStringContainsString('https://maps.google.com/', (string) file_get_contents($target . '/src/Middleware/SecurityHeadersMiddleware.php'));
        self::assertStringContainsString('https://www.google.com/maps/', (string) file_get_contents($target . '/src/Middleware/SecurityHeadersMiddleware.php'));
        self::assertFileExists($target . '/public/assets/img/hero/odontologia-640.webp');
        self::assertFileExists($target . '/public/assets/img/hero/odontologia-mobile-640.webp');
        self::assertFileExists($target . '/public/assets/img/social/odontologia-og.jpg');
        self::assertFileExists($target . '/public/assets/img/odontologia-mark.svg');
        self::assertFileDoesNotExist($target . '/public/assets/img/hero/medico-640.webp');
        self::assertFileDoesNotExist($target . '/public/assets/img/medico-mark.svg');
        self::assertFileDoesNotExist($target . '/public/assets/img/clinic-mark.svg');
        self::assertFileDoesNotExist($target . '/public/assets/img/hero/pediatria-640.webp');
        self::assertFileDoesNotExist($target . '/public/assets/img/hero/veterinaria-640.webp');
        self::assertStringContainsString('assets/img/odontologia-mark.svg', (string) file_get_contents($target . '/config/content/odontologia.php'));
        self::assertStringNotContainsString('assets/img/clinic-mark.svg', (string) file_get_contents($target . '/views/base.twig'));
        self::assertStringNotContainsString('assets/img/hero/medico-640.webp', (string) file_get_contents($target . '/views/pages/home.twig'));
        $landingCss = (string) file_get_contents($target . '/public/assets/css/landing.css');
        self::assertStringContainsString('min-width: 2.1rem;', $landingCss);
        self::assertStringContainsString('aspect-ratio: 1 / 1;', $landingCss);
        self::assertStringContainsString('border-radius: 999px;', $landingCss);
        self::assertStringNotContainsString('width: 28px;', $landingCss);

        $validateCommand = escapeshellarg(PHP_BINARY)
            . ' ' . escapeshellarg($target . '/scripts/validate-landing-content.php')
            . ' --project-root ' . escapeshellarg($target)
            . ' --content odontologia'
            . ' --slug odontologia'
            . ' --strict';

        exec($validateCommand, $validateOutput, $validateExitCode);

        self::assertSame(0, $validateExitCode, implode("\n", $validateOutput));
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
