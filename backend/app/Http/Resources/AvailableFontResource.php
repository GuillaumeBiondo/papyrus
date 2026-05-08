<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvailableFontResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'google_font_slug' => $this->google_font_slug,
            'css_family'       => $this->css_family,
            'category'         => $this->category,
            'enabled'          => $this->enabled,
            'sort_order'       => $this->sort_order,
        ];
    }
}
