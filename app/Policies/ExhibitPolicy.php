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
    public function viewAny(?User $user): bool
    {
        // Curators and above can access admin workspace
        return $user && $user->isCurator();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Exhibit $exhibit): bool
    {
        // Admins and curators can view everything
        if ($user && $user->isCurator()) {
            return true;
        }

        // For published exhibits, check visibility
        if ($exhibit->status === 'published') {
            if ($exhibit->visibility === 'public') {
                return true;
            }

            if ($exhibit->visibility === 'authenticated' && $user) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only curators and admins can create exhibits
        return $user->isCurator();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Exhibit $exhibit): bool
    {
        return $user->isCurator();
    }

    /**
     * Determine whether the user can publish/archive the exhibit.
     */
    public function publish(User $user, Exhibit $exhibit): bool
    {
        return $user->isCurator();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Exhibit $exhibit): bool
    {
        return $user->isCurator();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Exhibit $exhibit): bool
    {
        return $user->isCurator();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Exhibit $exhibit): bool
    {
        return $user->isAdmin();
    }
}
