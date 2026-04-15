<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Note extends Model
{
    use HasUuids;

    protected $fillable = [
        'noteable_type',
        'noteable_id',
        'body',
    ];

    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }
}
