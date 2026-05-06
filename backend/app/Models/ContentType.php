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
        'slug',
        'is_active',
        'type_schema',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_active'   => 'boolean',
            'type_schema' => 'array',
        ];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
