<?php

declare(strict_types=1);

namespace Devioz\Controllers;

use Devioz\Models\Cart;
use Devioz\Models\CartItem;
use Devioz\Models\Product;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * API REST del carrito (complementa las herramientas del agente:
 * el widget tambien puede mutar el carrito directamente desde la UI).
 */
class CartController
{
    private const MAX_QTY = 10;

    /** GET /api/cart/{sessionId} */
    public function show(Request $request, Response $response, array $args): Response
    {
        $cart = Cart::openForSession($this->sessionId($args));

        return $this->json($response, ['cart' => $cart->toSummary()]);
    }

    /** POST /api/cart/{sessionId}/items  {product_id, quantity} */
    public function addItem(Request $request, Response $response, array $args): Response
    {
        $body      = (array) $request->getParsedBody();
        $productId = (int) ($body['product_id'] ?? 0);
        $quantity  = max(1, min(self::MAX_QTY, (int) ($body['quantity'] ?? 1)));

        $product = Product::where('active', true)->find($productId);

        if ($product === null) {
            return $this->json($response, ['error' => 'not_found', 'message' => 'Producto no encontrado'], 404);
        }

        if ($product->isQuoteOnly()) {
            return $this->json($response, [
                'error'   => 'quote_only',
                'message' => 'Este producto se vende solo por cotización. Contáctanos por WhatsApp.',
            ], 422);
        }

        $cart = Cart::openForSession($this->sessionId($args));

        $item = CartItem::firstOrNew(['cart_id' => $cart->id, 'product_id' => $product->id]);
        $item->quantity   = min(self::MAX_QTY, ($item->exists ? $item->quantity : 0) + $quantity);
        $item->unit_price = $product->price_offer;
        $item->save();

        $cart->refresh();

        return $this->json($response, ['cart' => $cart->toSummary()]);
    }

    /** DELETE /api/cart/{sessionId}/items/{productId} */
    public function removeItem(Request $request, Response $response, array $args): Response
    {
        $cart = Cart::openForSession($this->sessionId($args));

        CartItem::where('cart_id', $cart->id)
            ->where('product_id', (int) ($args['productId'] ?? 0))
            ->delete();

        $cart->refresh();

        return $this->json($response, ['cart' => $cart->toSummary()]);
    }

    private function sessionId(array $args): string
    {
        return substr(preg_replace('/[^a-zA-Z0-9\-_]/', '', (string) ($args['sessionId'] ?? '')) ?? '', 0, 64);
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write((string) json_encode($data, JSON_UNESCAPED_UNICODE));

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
