<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_theme_is_registered(): void
    {
        $theme = Theme::get('default');

        $this->assertNotNull($theme);
        $this->assertSame('Default', $theme['name']);
        $this->assertSame('Base Larchive theme with standard Bootstrap 5 styling.', $theme['description']);
    }

    public function test_admin_can_reset_active_theme_to_default(): void
    {
        SiteSetting::set('active_theme', 'custom-theme');

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post('/admin/settings/theme/reset')
            ->assertRedirect('/admin/settings/theme');

        $this->assertSame('default', Theme::active());
    }

    public function test_sspm_theme_no_longer_renders_its_own_admin_nav(): void
    {
        $layoutPath = public_path('themes/SSPM-Larchive-Theme/views/layouts/app.blade.php');

        $this->assertFileExists($layoutPath);

        $layout = file_get_contents($layoutPath);

        $this->assertStringNotContainsString('Contributor top bar', $layout);
        $this->assertStringNotContainsString('THEME-ONLY NAV', $layout);
    }
}
