<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChapterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'project_id' => $this->project_id,
            'title'      => $this->title,
            'order'      => $this->order,
            'scenes'     => SceneResource::collection($this->whenLoaded('scenes')),
            'updated_at' => $this->updated_at,
        ];
    }
}
