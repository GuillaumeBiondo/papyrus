<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Todo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chapter extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'arc_id',
        'title',
        'summary',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    public function arc(): BelongsTo
    {
        return $this->belongsTo(Arc::class);
    }

    public function project()
    {
        return $this->arc->project();
    }

    public function scenes(): HasMany
    {
        return $this->hasMany(Scene::class)->orderBy('order');
    }

    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class)->orderBy('sort_order');
    }
}
