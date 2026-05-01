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
            'color'        => $this->color ?? '#f59e0b',
            'cards'        => CardResource::collection($this->whenLoaded('cards')),
            'scene'        => $this->whenLoaded('scene', fn () => [
                'id'            => $this->scene->id,
                'title'         => $this->scene->title,
                'chapter_title' => $this->scene->chapter?->title,
                'arc_title'     => $this->scene->chapter?->arc?->title,
            ]),
            'updated_at'   => $this->updated_at,
        ];
    }
}
