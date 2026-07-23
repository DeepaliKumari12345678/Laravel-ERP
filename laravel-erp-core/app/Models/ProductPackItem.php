<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPackItem extends Model
{
    protected $fillable = [
        'pack_product_id',
        'item_product_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
        ];
    }

    public function pack(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'pack_product_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item_product_id');
    }
}
