<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Controllers\HomeController;
use App\Core\Env;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/../src/Core/Env.php';
Env::load(__DIR__ . '/../.env');

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    echo '<h1>Dependencias ausentes</h1><p>Rode <code>composer install</code> neste projeto.</p>';
    exit;
}

require $autoload;

$base = $_ENV['APP_BASE'] ?? '';
$appEnv = strtolower((string) ($_ENV['APP_ENV'] ?? 'production'));
$isDev = in_array($appEnv, ['dev', 'development', 'local'], true);
$twigCache = $isDev ? false : __DIR__ . '/../storage/cache/twig';

$twig = Twig::create(__DIR__ . '/../views', [
    'cache' => $twigCache,
    'auto_reload' => $isDev,
]);
$twig->getEnvironment()->addGlobal('base_url', $base);
$twig->getEnvironment()->addGlobal('app_env', $_ENV['APP_ENV'] ?? 'production');
$twig->getEnvironment()->addGlobal('app_name', $_ENV['APP_NAME'] ?? 'Agência');
$twig->getEnvironment()->addGlobal('app_mark', $_ENV['APP_MARK'] ?? 'A');
$twig->getEnvironment()->addGlobal('app_badge', $_ENV['APP_BADGE'] ?? 'PHP 8.3+');
$twig->getEnvironment()->addGlobal('app_palette', $_ENV['APP_PALETTE'] ?? 'blue');
$twig->getEnvironment()->addGlobal('github_url', $_ENV['GITHUB_URL'] ?? '#');
$twig->getEnvironment()->addGlobal('x_url', $_ENV['X_URL'] ?? 'https://x.com');
$twig->getEnvironment()->addGlobal('instagram_url', $_ENV['INSTAGRAM_URL'] ?? 'https://instagram.com');
$twig->getEnvironment()->addGlobal('whatsapp_url', $_ENV['WHATSAPP_URL'] ?? 'https://wa.me/5584998087340');

$controller = new HomeController($twig, [
    'app_name' => $_ENV['APP_NAME'] ?? 'Agência',
    'app_mark' => $_ENV['APP_MARK'] ?? 'A',
    'page_title' => $_ENV['APP_PAGE_TITLE'] ?? null,
    'palette' => $_ENV['APP_PALETTE'] ?? 'blue',
    'base_url' => $base,
    'contact_to' => $_ENV['CONTACT_TO'] ?? null,
    'contact_from' => $_ENV['CONTACT_FROM'] ?? null,
    'mail_driver' => $_ENV['MAIL_DRIVER'] ?? 'mail',
    'smtp_host' => $_ENV['SMTP_HOST'] ?? '',
    'smtp_port' => (int) ($_ENV['SMTP_PORT'] ?? 587),
    'smtp_user' => $_ENV['SMTP_USER'] ?? '',
    'smtp_pass' => $_ENV['SMTP_PASS'] ?? '',
    'smtp_encryption' => $_ENV['SMTP_ENCRYPTION'] ?? 'tls',
    'smtp_auth' => ($_ENV['SMTP_AUTH'] ?? 'true') !== 'false',
    'smtp_timeout' => (int) ($_ENV['SMTP_TIMEOUT'] ?? 15),
]);

$app = AppFactory::create();
$app->setBasePath($base);
$app->add(TwigMiddleware::create($app, $twig));

$app->addErrorMiddleware($isDev, $isDev, $isDev);

$routes = require __DIR__ . '/../routes/web.php';
$routes($app, $controller);

$app->run();
