<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Support\Theme;

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
        // Register theme view path
        // This allows views to be overridden by placing them in resources/views/themes/{active_theme}/
        $this->registerThemeViews();

        // Share active theme with all views (with fallback during migration)
        try {
            View::share('activeTheme', Theme::active());
        } catch (\Exception $e) {
            View::share('activeTheme', 'default');
        }
    }

    /**
     * Register theme-specific view paths.
     * 
     * This adds the active theme's view directory to the view finder,
     * so Laravel will check themes/{active_theme}/ before falling back to base views.
     */
    protected function registerThemeViews(): void
    {
        try {
            $activeTheme = Theme::active();
            $themePath = resource_path("views/themes/{$activeTheme}");

            if (is_dir($themePath)) {
                // Prepend theme path so it's checked first
                View::getFinder()->prependLocation($themePath);
            }
        } catch (\Exception $e) {
            // Silently fail during migration or if site_settings table doesn't exist
            \Log::debug('Theme view registration skipped: ' . $e->getMessage());
        }
    }
}

