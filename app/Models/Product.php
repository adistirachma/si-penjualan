<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'variasi',
        'stock',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:0',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function forecasts(): HasMany
    {
        return $this->hasMany(Forecast::class);
    }
}
