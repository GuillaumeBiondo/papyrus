<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'genre'        => $this->genre,
            'color'        => $this->color,
            'target_words' => $this->target_words,
            'owner'        => new UserResource($this->whenLoaded('owner')),
            'members'      => UserResource::collection($this->whenLoaded('members')),
            'chapters'     => ChapterResource::collection($this->whenLoaded('chapters')),
            'updated_at'   => $this->updated_at,
            'created_at'   => $this->created_at,
        ];
    }
}
