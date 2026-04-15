<?php

namespace App\Policies;

use App\Models\Card;
use App\Models\Project;
use App\Models\User;

class CardPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $this->hasRole($user, $project, ['owner', 'co_author']);
    }

    public function view(User $user, Card $card): bool
    {
        return $this->hasRole($user, $card->project, ['owner', 'co_author']);
    }

    public function create(User $user, Project $project): bool
    {
        return $this->hasRole($user, $project, ['owner', 'co_author']);
    }

    public function update(User $user, Card $card): bool
    {
        return $this->hasRole($user, $card->project, ['owner', 'co_author']);
    }

    public function delete(User $user, Card $card): bool
    {
        return $this->hasRole($user, $card->project, ['owner', 'co_author']);
    }

    private function hasRole(User $user, Project $project, array $roles): bool
    {
        if ($project->owner_id === $user->id) {
            return true;
        }

        return $project->members()
            ->wherePivot('user_id', $user->id)
            ->wherePivotIn('role', $roles)
            ->exists();
    }
}
