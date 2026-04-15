<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\Project;
use App\Models\User;

class NotePolicy
{
    public function create(User $user, Project $project): bool
    {
        return $this->hasRole($user, $project, ['owner', 'co_author']);
    }

    public function update(User $user, Note $note): bool
    {
        $project = $this->resolveProject($note);

        return $project && $this->hasRole($user, $project, ['owner', 'co_author']);
    }

    public function delete(User $user, Note $note): bool
    {
        return $this->update($user, $note);
    }

    private function resolveProject(Note $note): ?Project
    {
        $noteable = $note->noteable;

        if ($noteable instanceof \App\Models\Scene) {
            return $noteable->chapter->project;
        }

        if ($noteable instanceof \App\Models\Card) {
            return $noteable->project;
        }

        return null;
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
