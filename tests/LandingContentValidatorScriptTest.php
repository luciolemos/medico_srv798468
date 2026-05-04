<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LandingContentValidatorScriptTest extends TestCase
{
    private string $rootPath;

    protected function setUp(): void
    {
        $this->rootPath = sys_get_temp_dir() . '/landing-validator-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->rootPath);
    }

    public function testValidatorAcceptsCurrentLandingContent(): void
    {
        $script = dirname(__DIR__) . '/scripts/validate-landing-content.php';
        $command = escapeshellarg(PHP_BINARY)
            . ' ' . escapeshellarg($script)
            . ' --project-root ' . escapeshellarg(dirname(__DIR__))
            . ' --content landing'
            . ' --slug medico';

        exec($command, $output, $exitCode);

        self::assertSame(0, $exitCode, implode("\n", $output));
        self::assertStringContainsString('typography.profile=clinical', implode("\n", $output));
        self::assertStringContainsString('failures: 0', implode("\n", $output));
    }

    #[DataProvider('mergedNicheContentProvider')]
    public function testValidatorAcceptsMergedNicheContent(string $contentName, string $expectedProfile): void
    {
        $script = dirname(__DIR__) . '/scripts/validate-landing-content.php';
        $command = escapeshellarg(PHP_BINARY)
            . ' ' . escapeshellarg($script)
            . ' --project-root ' . escapeshellarg(dirname(__DIR__))
            . ' --content ' . escapeshellarg($contentName)
            . ' --slug ' . escapeshellarg($contentName);

        exec($command, $output, $exitCode);

        self::assertSame(0, $exitCode, implode("\n", $output));
        self::assertStringContainsString('mesclado com config/content/landing.php', implode("\n", $output));
        self::assertStringContainsString('typography.profile=' . $expectedProfile, implode("\n", $output));
        self::assertStringContainsString('failures: 0', implode("\n", $output));
    }

    public static function mergedNicheContentProvider(): array
    {
        return [
            'pediatria' => ['pediatria', 'family'],
            'odontologia' => ['odontologia', 'premium'],
            'veterinaria' => ['veterinaria', 'warm'],
        ];
    }

    public function testValidatorFailsWhenRequiredAssetsAreMissing(): void
    {
        mkdir($this->rootPath . '/config/content', 0777, true);
        mkdir($this->rootPath . '/public', 0777, true);

        file_put_contents($this->rootPath . '/config/content/landing.php', <<<'PHP'
<?php
return [
    'seo' => [
        'title' => 'Teste',
        'description' => 'Descrição',
        'site_name' => 'Site',
        'image' => ['src' => 'assets/img/missing-og.jpg', 'width' => 1200, 'height' => 630, 'alt' => 'Alt'],
        'schema' => ['type' => 'InvalidSchema', 'include_faq' => true],
    ],
    'typography' => ['profile' => 'invalid'],
    'hero' => [
        'badge' => 'Badge',
        'title_parts' => ['Um', 'Dois', 'Três'],
        'lead' => 'Lead',
        'image' => [
            'src' => 'assets/img/missing-hero.webp',
            'width' => 640,
            'height' => 360,
            'alt' => 'Hero',
        ],
    ],
    'services' => ['items' => [['title' => 'Serviço', 'text' => 'Texto']]],
    'faq' => ['items' => []],
    'form' => [
        'title' => 'Form',
        'text' => 'Texto',
        'fields' => [
            'name_label' => 'Nome',
            'phone_label' => 'Telefone',
            'email_label' => 'Email',
            'message_label' => 'Mensagem',
        ],
        'buttons' => ['submit' => 'Enviar'],
        'privacy_note' => 'Privacidade',
    ],
    'footer' => ['label' => 'Footer', 'credit' => 'Crédito'],
];
PHP);

        $script = dirname(__DIR__) . '/scripts/validate-landing-content.php';
        $command = escapeshellarg(PHP_BINARY)
            . ' ' . escapeshellarg($script)
            . ' --project-root ' . escapeshellarg($this->rootPath)
            . ' --content landing'
            . ' --slug teste';

        exec($command, $output, $exitCode);

        self::assertSame(1, $exitCode, implode("\n", $output));
        self::assertStringContainsString('seo.schema.type inválido', implode("\n", $output));
        self::assertStringContainsString('typography.profile inválido', implode("\n", $output));
        self::assertStringContainsString('seo.image.src não encontrado', implode("\n", $output));
        self::assertStringContainsString('hero.image.src não encontrado', implode("\n", $output));
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
