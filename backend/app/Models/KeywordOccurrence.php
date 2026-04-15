<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeywordOccurrence extends Model
{
    public $timestamps = false;

    use HasUuids;

    protected $fillable = [
        'card_keyword_id',
        'scene_id',
        'position_start',
        'position_end',
        'context_excerpt',
        'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'position_start' => 'integer',
            'position_end'   => 'integer',
            'computed_at'    => 'datetime',
        ];
    }

    public function cardKeyword(): BelongsTo
    {
        return $this->belongsTo(CardKeyword::class);
    }

    public function scene(): BelongsTo
    {
        return $this->belongsTo(Scene::class);
    }
}
