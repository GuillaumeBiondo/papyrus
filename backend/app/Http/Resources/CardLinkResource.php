<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardLinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'card_id'     => $this->card_id,
            'linked_card' => new CardResource($this->whenLoaded('linkedCard')),
            'label'       => $this->label,
        ];
    }
}
