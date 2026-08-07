<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! defined('WKB_DONT_NORMALIZE_EWKB')) {
            // Informatif; PostWKB handler diset via GeometryCast, tidak dipakai di sini.
        }
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
        Carbon::setLocale(config('app.locale', 'id'));

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }

    /**
     * Membuat rate limiter untuk endpoint presensi agar tidak dibom beban ganda.
     */
    protected function configureRateLimiting(): void
    {
        $limit = (int) config('attendance.rate_limit_per_minute', 30);

        RateLimiter::for('attendance', function ($request) use ($limit) {
            $key = $request->user()
                ? 'attendance:'.$request->user()->getAuthIdentifier()
                : 'attendance:'.$request->ip();

            return Limit::perMinute($limit)->by($key);
        });

        RateLimiter::for('login', function ($request) {
            $login = strtolower((string) $request->input('login'));

            return Limit::perMinute(5)->by($request->ip().'|'.$login);
        });

        RateLimiter::for('password-change', function ($request) {
            return Limit::perMinute(5)->by($request->user()->getAuthIdentifier().'|'.$request->ip());
        });

        RateLimiter::for('admin-password-reset', function ($request) {
            return Limit::perMinute(10)->by($request->user()->getAuthIdentifier().'|'.$request->ip());
        });
    }
}
