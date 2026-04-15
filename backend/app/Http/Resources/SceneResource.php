<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SceneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'chapter_id'  => $this->chapter_id,
            'title'       => $this->title,
            'content'     => $this->content,
            'status'      => $this->status,
            'order'       => $this->order,
            'word_count'  => $this->word_count,
            'cards'       => CardResource::collection($this->whenLoaded('cards')),
            'annotations' => AnnotationResource::collection($this->whenLoaded('annotations')),
            'notes'       => NoteResource::collection($this->whenLoaded('notes')),
            'updated_at'  => $this->updated_at,
        ];
    }
}
