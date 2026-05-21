<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiVerification extends Model
{
    protected $fillable = [
        'label',
        'description',
        'is_active',
        'target',
        'has_extra_input',
        'extra_input_label',
        'extra_input_placeholder',
        'pre_prompt',
        'sort_order',
        'allowed_card_types',
        'allow_multiple_cards',
        'include_card_lore',
        'include_card_links',
        'is_premium',
        'allowed_content_types',
    ];

    protected function casts(): array
    {
        return [
            'is_active'             => 'boolean',
            'is_premium'            => 'boolean',
            'has_extra_input'       => 'boolean',
            'sort_order'            => 'integer',
            'allowed_card_types'    => 'array',
            'allow_multiple_cards'  => 'boolean',
            'include_card_lore'     => 'boolean',
            'include_card_links'    => 'boolean',
            'allowed_content_types' => 'array',
        ];
    }
}
