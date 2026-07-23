<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttributeGroup extends Model
{
    protected $fillable = [
        'name',
        'public_name',
        'type',
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
        return $this->hasMany(AttributeValue::class)->orderBy('position')->orderBy('name');
    }

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return ['select', 'radio', 'color'];
    }

    /**
     * @return array<string, string>
     */
    public static function typeLabels(): array
    {
        return [
            'select' => 'Drop-down list',
            'radio' => 'Radio buttons',
            'color' => 'Color or texture',
        ];
    }

    public function typeLabel(): string
    {
        return self::typeLabels()[$this->type] ?? ucfirst((string) $this->type);
    }
}
