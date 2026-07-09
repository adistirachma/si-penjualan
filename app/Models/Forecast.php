<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Forecast extends Model
{
    protected $fillable = [
        'product_id',
        'start_month',
        'end_month',
        'alpha',
        'beta',
        'phi',
        'periods',
        'actual_count',
        'forecast_values',
        'mape',
        'mae',
        'rmse',
    ];

    protected $casts = [
        'forecast_values' => 'array',
        'alpha' => 'float',
        'beta' => 'float',
        'phi' => 'float',
        'mape' => 'float',
        'mae' => 'float',
        'rmse' => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
