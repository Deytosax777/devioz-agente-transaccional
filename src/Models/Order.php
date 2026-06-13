<?php

declare(strict_types=1);

namespace Devioz\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'code',
        'session_id',
        'customer_email',
        'customer_name',
        'total',
        'currency',
        'status',
        'culqi_charge_id',
        'paid_at',
    ];

    protected $casts = [
        'total'   => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public const STATUS_PENDING  = 'pending';
    public const STATUS_PAID     = 'paid';
    public const STATUS_FAILED   = 'failed';
    public const STATUS_REFUNDED = 'refunded';

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** Monto en centimos de Sol, formato requerido por Culqi. */
    public function amountInCents(): int
    {
        return (int) round(((float) $this->total) * 100);
    }

    public static function generateCode(): string
    {
        do {
            $code = 'DVZ-' . strtoupper(bin2hex(random_bytes(4)));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function toSummary(): array
    {
        $this->loadMissing('items');

        return [
            'code'     => $this->code,
            'status'   => $this->status,
            'total'    => (float) $this->total,
            'currency' => $this->currency,
            'items'    => $this->items->map(fn (OrderItem $item) => [
                'name'       => $item->product_name,
                'quantity'   => $item->quantity,
                'unit_price' => (float) $item->unit_price,
            ])->values()->all(),
        ];
    }
}
