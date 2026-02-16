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
    | - Views: resources/views/themes/{theme-key}/
    | - CSS: public/themes/{theme-key}/theme.css
    | - Assets: public/themes/{theme-key}/
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
