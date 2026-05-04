<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Contact\ContactMailer;
use App\Contact\ContactRateLimiter;
use App\Contact\LeadLogger;
use App\Contact\RecaptchaVerifier;
use App\Core\LandingContent;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Slim\Views\Twig;

require __DIR__ . '/../vendor/autoload.php';

ob_start();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$testStoragePath = sys_get_temp_dir() . '/medico-test-unit-' . getmypid();
register_shutdown_function(static function () use ($testStoragePath): void {
    removeDirectory($testStoragePath);
});

final class TestRunner
{
    private int $assertions = 0;
    private int $failures = 0;

    public function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        $this->assertions++;
        if ($expected === $actual) {
            echo "[ok  ] {$message}\n";
            return;
        }

        $this->failures++;
        echo "[fail] {$message} | expected=" . var_export($expected, true) . " got=" . var_export($actual, true) . "\n";
    }

    public function assertTrue(bool $value, string $message): void
    {
        $this->assertions++;
        if ($value) {
            echo "[ok  ] {$message}\n";
            return;
        }

        $this->failures++;
        echo "[fail] {$message}\n";
    }

    public function summary(): int
    {
        echo "\nSummary\n";
        echo "  assertions: {$this->assertions}\n";
        echo "  failures: {$this->failures}\n";
        return $this->failures;
    }
}

function buildController(string $paletteFromConfig = 'blue'): HomeController
{
    global $testStoragePath;

    $projectRoot = dirname(__DIR__);
    $landingContent = LandingContent::load($projectRoot, 'landing', 'medico');

    $twig = Twig::create(__DIR__ . '/../views', [
        'cache' => false,
        'auto_reload' => true,
    ]);

    $twig->getEnvironment()->addGlobal('base_url', '');
    $twig->getEnvironment()->addGlobal('app_env', 'test');
    $twig->getEnvironment()->addGlobal('app_name', 'Clínica Médica Test');
    $twig->getEnvironment()->addGlobal('app_mark', 'M');
    $twig->getEnvironment()->addGlobal('app_badge', $landingContent['nav']['badge'] ?? 'Clínica médica');
    $twig->getEnvironment()->addGlobal('app_palette', $paletteFromConfig);
    $twig->getEnvironment()->addGlobal('landing_content', $landingContent);
    $twig->getEnvironment()->addGlobal('show_palette_selector', false);
    $twig->getEnvironment()->addGlobal('recaptcha_enabled', false);
    $twig->getEnvironment()->addGlobal('recaptcha_site_key', '');
    $twig->getEnvironment()->addGlobal('recaptcha_action', 'contact_submit');
    $twig->getEnvironment()->addGlobal('asset_version', 'test');
    $twig->getEnvironment()->addGlobal('github_url', '#');
    $twig->getEnvironment()->addGlobal('x_url', '#');
    $twig->getEnvironment()->addGlobal('facebook_url', '#');
    $twig->getEnvironment()->addGlobal('instagram_url', '#');
    $twig->getEnvironment()->addGlobal('whatsapp_url', '#');
    $twig->getEnvironment()->addGlobal('csp_nonce', 'test-nonce-aaaa');

    $config = [
        'app_name' => 'Clínica Médica Test',
        'app_mark' => 'M',
        'app_slug' => 'medico',
        'request_prefix' => 'MED',
        'page_title' => 'Clínica Médica Test',
        'canonical_url' => '',
        'landing_content' => $landingContent,
        'palette' => $paletteFromConfig,
        'base_url' => '',
        'contact_from' => 'no-reply@example.test',
        'lead_log_hash_salt' => 'test-salt',
    ];

    return new HomeController(
        $twig,
        $config,
        new RecaptchaVerifier(['recaptcha_enabled' => false]),
        new ContactRateLimiter([
            'rate_limit_max_attempts' => 0,
            'rate_limit_window_seconds' => 600,
            'storage_path' => $testStoragePath,
        ]),
        new ContactMailer([
            'app_name' => 'Clínica Médica Test',
            'contact_from' => 'no-reply@example.test',
            'mail_driver' => 'smtp',
        ]),
        new LeadLogger([
            'app_name' => 'Clínica Médica Test',
            'app_slug' => 'medico',
            'base_url' => '',
            'lead_log_retention_days' => 1,
            'lead_log_hash_salt' => 'test-salt',
            'storage_path' => $testStoragePath,
        ])
    );
}

function renderPaletteFromHome(HomeController $controller, string $uri): string
{
    $requestFactory = new ServerRequestFactory();
    $request = $requestFactory->createServerRequest('GET', $uri);
    $response = new Response();
    $rendered = $controller->home($request, $response);
    $html = (string) $rendered->getBody();

    if (preg_match('~/assets/css/palettes/([a-z]+)\.css~', $html, $matches) === 1) {
        return $matches[1];
    }

    return '';
}

