<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Workshop extends Model
{
    protected $fillable = [
        'key',
        'content_type_id',
        'label',
        'description',
        'is_active',
        'is_premium',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'is_premium' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function contentType(): BelongsTo
    {
        return $this->belongsTo(ContentType::class);
    }
}
