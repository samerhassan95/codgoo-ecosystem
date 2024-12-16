<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register any application services here.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Define API routes (common for both admin and client)
        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/api.php'));

        // Load the admin-specific routes, with 'admin' prefix
        Route::middleware('api')
            ->prefix('api/admin')
            ->group(base_path('routes/admin.php'));

        // Load the client-specific routes, with 'client' prefix
        Route::middleware('api')
            ->prefix('api/client')
            ->group(base_path('routes/client.php'));

        // Define web routes (for things like authentication, etc.)
        Route::middleware('web')
            ->group(base_path('routes/web.php'));
    }
}
