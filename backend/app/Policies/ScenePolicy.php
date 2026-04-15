<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Scene;
use App\Models\User;

class ScenePolicy
{
    public function view(User $user, Scene $scene): bool
    {
        return $this->isMember($user, $scene->chapter->project);
    }

    public function create(User $user, Project $project): bool
    {
        return $this->hasRole($user, $project, ['owner', 'co_author']);
    }

    public function update(User $user, Scene $scene): bool
    {
        return $this->hasRole($user, $scene->chapter->project, ['owner', 'co_author']);
    }

    public function delete(User $user, Scene $scene): bool
    {
        return $this->hasRole($user, $scene->chapter->project, ['owner', 'co_author']);
    }

    private function isMember(User $user, Project $project): bool
    {
        if ($project->owner_id === $user->id) {
            return true;
        }

        return $project->members()->wherePivot('user_id', $user->id)->exists();
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
