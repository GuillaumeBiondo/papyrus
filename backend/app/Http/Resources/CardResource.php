<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'project_id' => $this->project_id,
            'type'       => $this->type,
            'title'      => $this->title,
            'attributes' => CardAttributeResource::collection($this->whenLoaded('attributes')),
            'links'      => CardLinkResource::collection($this->whenLoaded('links')),
            'keywords'   => CardKeywordResource::collection($this->whenLoaded('keywords')),
            'notes'      => NoteResource::collection($this->whenLoaded('notes')),
            'updated_at' => $this->updated_at,
        ];
    }
}
