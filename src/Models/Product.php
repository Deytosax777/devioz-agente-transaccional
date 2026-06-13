<?php

declare(strict_types=1);

namespace Devioz\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'tier',
        'price_offer',
        'currency',
        'active',
    ];

    protected $casts = [
        'price_offer' => 'decimal:2',
        'active'      => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Un producto sin price_offer se vende solo via cotizacion. */
    public function isQuoteOnly(): bool
    {
        return $this->price_offer === null;
    }

    /** Precio formateado en Soles o etiqueta de cotizacion. */
    public function displayPrice(): string
    {
        return $this->isQuoteOnly()
            ? 'A cotizar'
            : 'S/ ' . number_format((float) $this->price_offer, 2);
    }

    /** Representacion compacta para el contexto del agente IA. */
    public function toAgentArray(): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'category' => $this->category?->name,
            'tier'     => $this->tier,
            'price'    => $this->isQuoteOnly() ? null : (float) $this->price_offer,
            'price_label' => $this->displayPrice(),
            'quote_only'  => $this->isQuoteOnly(),
        ];
    }
}
