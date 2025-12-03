<?php

namespace App\Policies;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CollectionPolicy
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
    public function view(?User $user, Collection $collection): bool
    {
        // Admins and curators can view everything
        if ($user && $user->isCurator()) {
            return true;
        }

        // For published collections, check visibility
        if ($collection->status === 'published') {
            if ($collection->visibility === 'public') {
                return true;
            }

            if ($collection->visibility === 'authenticated' && $user) {
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
        // Only curators and admins can create collections
        return $user->isCurator();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Collection $collection): bool
    {
        return $user->isCurator();
    }

    /**
     * Determine whether the user can publish/archive the collection.
     */
    public function publish(User $user, Collection $collection): bool
    {
        return $user->isCurator();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Collection $collection): bool
    {
        return $user->isCurator();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Collection $collection): bool
    {
        return $user->isCurator();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Collection $collection): bool
    {
        return $user->isAdmin();
    }
}
