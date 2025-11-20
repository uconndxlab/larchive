<?php

namespace App\Policies;

use App\Models\Exhibit;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ExhibitPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Exhibit $exhibit): bool
    {
        // Admins can always view
        if ($user && $user->isAdmin()) {
            return true;
        }

        // Check visibility level
        if ($exhibit->visibility === 'public') {
            return true;
        }

        if ($exhibit->visibility === 'authenticated' && $user) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Exhibit $exhibit): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Exhibit $exhibit): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Exhibit $exhibit): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Exhibit $exhibit): bool
    {
        return false;
    }
}
