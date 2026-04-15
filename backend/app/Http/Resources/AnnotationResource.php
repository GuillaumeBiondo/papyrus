<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnotationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'scene_id'     => $this->scene_id,
            'user'         => new UserResource($this->whenLoaded('user')),
            'anchor_start' => $this->anchor_start,
            'anchor_end'   => $this->anchor_end,
            'body'         => $this->body,
            'type'         => $this->type,
            'cards'        => CardResource::collection($this->whenLoaded('cards')),
            'updated_at'   => $this->updated_at,
        ];
    }
}
