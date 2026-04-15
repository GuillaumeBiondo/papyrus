<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'noteable_type'  => $this->noteable_type,
            'noteable_id'    => $this->noteable_id,
            'body'           => $this->body,
            'updated_at'     => $this->updated_at,
        ];
    }
}
