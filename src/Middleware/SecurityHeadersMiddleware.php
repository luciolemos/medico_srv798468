<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly string $cspNonce = '') {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        foreach ($this->headers() as $name => $value) {
            if (!$response->hasHeader($name)) {
                $response = $response->withHeader($name, $value);
            }
        }

        return $response;
    }

    /**
     * Keep CSP out of this shared prototype until each derived landing has its
     * final analytics, reCAPTCHA and media requirements.
     */
    private function headers(): array
    {
        return [
            'X-Content-Type-Options'    => 'nosniff',
            'X-Frame-Options'           => 'SAMEORIGIN',
            'Referrer-Policy'           => 'strict-origin-when-cross-origin',
            'Permissions-Policy'        => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
            'Content-Security-Policy'   => $this->buildCsp(),
        ];
    }

    private function buildCsp(): string
    {
        $noncePart = $this->cspNonce !== ''
            ? " 'nonce-{$this->cspNonce}'"
            : '';

        return implode('; ', [
            "default-src 'self'",
            "script-src 'self'{$noncePart} https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/",
            "style-src 'self'",
            "style-src-elem 'self'",
            "style-src-attr 'none'",
            "img-src 'self' data: https:",
            "connect-src 'self' https://www.google.com/recaptcha/",
            "font-src 'self'",
            // Permite embeds necessários (reCAPTCHA e Google Maps)
            "frame-src https://www.google.com/recaptcha/ https://recaptcha.google.com/ https://www.google.com/maps/ https://maps.google.com/",
            "frame-ancestors 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
    }
}
