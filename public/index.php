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

$base = trim((string) ($_ENV['APP_BASE'] ?? ''));
if ($base === '') {
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    if ($scriptDir !== '' && $scriptDir !== '.' && $scriptDir !== '/') {
        $base = $scriptDir;
        $_ENV['APP_BASE'] = $base;
        putenv('APP_BASE=' . $base);
    }
}
$appEnv = strtolower((string) ($_ENV['APP_ENV'] ?? 'production'));
$isDev = in_array($appEnv, ['dev', 'development', 'local'], true);
$showPaletteSelector = filter_var($_ENV['APP_SHOW_PALETTE_SELECTOR'] ?? false, FILTER_VALIDATE_BOOLEAN);
$recaptchaEnabled = filter_var($_ENV['RECAPTCHA_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN);
$recaptchaSiteKey = trim((string) ($_ENV['RECAPTCHA_SITE_KEY'] ?? ''));
$recaptchaAction = trim((string) ($_ENV['RECAPTCHA_ACTION'] ?? 'contact_submit'));
$twigCache = $isDev ? false : __DIR__ . '/../storage/cache/twig';
$assetVersion = trim((string) ($_ENV['ASSET_VERSION'] ?? ''));
if ($assetVersion === '') {
    $assetVersion = (string) max(
        (int) (@filemtime(__DIR__ . '/assets/css/app.css') ?: 0),
        (int) (@filemtime(__DIR__ . '/assets/css/landing.css') ?: 0),
        (int) (@filemtime(__DIR__ . '/assets/js/landing.js') ?: 0),
        (int) (@filemtime(__DIR__ . '/assets/vendor/bootstrap/bootstrap.min.css') ?: 0),
        (int) (@filemtime(__DIR__ . '/assets/vendor/bootstrap/bootstrap.bundle.min.js') ?: 0),
        (int) (@filemtime(__DIR__ . '/assets/vendor/bootstrap-icons/bootstrap-icons.css') ?: 0)
    );
}

$twig = Twig::create(__DIR__ . '/../views', [
    'cache' => $twigCache,
    'auto_reload' => $isDev,
]);
$twig->getEnvironment()->addGlobal('base_url', $base);
$twig->getEnvironment()->addGlobal('app_env', $_ENV['APP_ENV'] ?? 'production');
$twig->getEnvironment()->addGlobal('app_name', $_ENV['APP_NAME'] ?? 'Clínica Médica');
$twig->getEnvironment()->addGlobal('app_mark', $_ENV['APP_MARK'] ?? 'M');
$twig->getEnvironment()->addGlobal('app_badge', $_ENV['APP_BADGE'] ?? 'Clínica Médica');
$twig->getEnvironment()->addGlobal('app_palette', $_ENV['APP_PALETTE'] ?? 'blue');
$twig->getEnvironment()->addGlobal('show_palette_selector', $showPaletteSelector);
$twig->getEnvironment()->addGlobal('recaptcha_enabled', $recaptchaEnabled && $recaptchaSiteKey !== '');
$twig->getEnvironment()->addGlobal('recaptcha_site_key', $recaptchaSiteKey);
$twig->getEnvironment()->addGlobal('recaptcha_action', $recaptchaAction !== '' ? $recaptchaAction : 'contact_submit');
$twig->getEnvironment()->addGlobal('asset_version', $assetVersion);
$twig->getEnvironment()->addGlobal('github_url', $_ENV['GITHUB_URL'] ?? '#');
$twig->getEnvironment()->addGlobal('x_url', $_ENV['X_URL'] ?? 'https://x.com');
$twig->getEnvironment()->addGlobal('facebook_url', $_ENV['FACEBOOK_URL'] ?? 'https://facebook.com');
$twig->getEnvironment()->addGlobal('instagram_url', $_ENV['INSTAGRAM_URL'] ?? 'https://instagram.com');
$twig->getEnvironment()->addGlobal('whatsapp_url', $_ENV['WHATSAPP_URL'] ?? 'https://wa.me/5584999031906');

$controller = new HomeController($twig, [
    'app_name' => $_ENV['APP_NAME'] ?? 'Clínica Médica',
    'app_mark' => $_ENV['APP_MARK'] ?? 'M',
    'page_title' => $_ENV['APP_PAGE_TITLE'] ?? null,
    'palette' => $_ENV['APP_PALETTE'] ?? 'blue',
    'show_palette_selector' => $showPaletteSelector,
    'base_url' => $base,
    'recaptcha_enabled' => $recaptchaEnabled,
    'recaptcha_site_key' => $recaptchaSiteKey,
    'recaptcha_secret_key' => trim((string) ($_ENV['RECAPTCHA_SECRET_KEY'] ?? '')),
    'recaptcha_min_score' => (float) ($_ENV['RECAPTCHA_MIN_SCORE'] ?? 0.5),
    'recaptcha_allowed_hostname' => trim((string) ($_ENV['RECAPTCHA_ALLOWED_HOSTNAME'] ?? '')),
    'recaptcha_action' => $recaptchaAction !== '' ? $recaptchaAction : 'contact_submit',
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
    'rate_limit_max_attempts' => (int) ($_ENV['RATE_LIMIT_MAX_ATTEMPTS'] ?? 5),
    'rate_limit_window_seconds' => (int) ($_ENV['RATE_LIMIT_WINDOW_SECONDS'] ?? 600),
]);

$app = AppFactory::create();
$app->setBasePath($base);
$app->add(TwigMiddleware::create($app, $twig));

$app->addErrorMiddleware($isDev, $isDev, $isDev);

$routes = require __DIR__ . '/../routes/web.php';
$routes($app, $controller);

$app->run();
