<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/Support/TestAppFactory.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
