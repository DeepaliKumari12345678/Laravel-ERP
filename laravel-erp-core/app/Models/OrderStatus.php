<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderStatus extends Model
{
    protected $fillable = [
        'code',
        'name',
        'color',
        'send_email',
        'is_paid',
        'is_shipped',
        'is_delivered',
        'is_cancelled',
        'counts_as_validated',
        'active',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'send_email' => 'boolean',
            'is_paid' => 'boolean',
            'is_shipped' => 'boolean',
            'is_delivered' => 'boolean',
            'is_cancelled' => 'boolean',
            'counts_as_validated' => 'boolean',
            'active' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'status', 'code');
    }
}
