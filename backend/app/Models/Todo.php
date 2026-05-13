<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Todo extends Model
{
    use HasUuids;

    protected $fillable = [
        'arc_id',
        'chapter_id',
        'text',
        'is_done',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_done'    => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function arc(): BelongsTo
    {
        return $this->belongsTo(Arc::class);
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }
}
