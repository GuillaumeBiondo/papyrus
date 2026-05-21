<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GenreCategory extends Model
{
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'color',
        'light_color',
        'text_color',
        'adjacent_categories',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'adjacent_categories' => 'array',
            'sort_order'          => 'integer',
        ];
    }

    public function genres(): HasMany
    {
        return $this->hasMany(Genre::class)->orderBy('sort_order');
    }
}
