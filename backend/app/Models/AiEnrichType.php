<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiEnrichType extends Model
{
    protected $fillable = [
        'type_key',
        'label',
        'description',
        'is_active',
        'is_premium',
        'system_prompt',
        'sort_order',
        'allowed_content_types',
    ];

    protected $casts = [
        'is_active'             => 'boolean',
        'is_premium'            => 'boolean',
        'allowed_content_types' => 'array',
    ];
}
