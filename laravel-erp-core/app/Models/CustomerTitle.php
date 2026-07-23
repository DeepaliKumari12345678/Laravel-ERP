<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerTitle extends Model
{
    protected $fillable = [
        'name',
        'gender',
        'image_path',
        'image_width',
        'image_height',
        'active',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'position' => 'integer',
            'image_width' => 'integer',
            'image_height' => 'integer',
        ];
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }
}
