<?php

namespace App\Policies;

use App\Models\Annotation;
use App\Models\Project;
use App\Models\Scene;
use App\Models\User;

class AnnotationPolicy
{
    public function viewAny(User $user, Scene $scene): bool
    {
        return $this->isMember($user, $scene->chapter->project);
    }

    public function create(User $user, Scene $scene): bool
    {
        return $this->isMember($user, $scene->chapter->project);
    }

    public function update(User $user, Annotation $annotation): bool
    {
        // owner/co_author peuvent modifier toutes les annotations
        // beta_reader uniquement les siennes
        $project = $annotation->scene->chapter->project;

        if ($this->hasRole($user, $project, ['owner', 'co_author'])) {
            return true;
        }

        return $annotation->user_id === $user->id;
    }

    public function delete(User $user, Annotation $annotation): bool
    {
        return $this->update($user, $annotation);
    }

    public function linkCard(User $user, Annotation $annotation): bool
    {
        $project = $annotation->scene->chapter->project;

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