function postContact(HomeController $controller, array $payload): Response
{
    if (!isset($payload['csrf_token']) || (string) $payload['csrf_token'] === '') {
        $payload['csrf_token'] = issueContactCsrfToken($controller);
    }

    $requestFactory = new ServerRequestFactory();
    $request = $requestFactory
        ->createServerRequest('POST', 'http://localhost/contato')
        ->withParsedBody($payload);

    $response = new Response();
    return $controller->contact($request, $response);
}

function issueContactCsrfToken(HomeController $controller): string
{
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('issueContactCsrfToken');
    $method->setAccessible(true);
    /** @var string $token */
    $token = $method->invoke($controller);
    return $token;
}

function callValidateContact(HomeController $controller, array $data): array
{
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('validateContact');
    $method->setAccessible(true);
    /** @var array $result */
    $result = $method->invoke($controller, $data);
    return $result;
}

function removeDirectory(string $path): void
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
            removeDirectory($fullPath);
            continue;
        }

        @unlink($fullPath);
    }

    @rmdir($path);
}

$t = new TestRunner();

$_COOKIE = ['palette' => 'emerald'];
$palette = renderPaletteFromHome(buildController('blue'), 'http://localhost/?palette=red');
$t->assertSame('red', $palette, 'palette: query valida prevalece sobre cookie/config');

$_COOKIE = ['palette' => 'emerald'];
$palette = renderPaletteFromHome(buildController('blue'), 'http://localhost/');
$t->assertSame('emerald', $palette, 'palette: cookie prevalece sem query');

$_COOKIE = ['palette' => 'invalida'];
$palette = renderPaletteFromHome(buildController('amber'), 'http://localhost/?palette=naoexiste');
$t->assertSame('amber', $palette, 'palette: config prevalece quando query/cookie invalidos');

$_COOKIE = ['palette' => 'naovalida'];
$palette = renderPaletteFromHome(buildController('naovalida'), 'http://localhost/?palette=invalida');
$t->assertSame('blue', $palette, 'palette: fallback final para blue');

$controller = buildController('blue');

$validData = [
    'nome' => 'Fulano',
    'telefone' => '(84) 99999-0000',
    'email' => 'fulano@example.com',
    'empresa' => 'Particular',
    'mensagem' => 'Gostaria de agendar uma consulta clínica.',
];
$errors = callValidateContact($controller, $validData);
$t->assertSame([], $errors, 'contact: payload valido sem erros');

$invalidData = [
    'nome' => '',
    'telefone' => '',
    'email' => 'email-invalido',
    'empresa' => '',
    'mensagem' => '',
];
$errors = callValidateContact($controller, $invalidData);
$t->assertTrue(isset($errors['nome']), 'contact: valida nome obrigatorio');
$t->assertTrue(isset($errors['telefone']), 'contact: valida telefone obrigatorio');
$t->assertTrue(isset($errors['email']), 'contact: valida email obrigatorio/formato');
$t->assertTrue(isset($errors['mensagem']), 'contact: valida mensagem obrigatoria');

$_SESSION['form_flash'] = null;
$invalidResponse = postContact($controller, $invalidData);
$t->assertSame(302, $invalidResponse->getStatusCode(), 'contact action: invalido retorna 302');
$t->assertSame('/#form-orcamento', $invalidResponse->getHeaderLine('Location'), 'contact action: invalido redireciona para ancora do formulario');
$t->assertSame('error', $_SESSION['form_flash']['status']['type'] ?? '', 'contact action: invalido grava flash de erro');

$controllerWithoutContact = buildController('blue');
$_SESSION['form_flash'] = null;
$validWithoutContact = postContact($controllerWithoutContact, $validData);
$t->assertSame(302, $validWithoutContact->getStatusCode(), 'contact action: sem CONTACT_TO retorna 302');
$t->assertSame('/#form-orcamento', $validWithoutContact->getHeaderLine('Location'), 'contact action: sem CONTACT_TO redireciona para ancora do formulario');
$t->assertSame('warning', $_SESSION['form_flash']['status']['type'] ?? '', 'contact action: sem CONTACT_TO grava flash warning');
$t->assertSame('lead_form_submit_failure', $_SESSION['form_flash']['status']['tracking_event'] ?? '', 'contact action: sem CONTACT_TO registra evento de falha');

$failures = $t->summary();
ob_end_flush();
exit($failures > 0 ? 1 : 0);
