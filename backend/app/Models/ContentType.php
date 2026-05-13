<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentType extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'short_name',
        'slug',
        'is_active',
        'is_premium',
        'type_schema',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_active'   => 'boolean',
            'is_premium'  => 'boolean',
            'type_schema' => 'array',
        ];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
