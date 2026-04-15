<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardLink extends Model
{
    public $timestamps = false;

    use HasUuids;

    protected $fillable = [
        'card_id',
        'linked_card_id',
        'label',
    ];

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class, 'card_id');
    }

    public function linkedCard(): BelongsTo
    {
        return $this->belongsTo(Card::class, 'linked_card_id');
    }
}
