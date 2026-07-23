<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'user_id',
        'customer_code',
        'customer_group_id',
        'type',
        'social_title',
        'first_name',
        'last_name',
        'company',
        'email',
        'phone',
        'birthday',
        'address',
        'city',
        'state',
        'postcode',
        'country',
        'note',
        'active',
        'newsletter',
        'partner_offers',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'newsletter' => 'boolean',
            'partner_offers' => 'boolean',
            'birthday' => 'date',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birthday?->age;
    }

    public function totalSpent(): float
    {
        $cancelled = OrderStatus::query()->where('is_cancelled', true)->pluck('code');

        return (float) $this->orders()->whereNotIn('status', $cancelled)->sum('total');
    }

    public function validatedOrdersCount(): int
    {
        $validated = OrderStatus::query()->where('counts_as_validated', true)->pluck('code');

        return $this->orders()->whereIn('status', $validated)->count();
    }
}
