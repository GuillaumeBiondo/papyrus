<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SceneSnapshotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'trigger'    => $this->trigger,
            'label'      => $this->label,
            'word_count' => $this->word_count,
            'word_delta' => $this->word_delta,
            'created_at' => $this->created_at?->toISOString(),
            // content omis par défaut (chargé à la demande via show())
            'content'    => $this->when(
                $request->routeIs('snapshots.show'),
                $this->content
            ),
        ];
    }
}
