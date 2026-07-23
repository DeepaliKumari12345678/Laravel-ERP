<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandAddress extends Model
{
    protected $fillable = [
        'brand_id',
        'first_name',
        'last_name',
        'address1',
        'address2',
        'postcode',
        'city',
        'state',
        'country',
        'phone',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '').' '.($this->last_name ?? '')) ?: '—';
    }
}
