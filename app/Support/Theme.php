<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class Theme
{
    /**
     * Cached themes (merge of config + discovery).
     *
     * @var array|null
     */
    protected static ?array $cached = null;

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
     * Get all available themes (configured + discovered in filesystem).
     *
     * Returns an associative array keyed by theme key with at least
     * 'name' and 'description'. Discovered entries contain 'folder'
     * and 'base_path' when applicable.
     */
    public static function all(): array
    {
        if (static::$cached !== null) {
            return static::$cached;
        }

        $configured = config('larchive.themes', []) ?? [];
        $discovered = static::discoverThemes();

        // Merge without overwriting configured entries
        $themes = $configured;
        foreach ($discovered as $key => $info) {
            if (!array_key_exists($key, $themes)) {
                $themes[$key] = $info;
            }
        }

        static::$cached = $themes;

        return $themes;
    }

    /**
     * Get theme information by key.
     */
    public static function get(string $themeKey): ?array
    {
        $all = static::all();
        return $all[$themeKey] ?? null;
    }

    /**
     * Check if a theme exists in the registry (config or discovered).
     */
    public static function exists(string $themeKey): bool
    {
        return array_key_exists($themeKey, static::all());
    }

    /**
     * Get the path to a theme's CSS file (relative to public/).
     *
     * Returns null if not determinable.
     */
    public static function cssPath(?string $themeKey = null): ?string
    {
        $themeKey = $themeKey ?? static::active();
        $info = static::get($themeKey);

        // If theme was discovered from public/themes/{folder} and folder != key,
        // prefer using the actual folder name so asset lookups work.
        if (!empty($info['folder'])) {
            return "themes/{$info['folder']}/theme.css";
        }

        // Fallback to conventional location keyed by theme key
        return "themes/{$themeKey}/theme.css";
    }

    /**
     * Get the path to a theme asset (uses discovered folder when present).
     */
    public static function asset(string $path, ?string $themeKey = null): string
    {
        $themeKey = $themeKey ?? static::active();
        $info = static::get($themeKey);

        $folder = $themeKey;
        if (!empty($info['folder'])) {
            $folder = $info['folder'];
        }

        return asset("themes/{$folder}/{$path}");
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
     */
    public static function exhibitView($exhibit, string $view, array $data = [], array $mergeData = [])
    {
        if (!empty($exhibit->theme_key)) {
            $exhibitThemedView = "themes.{$exhibit->theme_key}.{$view}";
            if (View::exists($exhibitThemedView)) {
                return view($exhibitThemedView, $data, $mergeData);
            }
        }

        return static::view($view, $data, $mergeData);
    }

    /**
     * Return an array of filesystem paths that should be registered as
     * view locations for the given theme key (in precedence order).
     *
     * Examples:
     * - resources/views/themes/{key}
     * - public/themes/{folder}/front-ends (if manifest points to it)
     * - public/themes/{folder}/views
     */
    public static function getViewPaths(string $themeKey): array
    {
        $paths = [];

        // Prefer conventional resources override
        $resourceOverride = resource_path("views/themes/{$themeKey}");
        if (is_dir($resourceOverride)) {
            $paths[] = $resourceOverride;
        }

        $info = static::get($themeKey);
        if (!empty($info['base_path']) && is_dir($info['base_path'])) {
            // If manifest provided explicit view locations, use them
            if (!empty($info['manifest']['views'])) {
                $views = (array) $info['manifest']['views'];
                foreach ($views as $v) {
                    $candidate = $info['base_path'] . DIRECTORY_SEPARATOR . ltrim($v, DIRECTORY_SEPARATOR);
                    if (is_dir($candidate)) {
                        $paths[] = $candidate;
                    }
                }
            }

            // Common fallback locations inside a theme package
            $candidates = [
                $info['base_path'] . DIRECTORY_SEPARATOR . 'views',
                $info['base_path'] . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views',
                $info['base_path'] . DIRECTORY_SEPARATOR . 'front-ends',
                $info['base_path'],
            ];

            foreach ($candidates as $cand) {
                if (is_dir($cand) && !in_array($cand, $paths, true)) {
                    $paths[] = $cand;
                }
            }
        }

        return $paths;
    }

    /**
     * Scan resources/views/themes and public/themes for discoverable themes.
     *
     * @return array [ key => ['name'=>..., 'description'=>..., 'base_path'=>..., 'folder'=>..., 'manifest'=>[...] ] ]
     */
    protected static function discoverThemes(): array
    {
        $found = [];

        // 1) resources/views/themes/* (simple discovery)
        $resPattern = resource_path('views' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . '*');
        foreach (glob($resPattern, GLOB_ONLYDIR) as $dir) {
            $basename = basename($dir);
            $key = Str::slug($basename, '-');
            $found[$key] = [
                'name' => Str::title(str_replace(['-', '_'], ' ', $basename)),
                'description' => 'Theme discovered in resources/views/themes',
                'manifest' => [],
            ];
        }

        // 2) public/themes/* (theme packages / external repos)
        $publicThemesDir = public_path('themes');
        if (is_dir($publicThemesDir)) {
            foreach (glob($publicThemesDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) as $dir) {
                $manifest = [];
                $manifestPath = $dir . DIRECTORY_SEPARATOR . 'theme.json';
                if (file_exists($manifestPath) && is_readable($manifestPath)) {
                    $decoded = @json_decode(file_get_contents($manifestPath), true);
                    if (is_array($decoded)) {
                        $manifest = $decoded;
                    }
                }

                // Determine key (manifest key preferred, else slug of folder)
                $key = isset($manifest['key']) ? (string) $manifest['key'] : Str::slug(basename($dir), '-');

                $found[$key] = [
                    'name' => $manifest['name'] ?? Str::title(str_replace(['-', '_'], ' ', $key)),
                    'description' => $manifest['description'] ?? 'Discovered theme in public/themes/' . basename($dir),
                    'base_path' => $dir,
                    'folder' => basename($dir),
                    'manifest' => $manifest,
                ];
            }
        }

        return $found;
    }
}
