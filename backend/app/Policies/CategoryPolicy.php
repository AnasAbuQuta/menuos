<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->restaurant()->exists();
    }

    public function view(User $user, Category $category): bool
    {
        return $user->restaurant?->id === $category->restaurant_id;
    }

    public function create(User $user): bool
    {
        return $user->restaurant()->exists();
    }

    public function update(User $user, Category $category): bool
    {
        return $this->view($user, $category);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->view($user, $category);
    }
}
