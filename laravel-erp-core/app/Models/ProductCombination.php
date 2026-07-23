<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductCombination extends Model
{
    protected $fillable = [
        'product_id',
        'reference',
        'quantity',
        'price_impact',
        'active',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'price_impact' => 'decimal:2',
            'active' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeValue::class,
            'product_combination_values',
            'product_combination_id',
            'attribute_value_id'
        )->withTimestamps();
    }

    public function label(): string
    {
        $parts = $this->attributeValues
            ->sortBy(fn (AttributeValue $value) => $value->group?->position ?? 0)
            ->map(fn (AttributeValue $value) => ($value->group?->public_name ?: $value->group?->name).': '.$value->name);

        return $parts->isNotEmpty() ? $parts->implode(' / ') : ($this->reference ?: 'Combination #'.$this->id);
    }
}
