<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardImage extends Model
{
    use HasUuids;

    protected $fillable = [
        'card_id',
        'original_name',
        'stored_name',
        'mime_type',
        'size',
        'is_avatar',
    ];

    protected function casts(): array
    {
        return [
            'is_avatar' => 'boolean',
            'size'      => 'integer',
        ];
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }
}
