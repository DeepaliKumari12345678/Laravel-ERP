<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerGroup extends Model
{
    protected $fillable = [
        'name',
        'discount_percent',
        'show_prices',
        'price_display_method',
        'description',
        'meta',
        'active',
        'is_system',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'discount_percent' => 'decimal:2',
            'show_prices' => 'boolean',
            'active' => 'boolean',
            'is_system' => 'boolean',
            'position' => 'integer',
            'meta' => 'array',
        ];
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function metaValue(string $key, mixed $default = null): mixed
    {
        $meta = is_array($this->meta) ? $this->meta : [];

        return $meta[$key] ?? $default;
    }
}
