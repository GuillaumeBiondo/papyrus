<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'role', 'maintenance_bypass', 'is_blocked', 'block_reason', 'is_premium', 'premium_override', 'bio', 'avatar_stored_name', 'preferences', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, HasUuids, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at'   => 'datetime',
            'last_login_at'       => 'datetime',
            'password'            => 'hashed',
            'preferences'         => 'array',
            'maintenance_bypass'  => 'boolean',
            'is_blocked'          => 'boolean',
            'is_premium'          => 'boolean',
            'premium_override'    => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPremium(): bool
    {
        return (bool) $this->is_premium || (bool) $this->premium_override;
    }

    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_users')
            ->withPivot('role');
    }

    public function notebookEntries(): HasMany
    {
        return $this->hasMany(NotebookEntry::class);
    }

    public function annotations(): HasMany
    {
        return $this->hasMany(Annotation::class);
    }

    public function changelogReads(): HasMany
    {
        return $this->hasMany(ChangelogRead::class);
    }

    public function hasRoleInProject(string $role, Project $project): bool
    {
        return $this->projects()
            ->wherePivot('project_id', $project->id)
            ->wherePivot('role', $role)
            ->exists();
    }

    public function getRoleInProject(Project $project): ?string
    {
        if ($project->owner_id === $this->id) {
            return 'owner';
        }

        $pivot = $this->projects()
            ->wherePivot('project_id', $project->id)
            ->first();

        return $pivot?->pivot->role;
    }
}
