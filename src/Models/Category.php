<?php

declare(strict_types=1);

namespace Devioz\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'categories';

    public $timestamps = false;

    protected $fillable = ['name', 'slug', 'description', 'icon'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
