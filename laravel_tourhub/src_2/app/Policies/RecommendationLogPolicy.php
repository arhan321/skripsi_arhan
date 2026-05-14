<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RecommendationLog;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class RecommendationLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecommendationLog');
    }

    public function view(AuthUser $authUser, RecommendationLog $recommendationLog): bool
    {
        return $authUser->can('View:RecommendationLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecommendationLog');
    }

    public function update(AuthUser $authUser, RecommendationLog $recommendationLog): bool
    {
        return $authUser->can('Update:RecommendationLog');
    }

    public function delete(AuthUser $authUser, RecommendationLog $recommendationLog): bool
    {
        return $authUser->can('Delete:RecommendationLog');
    }
}
