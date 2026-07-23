<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ShippingCarrier extends Model
{
    protected $fillable = [
        'name',
        'delay',
        'logo_path',
        'tracking_url',
        'speed_grade',
        'price',
        'currency',
        'billing_basis',
        'free_shipping',
        'apply_handling_cost',
        'tax_rate',
        'out_of_range_behavior',
        'country_codes',
        'max_width',
        'max_height',
        'max_depth',
        'max_weight',
        'active',
        'position',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'speed_grade' => 'integer',
            'free_shipping' => 'boolean',
            'apply_handling_cost' => 'boolean',
            'tax_rate' => 'decimal:2',
            'country_codes' => 'array',
            'max_width' => 'decimal:2',
            'max_height' => 'decimal:2',
            'max_depth' => 'decimal:2',
            'max_weight' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    public function rateRanges(): HasMany
    {
        return $this->hasMany(ShippingRateRange::class)->orderBy('from_value');
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? asset('storage/'.$this->logo_path) : null;
    }

    public function trackingUrlFor(?string $trackingNumber): ?string
    {
        if (! $this->tracking_url || ! $trackingNumber) {
            return null;
        }

        return str_replace('@', rawurlencode($trackingNumber), $this->tracking_url);
    }

    public function deleteLogo(): void
    {
        if ($this->logo_path) {
            Storage::disk('public')->delete($this->logo_path);
        }
    }
}
