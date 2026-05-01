<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KeywordOccurrenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'card_keyword_id'  => $this->card_keyword_id,
            'scene_id'         => $this->scene_id,
            'scene'            => $this->scene ? ['id' => $this->scene->id, 'title' => $this->scene->title] : null,
            'position_start'   => $this->position_start,
            'position_end'     => $this->position_end,
            'context_excerpt'  => $this->context_excerpt,
            'computed_at'      => $this->computed_at,
        ];
    }
}
