<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Available Themes
    |--------------------------------------------------------------------------
    |
    | Register all available themes here. Each theme should have:
    | - A unique key (used in filesystem paths)
    | - A human-readable name
    | - A description
    |
    | Theme files should be placed in:
    | - App-owned views: resources/views/themes/{theme-key}/
    | - External package views/assets: public/themes/{theme-folder}/
    | - CSS: public/themes/{theme-folder}/theme.css
    |
    | To install a theme, clone or copy the theme package into public/themes/<folder>
    | and select it in the admin Theme Settings page. To remove a theme, reset the
    | active theme back to the default theme or remove the external folder from disk.
    |
    */

    'themes' => [
        'default' => [
            'name' => 'Default',
            'description' => 'Base Larchive theme with standard Bootstrap 5 styling.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Theme
    |--------------------------------------------------------------------------
    |
    | The fallback theme when no active theme is set.
    |
    | Note: themes may also be autodiscovered from `public/themes/{folder}` when a
    | `theme.json` manifest is present (manifest can define `key`, `name`, `views`, etc.).
    |
    */

    'default_theme' => 'default',
];
