<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contact\ContactMailer;
use App\Contact\ContactRateLimiter;
use App\Contact\LeadLogger;
use App\Contact\RecaptchaVerifier;
use App\Controllers\HomeController;
use App\Core\LandingContent;
use App\Core\WhatsappLink;
use App\Middleware\SecurityHeadersMiddleware;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

final class TestAppFactory
{
    public static function create(array $config = []): App
    {
        $base = $config['base_url'] ?? '/medico';
        $landingContent = $config['landing_content'] ?? LandingContent::load(dirname(__DIR__, 2), '', 'landing');

        $twig = Twig::create(dirname(__DIR__, 2) . '/views', [
            'cache' => false,
            'auto_reload' => true,
        ]);
        $whatsappUrl = WhatsappLink::fromConfig([
            'app_whatsapp_number' => $config['app_whatsapp_number'] ?? '',
            'app_whatsapp_message' => $config['app_whatsapp_message'] ?? '',
            'whatsapp_url' => $config['whatsapp_url'] ?? '',
        ]);

        $twig->getEnvironment()->addGlobal('base_url', $base);
        $twig->getEnvironment()->addGlobal('app_env', 'test');
        $twig->getEnvironment()->addGlobal('app_name', $config['app_name'] ?? 'Clínica Médica');
        $twig->getEnvironment()->addGlobal('app_mark', $config['app_mark'] ?? 'M');
        $twig->getEnvironment()->addGlobal('app_badge', $config['app_badge'] ?? ($landingContent['nav']['badge'] ?? 'Clínica médica'));
        $twig->getEnvironment()->addGlobal('app_palette', $config['palette'] ?? 'blue');
        $twig->getEnvironment()->addGlobal('landing_content', $landingContent);
        $twig->getEnvironment()->addGlobal('show_palette_selector', $config['show_palette_selector'] ?? false);
        $twig->getEnvironment()->addGlobal('recaptcha_enabled', $config['recaptcha_enabled'] ?? false);
        $twig->getEnvironment()->addGlobal('recaptcha_site_key', $config['recaptcha_site_key'] ?? '');
        $twig->getEnvironment()->addGlobal('recaptcha_action', $config['recaptcha_action'] ?? 'contact_submit');
        $twig->getEnvironment()->addGlobal('github_url', $config['github_url'] ?? '#');
        $twig->getEnvironment()->addGlobal('x_url', $config['x_url'] ?? '#');
        $twig->getEnvironment()->addGlobal('instagram_url', $config['instagram_url'] ?? '#');
        $twig->getEnvironment()->addGlobal('whatsapp_url', $whatsappUrl);
        $twig->getEnvironment()->addGlobal('csp_nonce', $config['csp_nonce'] ?? 'test-nonce-aaaa');

        $recaptchaVerifier = new RecaptchaVerifier([
            'recaptcha_enabled'          => $config['recaptcha_enabled'] ?? false,
            'recaptcha_site_key'         => $config['recaptcha_site_key'] ?? '',
            'recaptcha_secret_key'       => $config['recaptcha_secret_key'] ?? '',
            'recaptcha_min_score'        => $config['recaptcha_min_score'] ?? 0.5,
            'recaptcha_allowed_hostname' => $config['recaptcha_allowed_hostname'] ?? '',
            'recaptcha_action'           => $config['recaptcha_action'] ?? 'contact_submit',
            'recaptcha_verifier'         => $config['recaptcha_verifier'] ?? null,
        ]);

        $rateLimiter = new ContactRateLimiter([
            'rate_limit_max_attempts'   => $config['rate_limit_max_attempts'] ?? 5,
            'rate_limit_window_seconds' => $config['rate_limit_window_seconds'] ?? 600,
            'storage_path'              => $config['storage_path'] ?? null,
        ]);

        // Allow callers to inject a MailerInterface directly (e.g. FakeMailer).
        // Fall back to ContactMailer with optional mail_sender callable for tests
        // that still need to assert on the full HTTP round-trip.
        if (array_key_exists('mailer', $config)) {
            $mailer = $config['mailer'];
        } else {
            $mailer = new ContactMailer([
                'app_name'        => $config['app_name'] ?? 'Clínica Médica',
                'contact_from'    => array_key_exists('contact_from', $config) ? $config['contact_from'] : 'no-reply@example.com',
                'mail_driver'     => $config['mail_driver'] ?? 'smtp',
                'smtp_host'       => $config['smtp_host'] ?? '',
                'smtp_port'       => $config['smtp_port'] ?? 587,
                'smtp_user'       => $config['smtp_user'] ?? '',
                'smtp_pass'       => $config['smtp_pass'] ?? '',
                'smtp_encryption' => $config['smtp_encryption'] ?? 'tls',
                'smtp_auth'       => $config['smtp_auth'] ?? true,
                'smtp_timeout'    => $config['smtp_timeout'] ?? 15,
                'mail_sender'     => $config['mail_sender'] ?? null,
            ]);
        }

        $leadLogger = new LeadLogger([
            'app_name'                => $config['app_name'] ?? 'Clínica Médica',
            'app_slug'                => $config['app_slug'] ?? 'medico',
            'base_url'                => $base,
            'lead_log_retention_days' => $config['lead_log_retention_days'] ?? 30,
            'lead_log_hash_salt'      => $config['lead_log_hash_salt'] ?? 'test-salt',
            'lead_encrypt_key'        => $config['lead_encrypt_key'] ?? '',
            'storage_path'            => $config['storage_path'] ?? null,
        ]);

        $controller = new HomeController($twig, [
            'app_name'              => $config['app_name'] ?? 'Clínica Médica',
            'app_mark'              => $config['app_mark'] ?? 'M',
            'app_slug'              => $config['app_slug'] ?? 'medico',
            'request_prefix'        => $config['request_prefix'] ?? 'MED',
            'page_title'            => array_key_exists('page_title', $config) ? $config['page_title'] : 'Clínica Médica | Teste',
            'canonical_url'         => $config['canonical_url'] ?? '',
            'landing_content'       => $landingContent,
            'palette'               => $config['palette'] ?? 'blue',
            'show_palette_selector' => $config['show_palette_selector'] ?? false,
            'base_url'              => $base,
            'contact_to'            => array_key_exists('contact_to', $config) ? $config['contact_to'] : 'contato@example.com',
            'x_url'                 => $config['x_url'] ?? '#',
            'facebook_url'          => $config['facebook_url'] ?? '#',
            'instagram_url'         => $config['instagram_url'] ?? '#',
            'whatsapp_url'          => $whatsappUrl,
        ], $recaptchaVerifier, $rateLimiter, $mailer, $leadLogger);

        $app = AppFactory::create();
        $app->setBasePath($base);
        $app->add(TwigMiddleware::create($app, $twig));
        $app->addErrorMiddleware(true, true, true);
        $app->add(new SecurityHeadersMiddleware($config['csp_nonce'] ?? 'test-nonce-aaaa'));

        $routes = require dirname(__DIR__, 2) . '/routes/web.php';
        $routes($app, $controller);

        return $app;
    }
}
