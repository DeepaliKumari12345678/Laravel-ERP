<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'reference',
        'customer_id',
        'employee_id',
        'status',
        'subtotal',
        'tax_total',
        'discount_total',
        'total',
        'currency',
        'payment_method',
        'shipping_carrier_id',
        'shipping_carrier_name',
        'shipping_cost',
        'shipping_tax',
        'shipping_weight',
        'delivery_country',
        'notes',
        'ordered_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'total' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'shipping_tax' => 'decimal:2',
            'shipping_weight' => 'decimal:3',
            'ordered_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shippingCarrier(): BelongsTo
    {
        return $this->belongsTo(ShippingCarrier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function creditSlips(): HasMany
    {
        return $this->hasMany(CreditSlip::class);
    }

    public function deliverySlips(): HasMany
    {
        return $this->hasMany(DeliverySlip::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class)->latest();
    }
}
