<?php

declare(strict_types=1);

namespace Devioz\Middleware;

use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Rate limiting por IP con ventana fija de 60 segundos, persistido en MySQL.
 * Protege los endpoints costosos (chat IA, checkout) de llamadas abusivas.
 */
class RateLimitingMiddleware implements MiddlewareInterface
{
    private const WINDOW_SECONDS = 60;

    private int $limit;
    private string $group;

    public function __construct(string $group, ?int $limit = null)
    {
        $this->group = $group;
        $this->limit = $limit ?? (int) env('RATE_LIMIT_PER_MIN', 20);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ip  = $this->clientIp($request);
        $key = substr($ip . '|' . $this->group, 0, 140);

        $allowed = Capsule::connection()->transaction(function () use ($key) {
            $now    = date('Y-m-d H:i:s');
            $cutoff = date('Y-m-d H:i:s', time() - self::WINDOW_SECONDS);

            $row = Capsule::table('rate_limits')
                ->where('rl_key', $key)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                Capsule::table('rate_limits')->insert([
                    'rl_key'       => $key,
                    'window_start' => $now,
                    'hits'         => 1,
                ]);
                return true;
            }

            if ($row->window_start < $cutoff) {
                Capsule::table('rate_limits')
                    ->where('id', $row->id)
                    ->update(['window_start' => $now, 'hits' => 1]);
                return true;
            }

            if ($row->hits >= $this->limit) {
                return false;
            }

            Capsule::table('rate_limits')
                ->where('id', $row->id)
                ->increment('hits');

            return true;
        });

        if (!$allowed) {
            $response = new Response(429);
            $response->getBody()->write((string) json_encode([
                'error'   => 'rate_limited',
                'message' => 'Demasiadas solicitudes. Intenta nuevamente en un minuto.',
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json; charset=utf-8')
                ->withHeader('Retry-After', (string) self::WINDOW_SECONDS);
        }

        return $handler->handle($request);
    }

    private function clientIp(ServerRequestInterface $request): string
    {
        $server = $request->getServerParams();

        $forwarded = $request->getHeaderLine('X-Forwarded-For');
        if ($forwarded !== '') {
            $parts = explode(',', $forwarded);
            return trim($parts[0]);
        }

        return $server['REMOTE_ADDR'] ?? 'unknown';
    }
}
