<?php

namespace App\Policies;

use App\Models\MenuItem;
use App\Models\User;

class MenuItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->restaurant()->exists();
    }

    public function view(User $user, MenuItem $menuItem): bool
    {
        return $user->restaurant?->id === $menuItem->restaurant_id;
    }

    public function create(User $user): bool
    {
        return $user->restaurant()->exists();
    }

    public function update(User $user, MenuItem $menuItem): bool
    {
        return $this->view($user, $menuItem);
    }

    public function delete(User $user, MenuItem $menuItem): bool
    {
        return $this->view($user, $menuItem);
    }
}
