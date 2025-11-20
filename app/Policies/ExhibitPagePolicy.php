<?php

namespace App\Policies;

use App\Models\ExhibitPage;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ExhibitPagePolicy
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
    public function view(?User $user, ExhibitPage $exhibitPage): bool
    {
        // Admins can always view
        if ($user && $user->isAdmin()) {
            return true;
        }

        // Check visibility level
        if ($exhibitPage->visibility === 'public') {
            return true;
        }

        if ($exhibitPage->visibility === 'authenticated' && $user) {
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
    public function update(User $user, ExhibitPage $exhibitPage): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ExhibitPage $exhibitPage): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ExhibitPage $exhibitPage): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ExhibitPage $exhibitPage): bool
    {
        return false;
    }
}
