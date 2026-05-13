<?php

namespace App\Http\Resources;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $goalKeys = ['word_goals.project', 'word_goals.arc', 'word_goals.chapter', 'word_goals.scene'];
        $goals = Setting::whereIn('key', $goalKeys)->pluck('value', 'key');

        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'email'            => $this->email,
            'role'             => $this->role,
            'bio'              => $this->bio,
            'avatar_url'       => $this->avatar_stored_name
                                    ? route('profile.avatar')
                                    : null,
            'preferences'      => $this->preferences ?? [],
            'last_login_at'    => $this->last_login_at?->toISOString(),
            'is_premium'       => (bool) $this->is_premium,
            'premium_override' => (bool) $this->premium_override,
            'effective_premium'=> $this->isPremium(),
            'word_goal_defaults' => [
                'project' => (int) ($goals->get('word_goals.project') ?? 80000),
                'arc'     => (int) ($goals->get('word_goals.arc')     ?? 20000),
                'chapter' => (int) ($goals->get('word_goals.chapter') ?? 5000),
                'scene'   => (int) ($goals->get('word_goals.scene')   ?? 1000),
            ],
        ];
    }
}
