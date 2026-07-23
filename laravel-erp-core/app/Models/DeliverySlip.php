<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliverySlip extends Model
{
    protected $fillable = [
        'number',
        'order_id',
        'customer_id',
        'carrier',
        'tracking_number',
        'status',
        'shipped_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'shipped_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
