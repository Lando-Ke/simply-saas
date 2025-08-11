<?php

namespace App\Providers;

use App\Services\BrandingService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;

class BrandingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(BrandingService::class, function ($app) {
            return new BrandingService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Share branding service with all views
        View::composer('*', function ($view) {
            $view->with('brandingService', app(BrandingService::class));
        });

        // Register Blade directives for branding
        Blade::directive('brandingCss', function () {
            return "<?php echo app(App\Services\BrandingService::class)->getCssString(); ?>";
        });

        Blade::directive('primaryColor', function () {
            return "<?php echo app(App\Services\BrandingService::class)->getPrimaryColor(); ?>";
        });

        Blade::directive('secondaryColor', function () {
            return "<?php echo app(App\Services\BrandingService::class)->getSecondaryColor(); ?>";
        });

        Blade::directive('orgLogo', function () {
            return "<?php echo app(App\Services\BrandingService::class)->getLogo(); ?>";
        });

        Blade::directive('orgName', function () {
            return "<?php echo app(App\Services\BrandingService::class)->getOrganizationName(); ?>";
        });
    }
}