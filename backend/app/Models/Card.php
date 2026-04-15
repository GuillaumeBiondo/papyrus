<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Card extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'project_id',
        'type',
        'title',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(CardAttribute::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(CardLink::class, 'card_id');
    }

    public function linkedBy(): HasMany
    {
        return $this->hasMany(CardLink::class, 'linked_card_id');
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(CardKeyword::class);
    }

    public function scenes(): BelongsToMany
    {
        return $this->belongsToMany(Scene::class, 'scene_cards');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }
}
