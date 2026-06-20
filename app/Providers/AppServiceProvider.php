<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Client;
use App\Models\PensionSetting;
use App\Models\Rentencheck;
use App\Models\User;
use App\Policies\ClientPolicy;
use App\Policies\PensionSettingPolicy;
use App\Policies\RentencheckPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Explicit policy registration. Keep this list in sync with policies/.
        // (Each policy inlines the admin check; see policy docblocks.)
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(Rentencheck::class, RentencheckPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(PensionSetting::class, PensionSettingPolicy::class);
    }
}
