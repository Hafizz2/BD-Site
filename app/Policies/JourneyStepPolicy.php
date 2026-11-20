<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\JourneyStep;
use Illuminate\Auth\Access\HandlesAuthorization;

class JourneyStepPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:JourneyStep');
    }

    public function view(AuthUser $authUser, JourneyStep $journeyStep): bool
    {
        return $authUser->can('View:JourneyStep');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:JourneyStep');
    }

    public function update(AuthUser $authUser, JourneyStep $journeyStep): bool
    {
        return $authUser->can('Update:JourneyStep');
    }

    public function delete(AuthUser $authUser, JourneyStep $journeyStep): bool
    {
        return $authUser->can('Delete:JourneyStep');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:JourneyStep');
    }

    public function restore(AuthUser $authUser, JourneyStep $journeyStep): bool
    {
        return $authUser->can('Restore:JourneyStep');
    }

    public function forceDelete(AuthUser $authUser, JourneyStep $journeyStep): bool
    {
        return $authUser->can('ForceDelete:JourneyStep');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:JourneyStep');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:JourneyStep');
    }

    public function replicate(AuthUser $authUser, JourneyStep $journeyStep): bool
    {
        return $authUser->can('Replicate:JourneyStep');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:JourneyStep');
    }

}