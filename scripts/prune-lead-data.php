#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Core\Env;

$projectRoot = dirname(__DIR__);
require $projectRoot . '/src/Core/Env.php';
Env::load($projectRoot . '/.env');

$storagePath = $projectRoot . '/storage';
$days = (int) ($_ENV['LEAD_LOG_RETENTION_DAYS'] ?? 30);
$dryRun = false;

$args = $argv;
array_shift($args);
while ($args !== []) {
    $arg = array_shift($args);
    switch ($arg) {
        case '--storage':
            $storagePath = rtrim((string) array_shift($args), '/');
            break;
        case '--days':
            $days = (int) array_shift($args);
            break;
        case '--dry-run':
            $dryRun = true;
            break;
        case '--help':
        case '-h':
            echo "Usage: php scripts/prune-lead-data.php [--storage PATH] [--days N] [--dry-run]\n";
            exit(0);
        default:
            fwrite(STDERR, "[error] argumento desconhecido: {$arg}\n");
            exit(1);
    }
}

if ($days <= 0) {
    fwrite(STDERR, "[error] --days deve ser maior que zero\n");
    exit(1);
}

$cutoff = time() - ($days * 86400);
$stats = [
    'log_lines_removed' => 0,
    'log_lines_kept' => 0,
    'rate_limit_files_removed' => 0,
];

foreach ([$storagePath . '/logs/lead-events.log', $storagePath . '/logs/contatos-fallback.log'] as $logFile) {
    pruneJsonLines($logFile, $cutoff, $dryRun, $stats);
}

$rateLimitDir = $storagePath . '/rate-limit';
if (is_dir($rateLimitDir)) {
    $files = glob($rateLimitDir . '/contact-*.json') ?: [];
    foreach ($files as $file) {
        if (!is_file($file)) {
            continue;
        }

        $mtime = @filemtime($file) ?: time();
        if ($mtime >= $cutoff) {
            continue;
        }

        $stats['rate_limit_files_removed']++;
        if (!$dryRun) {
            @unlink($file);
        }
    }
}

echo '[ok  ] retention_days=' . $days
    . ' log_lines_removed=' . $stats['log_lines_removed']
    . ' log_lines_kept=' . $stats['log_lines_kept']
    . ' rate_limit_files_removed=' . $stats['rate_limit_files_removed']
    . ($dryRun ? ' dry_run=1' : '')
    . PHP_EOL;

function pruneJsonLines(string $file, int $cutoff, bool $dryRun, array &$stats): void
{
    if (!is_file($file)) {
        return;
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        fwrite(STDERR, "[warn] nao foi possivel ler {$file}\n");
        return;
    }

    $kept = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '') {
            continue;
        }

        $timestamp = extractTimestamp($trimmed);
        if ($timestamp === null || $timestamp >= $cutoff) {
            $kept[] = $line;
            $stats['log_lines_kept']++;
            continue;
        }

        $stats['log_lines_removed']++;
    }

    if ($dryRun) {
        return;
    }

    if ($kept === []) {
        @file_put_contents($file, '');
        return;
    }

    @file_put_contents($file, implode(PHP_EOL, $kept) . PHP_EOL, LOCK_EX);
}

function extractTimestamp(string $jsonLine): ?int
{
    $decoded = json_decode($jsonLine, true);
    if (!is_array($decoded)) {
        return null;
    }

    $timestamp = $decoded['timestamp'] ?? null;
    if (!is_string($timestamp) || trim($timestamp) === '') {
        return null;
    }

    $parsed = strtotime($timestamp);
    return is_int($parsed) ? $parsed : null;
}
