<?php

declare(strict_types=1);

namespace App\Policies;

class ProjectPolicy
{
    /**
     * Can the user update a project?
     * Only the project owner or an admin can update it.
     */
    public function update(?object $user, mixed $project): bool
    {
        if ($user === null) return false;
        if ($user->hasRole('admin')) return true;
        return (int) $user->id === (int) ($project['user_id'] ?? $project->user_id ?? 0);
    }

    /**
     * Can the user delete a project?
     * Only admins can delete.
     */
    public function delete(?object $user, mixed $project): bool
    {
        if ($user === null) return false;
        return $user->hasRole('admin');
    }

    /**
     * Can the user view a project?
     * Any authenticated user can view.
     */
    public function view(?object $user, mixed $project): bool
    {
        return $user !== null;
    }
}
