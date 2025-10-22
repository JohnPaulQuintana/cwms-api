<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Inventory;
use App\Policies\InventoryPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Inventory::class => InventoryPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
