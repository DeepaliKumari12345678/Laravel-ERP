<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureValue extends Model
{
    protected $fillable = [
        'feature_id',
        'value',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }
}
