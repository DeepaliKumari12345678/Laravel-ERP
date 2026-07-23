<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feature extends Model
{
    protected $fillable = [
        'name',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(FeatureValue::class)->orderBy('position')->orderBy('value');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'feature_product')
            ->withPivot('feature_value_id')
            ->withTimestamps();
    }
}
