<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\FeePlan;
use Illuminate\Auth\Access\HandlesAuthorization;

class FeePlanPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FeePlan');
    }

    public function view(AuthUser $authUser, FeePlan $feePlan): bool
    {
        return $authUser->can('View:FeePlan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FeePlan');
    }

    public function update(AuthUser $authUser, FeePlan $feePlan): bool
    {
        return $authUser->can('Update:FeePlan');
    }

    public function delete(AuthUser $authUser, FeePlan $feePlan): bool
    {
        return $authUser->can('Delete:FeePlan');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:FeePlan');
    }

    public function restore(AuthUser $authUser, FeePlan $feePlan): bool
    {
        return $authUser->can('Restore:FeePlan');
    }

    public function forceDelete(AuthUser $authUser, FeePlan $feePlan): bool
    {
        return $authUser->can('ForceDelete:FeePlan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FeePlan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FeePlan');
    }

    public function replicate(AuthUser $authUser, FeePlan $feePlan): bool
    {
        return $authUser->can('Replicate:FeePlan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FeePlan');
    }

}