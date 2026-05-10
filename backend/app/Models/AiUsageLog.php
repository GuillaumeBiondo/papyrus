<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageLog extends Model
{
    protected $fillable = [
        'user_id',
        'ai_verification_id',
        'verification_label',
        'model',
        'input_chars',
        'changes_count',
    ];

    protected function casts(): array
    {
        return [
            'input_chars'   => 'integer',
            'changes_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verification(): BelongsTo
    {
        return $this->belongsTo(AiVerification::class, 'ai_verification_id');
    }
}
