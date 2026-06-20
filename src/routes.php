<?php

declare(strict_types=1);

use Devioz\Controllers\AdminController;
use Devioz\Controllers\AuthController;
use Devioz\Controllers\CartController;
use Devioz\Controllers\ChatController;
use Devioz\Controllers\ContactController;
use Devioz\Controllers\HomeController;
use Devioz\Controllers\PaymentController;
use Devioz\Controllers\ProductController;
use Devioz\Controllers\WebhookController;
use Devioz\Middleware\AdminAuthMiddleware;
use Devioz\Middleware\RateLimitingMiddleware;
use Devioz\Services\CulqiPaymentService;
use Devioz\Services\EmailService;
use Devioz\Services\GroqService;
use Devioz\Services\TokenService;
use Devioz\Services\ToolExecutor;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {

    // ---------- Servicios compartidos (wiring manual, sin contenedor) ----------
    $groq   = new GroqService();
    $tools  = new ToolExecutor();
    $culqi  = new CulqiPaymentService();
    $tokens = new TokenService();

    $homeController    = new HomeController();
    $contactController = new ContactController();
    $productController = new ProductController();
    $cartController    = new CartController();
    $chatController    = new ChatController($groq, $tools);
    $paymentController = new PaymentController($culqi, new EmailService());
    $webhookController = new WebhookController($culqi);
    $authController    = new AuthController($tokens);
    $adminController   = new AdminController();

    // ---------- Web publica ----------
    $app->get('/', [$homeController, 'index']);

    $app->get('/admin', function (Request $request, Response $response): Response {
        return $response->withHeader('Location', '/admin/')->withStatus(302);
    });

    // ---------- API publica ----------
    $app->group('/api', function (RouteCollectorProxy $api) use (
        $contactController,
        $productController,
        $cartController,
        $chatController,
        $paymentController,
        $webhookController
    ): void {
        $api->get('/health', function (Request $request, Response $response): Response {
            $response->getBody()->write((string) json_encode([
                'status'  => 'ok',
                'service' => 'devioz-api',
                'time'    => date('c'),
            ]));
            return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
        });

        $api->get('/config', [$paymentController, 'config']);

        // Catalogo
        $api->get('/products', [$productController, 'index']);
        $api->get('/products/{id:[0-9]+}', [$productController, 'show']);
        $api->get('/categories', [$productController, 'categories']);

        // Carrito
        $api->get('/cart/{sessionId}', [$cartController, 'show']);
        $api->post('/cart/{sessionId}/items', [$cartController, 'addItem']);
        $api->delete('/cart/{sessionId}/items/{productId:[0-9]+}', [$cartController, 'removeItem']);

        // Agente SofIA (SSE) - protegido por rate limiting
        $api->post('/chat/message', [$chatController, 'message'])
            ->add(new RateLimitingMiddleware('chat'));

        // Checkout Culqi - protegido por rate limiting
        $api->post('/checkout', [$paymentController, 'checkout'])
            ->add(new RateLimitingMiddleware('checkout'));

        // Formulario de contacto web
        $api->post('/contact', [$contactController, 'send'])
            ->add(new RateLimitingMiddleware('contact', 10));

        // Webhook de Culqi (validacion de firma + confirmacion server-to-server)
        $api->post('/webhooks/culqi', [$webhookController, 'culqi']);
    });

    // ---------- API de administracion ----------
    $app->post('/api/admin/login', [$authController, 'login'])
        ->add(new RateLimitingMiddleware('admin-login', 10));

    $app->group('/api/admin', function (RouteCollectorProxy $admin) use ($adminController): void {
        $admin->get('/products', [$adminController, 'products']);
        $admin->post('/products', [$adminController, 'storeProduct']);
        $admin->put('/products/{id:[0-9]+}', [$adminController, 'updateProduct']);
        $admin->delete('/products/{id:[0-9]+}', [$adminController, 'deleteProduct']);
        $admin->get('/categories', [$adminController, 'categories']);
        $admin->get('/orders', [$adminController, 'orders']);
        $admin->get('/stats', [$adminController, 'stats']);
    })->add(new AdminAuthMiddleware($tokens));
};
