<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiVerification extends Model
{
    protected $fillable = [
        'label',
        'is_active',
        'target',
        'has_extra_input',
        'extra_input_label',
        'extra_input_placeholder',
        'pre_prompt',
        'sort_order',
        'allowed_card_types',
        'allow_multiple_cards',
    ];

    protected function casts(): array
    {
        return [
            'is_active'            => 'boolean',
            'has_extra_input'      => 'boolean',
            'sort_order'           => 'integer',
            'allowed_card_types'   => 'array',
            'allow_multiple_cards' => 'boolean',
        ];
    }
}
