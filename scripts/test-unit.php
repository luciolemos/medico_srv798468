#!/usr/bin/env php
<?php

declare(strict_types=1);

// Backward-compatible entrypoint for older scripts/CI jobs.
// The canonical unit test suite is PHPUnit via composer.json.

$phpunit = dirname(__DIR__) . '/vendor/bin/phpunit';
if (!is_file($phpunit)) {
    fwrite(STDERR, "[error] PHPUnit não encontrado. Rode composer install.\n");
    exit(2);
}

$command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($phpunit);
passthru($command, $exitCode);

exit((int) $exitCode);
