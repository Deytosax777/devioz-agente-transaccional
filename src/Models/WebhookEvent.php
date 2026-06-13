<?php

declare(strict_types=1);

namespace Devioz\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    protected $table = 'webhook_events';

    public const UPDATED_AT = null;

    protected $fillable = [
        'provider',
        'event_type',
        'external_id',
        'payload',
        'signature_valid',
        'processed',
        'notes',
    ];

    protected $casts = [
        'signature_valid' => 'boolean',
        'processed'       => 'boolean',
    ];
}
