<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Due;
use Illuminate\Auth\Access\HandlesAuthorization;

class DuePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Due');
    }

    public function view(AuthUser $authUser, Due $due): bool
    {
        return $authUser->can('View:Due');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Due');
    }

    public function update(AuthUser $authUser, Due $due): bool
    {
        return $authUser->can('Update:Due');
    }

    public function delete(AuthUser $authUser, Due $due): bool
    {
        return $authUser->can('Delete:Due');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Due');
    }

    public function restore(AuthUser $authUser, Due $due): bool
    {
        return $authUser->can('Restore:Due');
    }

    public function forceDelete(AuthUser $authUser, Due $due): bool
    {
        return $authUser->can('ForceDelete:Due');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Due');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Due');
    }

    public function replicate(AuthUser $authUser, Due $due): bool
    {
        return $authUser->can('Replicate:Due');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Due');
    }

}