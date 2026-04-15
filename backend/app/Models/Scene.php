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

class Scene extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'chapter_id',
        'title',
        'content',
        'status',
        'order',
        'word_count',
    ];

    protected function casts(): array
    {
        return [
            'order'      => 'integer',
            'word_count' => 'integer',
        ];
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function cards(): BelongsToMany
    {
        return $this->belongsToMany(Card::class, 'scene_cards');
    }

    public function annotations(): HasMany
    {
        return $this->hasMany(Annotation::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    public function keywordOccurrences(): HasMany
    {
        return $this->hasMany(KeywordOccurrence::class);
    }
}
