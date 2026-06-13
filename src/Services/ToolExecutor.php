<?php

declare(strict_types=1);

namespace Devioz\Services;

use Devioz\Models\Cart;
use Devioz\Models\CartItem;
use Devioz\Models\Order;
use Devioz\Models\OrderItem;
use Devioz\Models\Product;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Define el JSON Schema de las herramientas que Llama 3 puede invocar
 * y las ejecuta contra la base de datos de forma segura.
 *
 * Cada ejecucion devuelve:
 *  - 'model': resultado serializable que se devuelve al LLM (rol tool).
 *  - 'event': evento SSE opcional ['name' => ..., 'data' => ...] para la UI del widget.
 */
class ToolExecutor
{
    private const MAX_QTY = 10;

    /** JSON Schema exacto de las herramientas disponibles para Groq. */
    public static function definitions(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'get_catalog',
                    'description' => 'Consulta el catálogo real de productos de Devioz con precios vigentes en Soles (PEN). Úsala SIEMPRE antes de mencionar precios o productos.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'category' => [
                                'type'        => 'string',
                                'description' => 'Filtrar por categoría: Diseño Gráfico, Spots Publicitarios, Business Intelligence, Inteligencia Artificial o Desarrollo Web.',
                            ],
                            'search' => [
                                'type'        => 'string',
                                'description' => 'Texto libre para buscar por nombre de producto.',
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'add_to_cart',
                    'description' => 'Agrega un producto al carrito de compras del cliente. Requiere el product_id obtenido con get_catalog.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'product_id' => [
                                'type'        => 'integer',
                                'description' => 'ID numérico del producto en el catálogo.',
                            ],
                            'quantity' => [
                                'type'        => 'integer',
                                'description' => 'Cantidad de unidades (1 a 10). Por defecto 1.',
                            ],
                        ],
                        'required' => ['product_id'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'remove_from_cart',
                    'description' => 'Elimina un producto del carrito del cliente.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'product_id' => [
                                'type'        => 'integer',
                                'description' => 'ID del producto a quitar del carrito.',
                            ],
                        ],
                        'required' => ['product_id'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'get_cart',
                    'description' => 'Muestra el contenido y total actual del carrito del cliente.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => new \stdClass(),
                        'required'   => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'generate_checkout',
                    'description' => 'Genera la orden de pago y abre la pasarela Culqi (tarjetas y Yape, en Soles) para que el cliente pague el carrito. Úsala solo cuando el cliente confirme que desea pagar.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => new \stdClass(),
                        'required'   => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name'        => 'human_handoff',
                    'description' => 'Deriva la conversación a un asesor humano de Devioz vía WhatsApp. Úsala para cotizaciones de productos sin precio, proyectos a medida o cuando el cliente pida hablar con una persona.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'reason' => [
                                'type'        => 'string',
                                'description' => 'Motivo breve del contacto, se incluirá en el mensaje de WhatsApp.',
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],
        ];
    }

    /**
     * Ejecuta una herramienta solicitada por el modelo.
     *
     * @return array{model: array, event: ?array}
     */
    public function execute(string $name, array $args, string $sessionId): array
    {
        return match ($name) {
            'get_catalog'       => $this->getCatalog($args),
            'add_to_cart'       => $this->addToCart($args, $sessionId),
            'remove_from_cart'  => $this->removeFromCart($args, $sessionId),
            'get_cart'          => $this->getCart($sessionId),
            'generate_checkout' => $this->generateCheckout($sessionId),
            'human_handoff'     => $this->humanHandoff($args),
            default             => [
                'model' => ['error' => "Herramienta desconocida: {$name}"],
                'event' => null,
            ],
        };
    }

    private function getCatalog(array $args): array
    {
        $query = Product::query()->with('category')->where('active', true);

        if (!empty($args['category'])) {
            $term = '%' . trim((string) $args['category']) . '%';
            $query->whereHas('category', function ($q) use ($term) {
                $q->where('name', 'like', $term)->orWhere('slug', 'like', $term);
            });
        }

        if (!empty($args['search'])) {
            $query->where('name', 'like', '%' . trim((string) $args['search']) . '%');
        }

        $products = $query->orderBy('category_id')->orderBy('price_offer')->get();

        if ($products->isEmpty()) {
            return [
                'model' => [
                    'products' => [],
                    'note'     => 'No se encontraron productos con ese filtro. Prueba sin filtros o con otra categoría.',
                ],
                'event' => null,
            ];
        }

        return [
            'model' => [
                'currency' => 'PEN',
                'products' => $products->map(fn (Product $p) => $p->toAgentArray())->all(),
            ],
            'event' => [
                'name' => 'catalog',
                'data' => [
                    'products' => $products->map(fn (Product $p) => [
                        'id'          => $p->id,
                        'name'        => $p->name,
                        'category'    => $p->category?->name,
                        'tier'        => $p->tier,
                        'description' => $p->description,
                        'price'       => $p->isQuoteOnly() ? null : (float) $p->price_offer,
                        'price_label' => $p->displayPrice(),
                        'quote_only'  => $p->isQuoteOnly(),
                    ])->values()->all(),
                ],
            ],
        ];
    }

    private function addToCart(array $args, string $sessionId): array
    {
        $productId = (int) ($args['product_id'] ?? 0);
        $quantity  = max(1, min(self::MAX_QTY, (int) ($args['quantity'] ?? 1)));

        $product = Product::with('category')->where('active', true)->find($productId);

        if ($product === null) {
            return [
                'model' => ['error' => "No existe un producto activo con id {$productId}. Verifica el catálogo con get_catalog."],
                'event' => null,
            ];
        }

        if ($product->isQuoteOnly()) {
            return [
                'model' => [
                    'error' => "El producto '{$product->name}' no tiene precio fijo: es solo a cotizar. Ofrece al cliente derivarlo con un asesor usando human_handoff.",
                ],
                'event' => null,
            ];
        }

        $cart = Cart::openForSession($sessionId);

        $item = CartItem::firstOrNew([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
        ]);
        $item->quantity   = min(self::MAX_QTY, ($item->exists ? $item->quantity : 0) + $quantity);
        $item->unit_price = $product->price_offer;
        $item->save();

        $cart->refresh();
        $summary = $cart->toSummary();

        return [
            'model' => [
                'added'   => ['product' => $product->name, 'quantity' => $quantity],
                'cart'    => $summary,
                'message' => 'Producto agregado correctamente.',
            ],
            'event' => ['name' => 'cart', 'data' => ['cart' => $summary]],
        ];
    }

    private function removeFromCart(array $args, string $sessionId): array
    {
        $productId = (int) ($args['product_id'] ?? 0);
        $cart      = Cart::openForSession($sessionId);

        $deleted = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->delete();

        $cart->refresh();
        $summary = $cart->toSummary();

        return [
            'model' => [
                'removed' => $deleted > 0,
                'cart'    => $summary,
            ],
            'event' => ['name' => 'cart', 'data' => ['cart' => $summary]],
        ];
    }

    private function getCart(string $sessionId): array
    {
        $summary = Cart::openForSession($sessionId)->toSummary();

        return [
            'model' => ['cart' => $summary],
            'event' => ['name' => 'cart', 'data' => ['cart' => $summary]],
        ];
    }

    private function generateCheckout(string $sessionId): array
    {
        $cart = Cart::openForSession($sessionId);
        $cart->load('items.product');

        if ($cart->items->isEmpty()) {
            return [
                'model' => ['error' => 'El carrito está vacío. Agrega productos con add_to_cart antes de generar el pago.'],
                'event' => null,
            ];
        }

        // Transaccion ACID: la orden y sus items se crean de forma atomica
        $order = Capsule::connection()->transaction(function () use ($cart, $sessionId) {
            $order = Order::create([
                'code'       => Order::generateCode(),
                'session_id' => $sessionId,
                'total'      => round($cart->total(), 2),
                'currency'   => 'PEN',
                'status'     => Order::STATUS_PENDING,
            ]);

            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $item->product_id,
                    'product_name' => $item->product?->name ?? 'Producto Devioz',
                    'unit_price'   => $item->unit_price,
                    'quantity'     => $item->quantity,
                ]);
            }

            return $order;
        });

        $summary     = $order->toSummary();
        $description = 'Devioz - Orden ' . $order->code;

        return [
            'model' => [
                'order'   => $summary,
                'message' => 'Orden generada. La pasarela de pago Culqi se abrirá en el chat para que el cliente complete el pago con tarjeta o Yape.',
            ],
            'event' => [
                'name' => 'checkout',
                'data' => [
                    'order'        => $summary,
                    'amount_cents' => $order->amountInCents(),
                    'currency'     => 'PEN',
                    'description'  => $description,
                ],
            ],
        ];
    }

    private function humanHandoff(array $args): array
    {
        $number = preg_replace('/\D+/', '', (string) env('WHATSAPP_NUMBER', '51999999999'));
        $reason = trim((string) ($args['reason'] ?? 'Quiero hablar con un asesor de Devioz'));
        $text   = rawurlencode('Hola Devioz 👋. Vengo del chat con SofIA. ' . $reason);
        $url    = "https://wa.me/{$number}?text={$text}";

        return [
            'model' => [
                'handoff_url' => $url,
                'message'     => 'Comparte este enlace de WhatsApp con el cliente para que un asesor humano lo atienda.',
            ],
            'event' => ['name' => 'handoff', 'data' => ['url' => $url]],
        ];
    }
}
