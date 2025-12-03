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
    public function viewAny(?User $user): bool
    {
        // Curators and above can access admin workspace
        return $user && $user->isCurator();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, ExhibitPage $exhibitPage): bool
    {
        // Admins and curators can view everything
        if ($user && $user->isCurator()) {
            return true;
        }

        // For published pages, check visibility
        if ($exhibitPage->status === 'published') {
            if ($exhibitPage->visibility === 'public') {
                return true;
            }

            if ($exhibitPage->visibility === 'authenticated' && $user) {
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
        // Only curators and admins can create exhibit pages
        return $user->isCurator();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ExhibitPage $exhibitPage): bool
    {
        return $user->isCurator();
    }

    /**
     * Determine whether the user can publish/archive the page.
     */
    public function publish(User $user, ExhibitPage $exhibitPage): bool
    {
        return $user->isCurator();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ExhibitPage $exhibitPage): bool
    {
        return $user->isCurator();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ExhibitPage $exhibitPage): bool
    {
        return $user->isCurator();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ExhibitPage $exhibitPage): bool
    {
        return $user->isAdmin();
    }
}
