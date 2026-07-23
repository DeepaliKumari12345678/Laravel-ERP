<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeValue extends Model
{
    protected $fillable = [
        'attribute_group_id',
        'name',
        'color',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(AttributeGroup::class, 'attribute_group_id');
    }
}
