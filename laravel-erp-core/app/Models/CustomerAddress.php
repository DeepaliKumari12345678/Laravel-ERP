<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    protected $fillable = [
        'customer_id',
        'alias',
        'first_name',
        'last_name',
        'company',
        'dni',
        'vat_number',
        'address1',
        'address2',
        'postcode',
        'city',
        'state',
        'country',
        'phone',
        'phone_mobile',
        'other',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '').' '.($this->last_name ?? ''))
            ?: ($this->customer?->full_name ?? '—');
    }

    public function getDisplayLabelAttribute(): string
    {
        return $this->alias ?: $this->full_name;
    }
}
