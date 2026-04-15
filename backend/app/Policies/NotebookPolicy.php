<?php

namespace App\Policies;

use App\Models\NotebookEntry;
use App\Models\User;

class NotebookPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, NotebookEntry $entry): bool
    {
        return $entry->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, NotebookEntry $entry): bool
    {
        return $entry->user_id === $user->id;
    }

    public function delete(User $user, NotebookEntry $entry): bool
    {
        return $entry->user_id === $user->id;
    }
}
