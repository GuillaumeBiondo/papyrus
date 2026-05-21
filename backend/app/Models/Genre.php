<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Genre extends Model
{
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'genre_category_id',
        'name',
        'bridges',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'bridges'    => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function genreCategory(): BelongsTo
    {
        return $this->belongsTo(GenreCategory::class);
    }
}
