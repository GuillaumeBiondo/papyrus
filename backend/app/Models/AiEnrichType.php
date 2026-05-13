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
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_premium' => 'boolean',
    ];
}
