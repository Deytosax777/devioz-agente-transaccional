<?php

declare(strict_types=1);

namespace Devioz\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $table = 'carts';

    protected $fillable = ['session_id', 'status'];

    public const STATUS_OPEN        = 'open';
    public const STATUS_CHECKED_OUT = 'checked_out';
    public const STATUS_ABANDONED   = 'abandoned';

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public static function openForSession(string $sessionId): self
    {
        return static::firstOrCreate(
            ['session_id' => $sessionId, 'status' => self::STATUS_OPEN]
        );
    }

    /** Total del carrito en Soles, calculado siempre desde la BD. */
    public function total(): float
    {
        return (float) $this->items->sum(
            fn (CartItem $item) => (float) $item->unit_price * $item->quantity
        );
    }

    /** Resumen serializable para el widget y para el agente IA. */
    public function toSummary(): array
    {
        $this->load('items.product');

        return [
            'items' => $this->items->map(fn (CartItem $item) => [
                'product_id' => $item->product_id,
                'name'       => $item->product?->name ?? 'Producto',
                'quantity'   => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'subtotal'   => round((float) $item->unit_price * $item->quantity, 2),
            ])->values()->all(),
            'total'    => round($this->total(), 2),
            'currency' => 'PEN',
            'count'    => (int) $this->items->sum('quantity'),
        ];
    }
}
