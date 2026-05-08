<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'card_id'       => $this->card_id,
            'original_name' => $this->original_name,
            'mime_type'     => $this->mime_type,
            'size'          => $this->size,
            'is_avatar'     => $this->is_avatar,
            'url'           => route('cards.images.serve', [$this->card_id, $this->id]),
            'created_at'    => $this->created_at,
        ];
    }
}
