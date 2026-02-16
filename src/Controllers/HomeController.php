<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class HomeController
{
    public function __construct(private Twig $twig, private array $config)
    {
    }

    public function home(Request $request, Response $response): Response
    {
        return $this->twig->render($response, 'pages/home.twig', [
            'app_name' => $this->config['app_name'] ?? 'Agência',
            'app_mark' => $this->config['app_mark'] ?? 'A',
            'page_title' => $this->config['page_title'] ?? null,
        ]);
    }
}
