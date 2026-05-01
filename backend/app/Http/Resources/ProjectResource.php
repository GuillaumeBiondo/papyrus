<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'genre'            => $this->genre,
            'color'            => $this->color,
            'status'           => $this->status,
            'target_words'     => $this->target_words,
            'target_scenes'    => $this->target_scenes,
            // Stats calculées dans le controller (subqueries)
            'word_count'       => (int) ($this->word_count ?? 0),
            'scene_count'      => (int) ($this->scene_count ?? 0),
            'cards_count'      => (int) ($this->cards_count ?? 0),
            'last_scene_title' => $this->last_scene_title,
            'owner'            => new UserResource($this->whenLoaded('owner')),
            'members'          => UserResource::collection($this->whenLoaded('members')),
            'updated_at'       => $this->updated_at,
            'created_at'       => $this->created_at,
        ];
    }
}
