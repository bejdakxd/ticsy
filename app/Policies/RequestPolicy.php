<?php

namespace App\Policies;

use App\Models\Request;
use App\Models\User;

class RequestPolicy
{
    public function edit(User $user, Request $request): bool
    {
        return ($user->id === $request->caller_id || $user->hasPermissionTo('view_all_tickets'));
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('update_all_tickets');
    }

    public function setPriority(User $user): bool
    {
        return $user->hasPermissionTo('set_priority');
    }

    public function addComment(User $user, Request $request): bool
    {
        return $user->id === $request->caller_id || $user->hasPermissionTo('add_comments_to_all_tickets');
    }
}
