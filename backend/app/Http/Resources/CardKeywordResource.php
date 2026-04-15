<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardKeywordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'card_id'        => $this->card_id,
            'keyword'        => $this->keyword,
            'case_sensitive' => $this->case_sensitive,
        ];
    }
}
