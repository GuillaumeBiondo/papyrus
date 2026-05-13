<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workshop extends Model
{
    protected $fillable = [
        'key',
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
}
