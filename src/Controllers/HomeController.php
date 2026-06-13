<?php

declare(strict_types=1);

namespace Devioz\Controllers;

use Devioz\Models\Category;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

/** Renderiza la pagina corporativa publica (server-side para SEO). */
class HomeController
{
    public function index(Request $request, Response $response): Response
    {
        // Si la BD no esta disponible la web igual carga (el catalogo se oculta)
        try {
            $categories = Category::with(['products' => fn ($q) => $q->where('active', true)->orderBy('price_offer')])
                ->orderBy('id')
                ->get();
        } catch (Throwable) {
            $categories = collect();
        }

        $whatsapp = preg_replace('/\D+/', '', (string) env('WHATSAPP_NUMBER', '51999999999'));
        $appUrl   = rtrim((string) env('APP_URL', 'http://localhost:8080'), '/');

        ob_start();
        require dirname(__DIR__) . '/Views/home.php';
        $html = (string) ob_get_clean();

        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }
}
