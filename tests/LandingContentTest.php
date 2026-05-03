<?php

declare(strict_types=1);

namespace Tests;

use App\Core\LandingContent;
use PHPUnit\Framework\TestCase;

final class LandingContentTest extends TestCase
{
    private string $rootPath;

    protected function setUp(): void
    {
        $this->rootPath = sys_get_temp_dir() . '/landing-content-' . bin2hex(random_bytes(4));
        mkdir($this->rootPath . '/config/content', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->rootPath);
    }

    public function testLoadMergesNamedContentWithDefaultLandingContent(): void
    {
        file_put_contents($this->rootPath . '/config/content/landing.php', <<<'PHP'
<?php
return [
    'seo' => ['title' => 'Default title', 'description' => 'Default description'],
    'services' => ['items' => [['title' => 'Default service']]],
    'footer' => ['label' => 'Default footer', 'meta' => 'Default meta'],
];
PHP);

        file_put_contents($this->rootPath . '/config/content/pediatria.php', <<<'PHP'
<?php
return [
    'seo' => ['title' => 'Pediatria title'],
    'services' => ['items' => [['title' => 'Puericultura']]],
    'footer' => ['label' => 'Pediatria'],
];
PHP);

        $content = LandingContent::load($this->rootPath, 'pediatria', '');

        self::assertSame('Pediatria title', $content['seo']['title'] ?? null);
        self::assertSame('Default description', $content['seo']['description'] ?? null);
        self::assertSame([['title' => 'Puericultura']], $content['services']['items'] ?? null);
        self::assertSame('Default meta', $content['footer']['meta'] ?? null);
    }

    public function testLoadSanitizesContentFileNameAndFallsBackToDefault(): void
    {
        file_put_contents($this->rootPath . '/config/content/landing.php', <<<'PHP'
<?php
return ['seo' => ['title' => 'Default title']];
PHP);

        file_put_contents($this->rootPath . '/secret.php', <<<'PHP'
<?php
return ['seo' => ['title' => 'Secret title']];
PHP);

        $content = LandingContent::load($this->rootPath, '../secret', '');

        self::assertSame('Default title', $content['seo']['title'] ?? null);
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
