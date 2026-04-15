<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return $this->isMember($user, $project);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Project $project): bool
    {
        return $this->hasRole($user, $project, ['owner', 'co_author']);
    }

    public function delete(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id;
    }

    public function manageMembers(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id;
    }

    public function rebuildIndex(User $user, Project $project): bool
    {
        return $this->hasRole($user, $project, ['owner', 'co_author']);
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
