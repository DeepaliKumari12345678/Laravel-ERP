<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRateRange extends Model
{
    protected $fillable = [
        'shipping_carrier_id',
        'from_value',
        'to_value',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'from_value' => 'decimal:2',
            'to_value' => 'decimal:2',
            'price' => 'decimal:2',
        ];
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(ShippingCarrier::class, 'shipping_carrier_id');
    }
}
