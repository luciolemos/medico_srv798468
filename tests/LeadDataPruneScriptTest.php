<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class LeadDataPruneScriptTest extends TestCase
{
    private string $storagePath;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/lead-prune-' . bin2hex(random_bytes(4));
        mkdir($this->storagePath . '/logs', 0777, true);
        mkdir($this->storagePath . '/rate-limit', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->storagePath);
    }

    public function testPruneLeadDataRemovesExpiredLogLinesAndRateLimitFiles(): void
    {
        $oldTimestamp = date('c', time() - 45 * 86400);
        $newTimestamp = date('c', time() - 2 * 86400);

        file_put_contents($this->storagePath . '/logs/lead-events.log', implode(PHP_EOL, [
            json_encode(['timestamp' => $oldTimestamp, 'request_id' => 'OLD-1']),
            json_encode(['timestamp' => $newTimestamp, 'request_id' => 'NEW-1']),
            'not-json',
        ]) . PHP_EOL);
        file_put_contents($this->storagePath . '/logs/contatos-fallback.log', json_encode([
            'timestamp' => $oldTimestamp,
            'request_id' => 'OLD-FALLBACK',
        ]) . PHP_EOL);

        $rateLimitFile = $this->storagePath . '/rate-limit/contact-old.json';
        file_put_contents($rateLimitFile, '[]');
        touch($rateLimitFile, time() - 45 * 86400);

        $script = dirname(__DIR__) . '/scripts/prune-lead-data.php';
        $command = escapeshellarg(PHP_BINARY)
            . ' ' . escapeshellarg($script)
            . ' --storage ' . escapeshellarg($this->storagePath)
            . ' --days 30';
        exec($command, $output, $exitCode);

        self::assertSame(0, $exitCode, implode("\n", $output));
        self::assertStringContainsString('log_lines_removed=2', implode("\n", $output));
        self::assertStringContainsString('rate_limit_files_removed=1', implode("\n", $output));

        $leadLog = file_get_contents($this->storagePath . '/logs/lead-events.log') ?: '';
        self::assertStringNotContainsString('OLD-1', $leadLog);
        self::assertStringContainsString('NEW-1', $leadLog);
        self::assertStringContainsString('not-json', $leadLog);
        self::assertSame('', file_get_contents($this->storagePath . '/logs/contatos-fallback.log') ?: '');
        self::assertFileDoesNotExist($rateLimitFile);
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
