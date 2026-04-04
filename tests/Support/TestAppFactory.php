<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Controllers\HomeController;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

final class TestAppFactory
{
    public static function create(array $config = []): App
    {
        $base = $config['base_url'] ?? '/natalcode';

        $twig = Twig::create(dirname(__DIR__, 2) . '/views', [
            'cache' => false,
            'auto_reload' => true,
        ]);

        $twig->getEnvironment()->addGlobal('base_url', $base);
        $twig->getEnvironment()->addGlobal('app_env', 'test');
        $twig->getEnvironment()->addGlobal('app_name', $config['app_name'] ?? 'NatalCode');
        $twig->getEnvironment()->addGlobal('app_mark', $config['app_mark'] ?? 'N');
        $twig->getEnvironment()->addGlobal('app_badge', $config['app_badge'] ?? 'PHP 8.3+');
        $twig->getEnvironment()->addGlobal('app_palette', $config['palette'] ?? 'blue');
        $twig->getEnvironment()->addGlobal('github_url', $config['github_url'] ?? '#');
        $twig->getEnvironment()->addGlobal('x_url', $config['x_url'] ?? '#');
        $twig->getEnvironment()->addGlobal('instagram_url', $config['instagram_url'] ?? '#');
        $twig->getEnvironment()->addGlobal('whatsapp_url', $config['whatsapp_url'] ?? '#');

        $controller = new HomeController($twig, [
            'app_name' => $config['app_name'] ?? 'NatalCode',
            'app_mark' => $config['app_mark'] ?? 'N',
            'page_title' => $config['page_title'] ?? 'NatalCode | Teste',
            'palette' => $config['palette'] ?? 'blue',
            'base_url' => $base,
            'contact_to' => array_key_exists('contact_to', $config) ? $config['contact_to'] : 'contato@example.com',
            'contact_from' => array_key_exists('contact_from', $config) ? $config['contact_from'] : 'no-reply@example.com',
            'mail_driver' => $config['mail_driver'] ?? 'smtp',
            'smtp_host' => $config['smtp_host'] ?? '',
            'smtp_port' => $config['smtp_port'] ?? 587,
            'smtp_user' => $config['smtp_user'] ?? '',
            'smtp_pass' => $config['smtp_pass'] ?? '',
            'smtp_encryption' => $config['smtp_encryption'] ?? 'tls',
            'smtp_auth' => $config['smtp_auth'] ?? true,
            'smtp_timeout' => $config['smtp_timeout'] ?? 15,
            'mail_sender' => $config['mail_sender'] ?? null,
            'storage_path' => $config['storage_path'] ?? null,
            'rate_limit_max_attempts' => $config['rate_limit_max_attempts'] ?? 5,
            'rate_limit_window_seconds' => $config['rate_limit_window_seconds'] ?? 600,
        ]);

        $app = AppFactory::create();
        $app->setBasePath($base);
        $app->add(TwigMiddleware::create($app, $twig));
        $app->addErrorMiddleware(true, true, true);

        $routes = require dirname(__DIR__, 2) . '/routes/web.php';
        $routes($app, $controller);

        return $app;
    }
}
