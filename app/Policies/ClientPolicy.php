<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('clients.view');
    }

    public function view(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('clients.manage');
    }

    public function update(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.manage');
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.manage') && $client->isDeletable();
    }
}
