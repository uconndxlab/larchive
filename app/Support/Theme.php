<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\View;

class Theme
{
    /**
     * Get the active theme key.
     */
    public static function active(): string
    {
        return SiteSetting::get('active_theme', config('larchive.default_theme', 'default'));
    }

    /**
     * Check if the given theme is the active theme.
     */
    public static function is(string $themeKey): bool
    {
        return static::active() === $themeKey;
    }

    /**
     * Get all available themes from config.
     */
    public static function all(): array
    {
        return config('larchive.themes', []);
    }

    /**
     * Get theme information by key.
     */
    public static function get(string $themeKey): ?array
    {
        return config("larchive.themes.{$themeKey}");
    }

    /**
     * Check if a theme exists in the registry.
     */
    public static function exists(string $themeKey): bool
    {
        return array_key_exists($themeKey, static::all());
    }

    /**
     * Get the path to a theme's CSS file.
     */
    public static function cssPath(?string $themeKey = null): ?string
    {
        $theme = $themeKey ?? static::active();
        return "themes/{$theme}/theme.css";
    }

    /**
     * Get the path to a theme asset.
     */
    public static function asset(string $path, ?string $themeKey = null): string
    {
        $theme = $themeKey ?? static::active();
        return asset("themes/{$theme}/{$path}");
    }

    /**
     * Resolve a view with theme override support.
     * 
     * Tries in order:
     * 1. themes/{active_theme}/{view}
     * 2. {view} (fallback to base view)
     */
    public static function view(string $view, array $data = [], array $mergeData = [])
    {
        $activeTheme = static::active();
        $themedView = "themes.{$activeTheme}.{$view}";

        if (View::exists($themedView)) {
            return view($themedView, $data, $mergeData);
        }

        return view($view, $data, $mergeData);
    }

    /**
     * Resolve a view for an exhibit with theme override support.
     * 
     * Tries in order:
     * 1. themes/{exhibit->theme_key}/{view} (if exhibit has theme_key)
     * 2. themes/{active_theme}/{view}
     * 3. {view} (fallback to base view)
     */
    public static function exhibitView($exhibit, string $view, array $data = [], array $mergeData = [])
    {
        // Try exhibit-specific theme first
        if (!empty($exhibit->theme_key)) {
            $exhibitThemedView = "themes.{$exhibit->theme_key}.{$view}";
            if (View::exists($exhibitThemedView)) {
                return view($exhibitThemedView, $data, $mergeData);
            }
        }

        // Fall back to site theme resolution
        return static::view($view, $data, $mergeData);
    }
}
