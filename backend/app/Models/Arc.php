<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Todo;

class Arc extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'project_id',
        'title',
        'summary',
        'summary_generated_at',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'order'                => 'integer',
            'summary_generated_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class)->orderBy('order');
    }

    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class)->orderBy('sort_order');
    }
}
