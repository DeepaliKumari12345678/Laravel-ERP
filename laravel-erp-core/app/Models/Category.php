<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'cover_image_path',
        'thumbnail_path',
        'meta',
        'active',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        return $this->cover_image_path ? asset('storage/'.$this->cover_image_path) : null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail_path ? asset('storage/'.$this->thumbnail_path) : null;
    }

    public function metaValue(string $key, mixed $default = null): mixed
    {
        $meta = is_array($this->meta) ? $this->meta : [];

        return $meta[$key] ?? $default;
    }
}
