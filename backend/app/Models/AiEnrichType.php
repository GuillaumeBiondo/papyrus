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
        'system_prompt',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
