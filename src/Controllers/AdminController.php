<?php

declare(strict_types=1);

namespace Devioz\Controllers;

use Devioz\Models\Category;
use Devioz\Models\Conversation;
use Devioz\Models\Order;
use Devioz\Models\Product;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Endpoints del panel de administracion (protegidos por AdminAuthMiddleware):
 * gestion de productos, ordenes/transacciones y metricas basicas.
 */
class AdminController
{
    private const TIERS = ['Básico', 'Pro', 'Premium', 'Enterprise'];

    // ------------------------- Productos -------------------------

    /** GET /api/admin/products — incluye inactivos. */
    public function products(Request $request, Response $response): Response
    {
        $products = Product::with('category')->orderBy('category_id')->orderBy('name')->get();

        return $this->json($response, [
            'products' => $products->map(fn (Product $p) => $this->serializeProduct($p))->all(),
        ]);
    }

    /** GET /api/admin/categories */
    public function categories(Request $request, Response $response): Response
    {
        return $this->json($response, [
            'categories' => Category::orderBy('id')->get(['id', 'name', 'slug'])->all(),
        ]);
    }

    /** POST /api/admin/products */
    public function storeProduct(Request $request, Response $response): Response
    {
        $data  = (array) $request->getParsedBody();
        $error = $this->validateProduct($data, null);
        if ($error !== null) {
            return $this->json($response, ['error' => 'validation', 'message' => $error], 422);
        }

        $product = Product::create([
            'category_id' => (int) $data['category_id'],
            'name'        => trim((string) $data['name']),
            'slug'        => $this->uniqueSlug((string) $data['name']),
            'description' => trim((string) ($data['description'] ?? '')),
            'tier'        => $data['tier'],
            'price_offer' => $this->normalizePrice($data),
            'currency'    => 'PEN',
            'active'      => !empty($data['active']),
        ]);

        return $this->json($response, ['product' => $this->serializeProduct($product->load('category'))], 201);
    }

    /** PUT /api/admin/products/{id} */
    public function updateProduct(Request $request, Response $response, array $args): Response
    {
        $product = Product::find((int) ($args['id'] ?? 0));
        if ($product === null) {
            return $this->json($response, ['error' => 'not_found', 'message' => 'Producto no encontrado'], 404);
        }

        $data  = (array) $request->getParsedBody();
        $error = $this->validateProduct($data, $product->id);
        if ($error !== null) {
            return $this->json($response, ['error' => 'validation', 'message' => $error], 422);
        }

        $product->update([
            'category_id' => (int) $data['category_id'],
            'name'        => trim((string) $data['name']),
            'description' => trim((string) ($data['description'] ?? '')),
            'tier'        => $data['tier'],
            'price_offer' => $this->normalizePrice($data),
            'active'      => !empty($data['active']),
        ]);

        return $this->json($response, ['product' => $this->serializeProduct($product->load('category'))]);
    }

    /** DELETE /api/admin/products/{id} — borrado logico (active = 0). */
    public function deleteProduct(Request $request, Response $response, array $args): Response
    {
        $product = Product::find((int) ($args['id'] ?? 0));
        if ($product === null) {
            return $this->json($response, ['error' => 'not_found', 'message' => 'Producto no encontrado'], 404);
        }

        $product->update(['active' => false]);

        return $this->json($response, ['ok' => true, 'message' => 'Producto desactivado.']);
    }

    // ------------------------- Ordenes -------------------------

    /** GET /api/admin/orders?status=paid */
    public function orders(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $query = Order::with('items')->orderByDesc('id');

        if (!empty($params['status'])) {
            $query->where('status', (string) $params['status']);
        }

        $orders = $query->limit(200)->get();

        return $this->json($response, [
            'orders' => $orders->map(fn (Order $o) => [
                'id'              => $o->id,
                'code'            => $o->code,
                'customer_email'  => $o->customer_email,
                'customer_name'   => $o->customer_name,
                'total'           => (float) $o->total,
                'currency'        => $o->currency,
                'status'          => $o->status,
                'culqi_charge_id' => $o->culqi_charge_id,
                'paid_at'         => $o->paid_at?->format('Y-m-d H:i'),
                'created_at'      => $o->created_at?->format('Y-m-d H:i'),
                'items'           => $o->items->map(fn ($i) => [
                    'name'       => $i->product_name,
                    'quantity'   => $i->quantity,
                    'unit_price' => (float) $i->unit_price,
                ])->all(),
            ])->all(),
        ]);
    }

    /** GET /api/admin/stats — metricas del dashboard. */
    public function stats(Request $request, Response $response): Response
    {
        $paid = Order::where('status', Order::STATUS_PAID);

        $topProducts = Capsule::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', Order::STATUS_PAID)
            ->groupBy('order_items.product_name')
            ->selectRaw('order_items.product_name AS name, SUM(order_items.quantity) AS sold, SUM(order_items.unit_price * order_items.quantity) AS revenue')
            ->orderByDesc('sold')
            ->limit(5)
            ->get();

        return $this->json($response, [
            'revenue'        => round((float) $paid->clone()->sum('total'), 2),
            'orders_paid'    => $paid->clone()->count(),
            'orders_pending' => Order::where('status', Order::STATUS_PENDING)->count(),
            'orders_failed'  => Order::where('status', Order::STATUS_FAILED)->count(),
            'conversations'  => Conversation::count(),
            'products_active' => Product::where('active', true)->count(),
            'top_products'   => $topProducts,
            'currency'       => 'PEN',
        ]);
    }

    // ------------------------- Helpers -------------------------

    private function validateProduct(array $data, ?int $ignoreId): ?string
    {
        if (trim((string) ($data['name'] ?? '')) === '') {
            return 'El nombre es obligatorio.';
        }
        if (mb_strlen((string) $data['name']) > 160) {
            return 'El nombre no puede superar 160 caracteres.';
        }
        if (!in_array($data['tier'] ?? '', self::TIERS, true)) {
            return 'Tier inválido. Valores permitidos: ' . implode(', ', self::TIERS) . '.';
        }
        if (Category::find((int) ($data['category_id'] ?? 0)) === null) {
            return 'La categoría seleccionada no existe.';
        }

        $price = $data['price_offer'] ?? null;
        if ($price !== null && $price !== '' && (!is_numeric($price) || (float) $price < 0)) {
            return 'El precio debe ser un número positivo o vacío (a cotizar).';
        }

        return null;
    }

    private function normalizePrice(array $data): ?float
    {
        $price = $data['price_offer'] ?? null;

        if ($price === null || $price === '' || !empty($data['quote_only'])) {
            return null;
        }

        return round((float) $price, 2);
    }

    private function uniqueSlug(string $name): string
    {
        $base = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $name) ?: $name), '-'));
        $base = $base !== '' ? $base : 'producto';

        $slug = $base;
        $i    = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }

    private function serializeProduct(Product $p): array
    {
        return [
            'id'          => $p->id,
            'category_id' => $p->category_id,
            'category'    => $p->category?->name,
            'name'        => $p->name,
            'slug'        => $p->slug,
            'description' => $p->description,
            'tier'        => $p->tier,
            'price_offer' => $p->isQuoteOnly() ? null : (float) $p->price_offer,
            'price_label' => $p->displayPrice(),
            'active'      => (bool) $p->active,
        ];
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write((string) json_encode($data, JSON_UNESCAPED_UNICODE));

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
