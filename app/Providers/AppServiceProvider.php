<?php

namespace App\Providers;

use App\Services\AdminClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AdminClient::class, function ($app) {
            return new AdminClient(
                baseUrl: (string) config('services.admin.url'),
                token: config('services.admin.token'),
                cacheTtl: (int) config('services.admin.cache_ttl', 300),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
