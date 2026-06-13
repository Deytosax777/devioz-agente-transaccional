<?php

declare(strict_types=1);

/**
 * Front controller - Devioz Web + Agente SofIA
 * Slim Framework 4 + Eloquent ORM
 */

use Devioz\Middleware\CorsMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;

// Bootstrap: autoload + .env + Eloquent
require dirname(__DIR__) . '/src/bootstrap.php';

$app = AppFactory::create();

// Parseo de cuerpos JSON / form-data
$app->addBodyParsingMiddleware();

// Rutas de la aplicacion
(require dirname(__DIR__) . '/src/routes.php')($app);

$app->addRoutingMiddleware();

// CORS para el widget y el panel admin (tambien responde preflights OPTIONS)
$app->add(new CorsMiddleware());

// Manejo de errores: JSON para la API, HTML simple para la web
$displayErrors = env('APP_ENV', 'production') !== 'production';
$errorMiddleware = $app->addErrorMiddleware($displayErrors, true, true);

$errorMiddleware->setDefaultErrorHandler(function (
    Request $request,
    Throwable $exception,
    bool $displayErrorDetails
) use ($app): Response {
    $isApi  = str_starts_with($request->getUri()->getPath(), '/api');
    $status = $exception instanceof HttpNotFoundException ? 404 : 500;

    $response = $app->getResponseFactory()->createResponse($status);

    if ($isApi) {
        $payload = [
            'error'   => $status === 404 ? 'not_found' : 'server_error',
            'message' => $status === 404
                ? 'Recurso no encontrado.'
                : ($displayErrorDetails ? $exception->getMessage() : 'Error interno del servidor.'),
        ];
        $response->getBody()->write((string) json_encode($payload, JSON_UNESCAPED_UNICODE));

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    $message = $status === 404 ? 'Página no encontrada' : 'Error interno';
    $detail  = $displayErrorDetails ? htmlspecialchars($exception->getMessage()) : '';
    $response->getBody()->write(
        "<!DOCTYPE html><html lang='es'><head><meta charset='utf-8'><title>{$status} - Devioz</title>" .
        "<style>body{font-family:system-ui;background:#0a0e1a;color:#e2e8f0;display:grid;place-items:center;min-height:100vh;margin:0}" .
        "div{text-align:center}h1{font-size:4rem;margin:0;color:#3b82f6}a{color:#60a5fa}</style></head>" .
        "<body><div><h1>{$status}</h1><p>{$message}</p><p>{$detail}</p><a href='/'>Volver al inicio</a></div></body></html>"
    );

    return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
});

$app->run();
