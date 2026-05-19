<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoiceUsageLog extends Model
{
    protected $fillable = [
        'user_id',
        'model',
        'source',
        'audio_seconds',
        'text_length',
    ];

    protected function casts(): array
    {
        return [
            'audio_seconds' => 'float',
            'text_length'   => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
