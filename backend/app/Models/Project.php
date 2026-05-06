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

class Project extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'content_type_id',
        'title',
        'genre',
        'color',
        'target_words',
        'target_scenes',
        'status',
        'type_data',
    ];

    protected function casts(): array
    {
        return [
            'target_words'  => 'integer',
            'target_scenes' => 'integer',
            'type_data'     => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function contentType(): BelongsTo
    {
        return $this->belongsTo(ContentType::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_users')
            ->withPivot('role');
    }

    public function arcs(): HasMany
    {
        return $this->hasMany(Arc::class)->orderBy('order');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }

    public function notebookEntries(): HasMany
    {
        return $this->hasMany(NotebookEntry::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable')->latest();
    }
}
