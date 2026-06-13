<?php

declare(strict_types=1);

namespace Devioz\Middleware;

use Devioz\Services\TokenService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Protege las rutas /api/admin/* exigiendo un Bearer token valido
 * emitido por TokenService en el login.
 */
class AdminAuthMiddleware implements MiddlewareInterface
{
    public function __construct(private TokenService $tokens)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $header = $request->getHeaderLine('Authorization');

        $token = str_starts_with($header, 'Bearer ')
            ? substr($header, 7)
            : null;

        $claims = $this->tokens->verify($token);

        if ($claims === null || empty($claims['admin_id'])) {
            $response = new Response(401);
            $response->getBody()->write((string) json_encode([
                'error'   => 'unauthorized',
                'message' => 'Sesión inválida o expirada. Inicia sesión nuevamente.',
            ]));

            return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
        }

        return $handler->handle(
            $request->withAttribute('admin_id', (int) $claims['admin_id'])
        );
    }
}
