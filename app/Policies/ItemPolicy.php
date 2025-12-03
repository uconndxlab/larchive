<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        // Contributors and above can access the admin workspace
        return $user && $user->isContributor();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Item $item): bool
    {
        // Admins can always view
        if ($user && $user->isAdmin()) {
            return true;
        }

        // Curators can view everything
        if ($user && $user->isCurator()) {
            return true;
        }

        // Contributors can view drafts and in_review items
        if ($user && $user->isContributor() && in_array($item->status, ['draft', 'in_review'])) {
            return true;
        }

        // For published items, check visibility
        if ($item->status === 'published') {
            if ($item->visibility === 'public') {
                return true;
            }

            if ($item->visibility === 'authenticated' && $user) {
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
        // Contributors and above can create items
        return $user->isContributor();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Item $item): bool
    {
        // Admins can update everything
        if ($user->isAdmin()) {
            return true;
        }

        // Curators can update everything
        if ($user->isCurator()) {
            return true;
        }

        // Contributors can only edit drafts and in_review items
        if ($user->isContributor() && in_array($item->status, ['draft', 'in_review'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can change the status to published or archived.
     */
    public function publish(User $user, Item $item): bool
    {
        // Only curators and admins can publish or archive
        return $user->isCurator();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Item $item): bool
    {
        // Admins can delete anything
        if ($user->isAdmin()) {
            return true;
        }

        // Curators can delete anything
        if ($user->isCurator()) {
            return true;
        }

        // Contributors can only delete their own drafts
        if ($user->isContributor() && $item->status === 'draft') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Item $item): bool
    {
        return $user->isCurator();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Item $item): bool
    {
        return $user->isAdmin();
    }
}
