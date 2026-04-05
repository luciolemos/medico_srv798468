<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Slim\Views\Twig;

require __DIR__ . '/../vendor/autoload.php';

ob_start();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

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
    $twig = Twig::create(__DIR__ . '/../views', [
        'cache' => false,
        'auto_reload' => true,
    ]);

    return new HomeController($twig, [
        'app_name' => 'NatalCloud Test',
        'app_mark' => 'N',
        'palette' => $paletteFromConfig,
        'base_url' => '',
    ]);
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

function renderCopyModeFromHome(HomeController $controller, string $uri): string
{
    $requestFactory = new ServerRequestFactory();
    $request = $requestFactory->createServerRequest('GET', $uri);
    $response = new Response();
    $rendered = $controller->home($request, $response);
    $html = (string) $rendered->getBody();

    if (preg_match('/data-copy-mode="([a-z]+)"/', $html, $matches) === 1) {
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

$copyMode = renderCopyModeFromHome(buildController('blue'), 'http://localhost/?copy=growth');
$t->assertSame('growth', $copyMode, 'copy mode: query growth aplicada no SSR');

$copyMode = renderCopyModeFromHome(buildController('blue'), 'http://localhost/?copy=invalido');
$t->assertSame('soft', $copyMode, 'copy mode: fallback para soft quando valor invalido');

$controller = buildController('blue');

$validData = [
    'nome' => 'Fulano',
    'telefone' => '(84) 99999-0000',
    'email' => 'fulano@example.com',
    'empresa' => 'Empresa',
    'mensagem' => 'Preciso de uma landing page.',
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
