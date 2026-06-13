<?php

declare(strict_types=1);

namespace Devioz\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $table = 'conversations';

    protected $fillable = ['session_id'];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('id');
    }

    public static function findOrCreateBySession(string $sessionId): self
    {
        return static::firstOrCreate(['session_id' => $sessionId]);
    }
}
