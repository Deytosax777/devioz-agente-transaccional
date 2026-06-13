<?php

declare(strict_types=1);

namespace Devioz\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * CORS para que el widget React y el panel admin consuman la API
 * sin bloqueos del navegador. Origenes permitidos via CORS_ALLOWED_ORIGINS.
 */
class CorsMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Preflight: responder de inmediato sin tocar la aplicacion
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return $this->withCorsHeaders($request, new Response(204));
        }

        $response = $handler->handle($request);

        return $this->withCorsHeaders($request, $response);
    }

    private function withCorsHeaders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $origin = self::resolveAllowedOrigin($request->getHeaderLine('Origin'));

        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept, Origin, X-Requested-With')
            ->withHeader('Access-Control-Max-Age', '86400');

        if ($origin !== '*') {
            $response = $response
                ->withHeader('Vary', 'Origin')
                ->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }

    /**
     * Determina el valor de Access-Control-Allow-Origin segun la lista
     * configurada. Tambien lo usa el endpoint SSE, que emite headers a mano.
     */
    public static function resolveAllowedOrigin(string $requestOrigin): string
    {
        $configured = trim((string) env('CORS_ALLOWED_ORIGINS', '*'));

        if ($configured === '' || $configured === '*') {
            return '*';
        }

        $allowed = array_map('trim', explode(',', $configured));

        if ($requestOrigin !== '' && in_array($requestOrigin, $allowed, true)) {
            return $requestOrigin;
        }

        // Origen no listado: devolver el primero configurado (el navegador bloqueara el resto)
        return $allowed[0];
    }
}
