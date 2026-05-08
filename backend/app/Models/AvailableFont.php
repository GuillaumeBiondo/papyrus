<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'google_font_slug', 'css_family', 'category', 'enabled', 'sort_order'])]
class AvailableFont extends Model
{
    protected $casts = [
        'enabled'    => 'boolean',
        'sort_order' => 'integer',
    ];
}
