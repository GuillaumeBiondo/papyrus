<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'email'         => $this->email,
            'role'          => $this->role,
            'bio'           => $this->bio,
            'avatar_url'    => $this->avatar_stored_name
                                ? route('profile.avatar')
                                : null,
            'preferences'   => $this->preferences ?? [],
            'last_login_at' => $this->last_login_at?->toISOString(),
        ];
    }
}
