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
     * This registers any view folders provided by the active theme (resources override
     * or external theme packages in public/themes/*). Each discovered path is prepended
     * so theme-provided views take precedence.
     */
    protected function registerThemeViews(): void
    {
        try {
            $activeTheme = Theme::active();
            $paths = Theme::getViewPaths($activeTheme);

            foreach ($paths as $path) {
                if (is_dir($path)) {
                    View::getFinder()->prependLocation($path);
                }
            }
        } catch (\Exception $e) {
            // Silently fail during migration or if site_settings table doesn't exist
            \Log::debug('Theme view registration skipped: ' . $e->getMessage());
        }
    }
}

