<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use App\Services\VoucherService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(VoucherService::class, fn() => new VoucherService());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\Branch::observe(\App\Observers\BranchObserver::class);
        \App\Models\Supplier::observe(\App\Observers\SupplierObserver::class);
        \App\Models\PosDevice::observe(\App\Observers\PosDeviceObserver::class);

        // Auto-generate account_code for every new User
        User::observe(UserObserver::class);

        \Illuminate\Pagination\Paginator::useBootstrapFive();
    }
}
