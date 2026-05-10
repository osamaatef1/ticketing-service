<?php

namespace App\Providers;

use App\Services\AdminClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('public-tickets', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Coarse global API safety net — IP-keyed, applied to every /api/* route
        // by Laravel's auto-applied `api` middleware group. Runs before auth.jwt.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Per-admin quota — keyed by JWT subject, applied AFTER auth.jwt so
        // `acting_admin` is populated. Falls back to IP for service-account
        // edge cases where no admin is bound.
        RateLimiter::for('admin', function (Request $request) {
            $admin = $request->attributes->get('acting_admin');

            return $admin?->id
                ? Limit::perMinute(120)->by("admin:{$admin->id}")
                : Limit::perMinute(30)->by('ip:'.$request->ip());
        });

        JsonResource::macro('paginationInformation', function ($request, $paginated, $default) {
            $current = $paginated['current_page'] ?? null;
            $last    = $paginated['last_page']    ?? null;

            return [
                'links' => [
                    'first' => $paginated['first_page_url'] ?? null,
                    'last'  => $paginated['last_page_url']  ?? null,
                    'prev'  => $paginated['prev_page_url']  ?? null,
                    'next'  => $paginated['next_page_url']  ?? null,
                ],
                'meta' => [
                    'current_page' => $current,
                    'last_page'    => $last,
                    'per_page'     => $paginated['per_page'] ?? null,
                    'total'        => $paginated['total']    ?? null,
                    'from'         => $paginated['from']     ?? null,
                    'to'           => $paginated['to']       ?? null,
                    'has_more'     => is_int($current) && is_int($last) && $current < $last,
                ],
            ];
        });
    }
}
