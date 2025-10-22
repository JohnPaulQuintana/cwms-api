<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Inventory;

class InventoryPolicy
{
    public function viewAny(User $user)
    {
        return in_array($user->role, ['admin', 'warehouse_staff', 'project_manager']);
    }
    public function view(User $user, Inventory $inventory)
    {
        return $this->viewAny($user);
    }
    public function create(User $user)
    {
        return in_array($user->role, ['admin', 'warehouse_staff']);
    }
    public function update(User $user, Inventory $inventory)
    {
        return in_array($user->role, ['admin', 'warehouse_staff']);
    }
    public function delete(User $user, Inventory $inventory)
    {
        return $user->role === 'admin';
    }
}
