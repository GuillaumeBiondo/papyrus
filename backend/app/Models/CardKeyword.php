<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CardKeyword extends Model
{
    public $timestamps = false;

    use HasUuids;

    protected $fillable = [
        'card_id',
        'keyword',
        'case_sensitive',
    ];

    protected function casts(): array
    {
        return [
            'case_sensitive' => 'boolean',
        ];
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(KeywordOccurrence::class);
    }
}
