<?php

declare(strict_types=1);

namespace Devioz\Controllers;

use Devioz\Models\Category;
use Devioz\Models\Product;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/** Catalogo publico de productos para la web y el widget. */
class ProductController
{
    /** GET /api/products?category=slug&search=texto */
    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $query = Product::query()->with('category')->where('active', true);

        if (!empty($params['category'])) {
            $term = trim((string) $params['category']);
            $query->whereHas('category', function ($q) use ($term) {
                $q->where('slug', $term)->orWhere('name', 'like', "%{$term}%");
            });
        }

        if (!empty($params['search'])) {
            $query->where('name', 'like', '%' . trim((string) $params['search']) . '%');
        }

        $products = $query->orderBy('category_id')->orderBy('price_offer')->get();

        return $this->json($response, [
            'currency' => 'PEN',
            'products' => $products->map(fn (Product $p) => $this->serialize($p))->all(),
        ]);
    }

    /** GET /api/products/{id} */
    public function show(Request $request, Response $response, array $args): Response
    {
        $product = Product::with('category')
            ->where('active', true)
            ->find((int) ($args['id'] ?? 0));

        if ($product === null) {
            return $this->json($response, ['error' => 'not_found', 'message' => 'Producto no encontrado'], 404);
        }

        return $this->json($response, ['product' => $this->serialize($product)]);
    }

    /** GET /api/categories */
    public function categories(Request $request, Response $response): Response
    {
        $categories = Category::query()
            ->withCount(['products' => fn ($q) => $q->where('active', true)])
            ->orderBy('id')
            ->get();

        return $this->json($response, [
            'categories' => $categories->map(fn (Category $c) => [
                'id'             => $c->id,
                'name'           => $c->name,
                'slug'           => $c->slug,
                'description'    => $c->description,
                'icon'           => $c->icon,
                'products_count' => $c->products_count,
            ])->all(),
        ]);
    }

    private function serialize(Product $p): array
    {
        return [
            'id'          => $p->id,
            'name'        => $p->name,
            'slug'        => $p->slug,
            'description' => $p->description,
            'category'    => $p->category?->name,
            'category_slug' => $p->category?->slug,
            'tier'        => $p->tier,
            'price'       => $p->isQuoteOnly() ? null : (float) $p->price_offer,
            'price_label' => $p->displayPrice(),
            'quote_only'  => $p->isQuoteOnly(),
            'currency'    => $p->currency,
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
