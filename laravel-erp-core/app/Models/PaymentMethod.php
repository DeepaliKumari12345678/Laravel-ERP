<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'active',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }
}
