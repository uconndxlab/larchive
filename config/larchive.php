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
        'sing-sing' => [
            'name' => 'Sing Sing Prison Museum',
            'description' => 'Custom theme for the Sing Sing Prison Museum implementation.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Theme
    |--------------------------------------------------------------------------
    |
    | The fallback theme when no active theme is set.
    |
    */

    'default_theme' => 'default',
];
