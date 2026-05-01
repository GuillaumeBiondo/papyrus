<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Annotation extends Model
{
    use HasUuids;

    protected $fillable = [
        'scene_id',
        'user_id',
        'anchor_start',
        'anchor_end',
        'body',
        'type',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'anchor_start' => 'integer',
            'anchor_end'   => 'integer',
        ];
    }

    public function scene(): BelongsTo
    {
        return $this->belongsTo(Scene::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cards(): BelongsToMany
    {
        return $this->belongsToMany(Card::class, 'annotation_cards');
    }
}
