<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/laboratorium.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/osipp.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            $identity = Str::lower(trim((string) $request->input('identity')));

            return [
                Limit::perMinute(20)->by('login-ip:'.$request->ip()),
                Limit::perMinute(10)->by('login-id:'.$identity.'|'.$request->ip()),
            ];
        });

        RateLimiter::for('otp', function (Request $request) {
            return [
                Limit::perMinute(15)->by('otp-ip:'.$request->ip()),
                Limit::perMinute(8)->by('otp-session:'.$request->session()->getId()),
            ];
        });

        RateLimiter::for('wali-link', function (Request $request) {
            $identity = 'wali-link:'.($request->user()?->id ?? 'guest').'|'.$request->ip();

            return [
                Limit::perMinute(5)->by($identity),
                Limit::perDay(20)->by($identity.'|day'),
            ];
        });
    }
}
