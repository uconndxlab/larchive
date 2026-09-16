<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Providers\AppServiceProvider;
use App\Support\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionProperty;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;

    private string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturePath = public_path('themes/theme-resolution-test');
    }

    protected function tearDown(): void
    {
        if (is_dir($this->fixturePath)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->fixturePath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );

            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }

            rmdir($this->fixturePath);
        }

        $this->resetThemeCache();

        parent::tearDown();
    }

    public function test_aarons_theme_is_discovered_unchanged(): void
    {
        $theme = Theme::get('sspm');

        $this->assertNotNull($theme);
        $this->assertSame('SSPM-Larchive-Theme', $theme['folder']);
        $this->assertSame(public_path('themes/SSPM-Larchive-Theme'), $theme['base_path']);
        $this->assertSame('views', $theme['manifest']['views']);
    }

    public function test_aarons_views_override_core_views_and_core_views_remain_fallbacks(): void
    {
        SiteSetting::set('active_theme', 'sspm');
        (new AppServiceProvider($this->app))->boot();

        $finder = $this->app['view']->getFinder();
        $themeViews = public_path('themes/SSPM-Larchive-Theme/views');

        $this->assertSame(realpath("{$themeViews}/layouts/app.blade.php"), realpath($finder->find('layouts.app')));
        $this->assertSame(realpath("{$themeViews}/home.blade.php"), realpath($finder->find('home')));
        $this->assertSame(realpath("{$themeViews}/items/show.blade.php"), realpath($finder->find('items.show')));
        $this->assertSame(realpath(resource_path('views/collections/index.blade.php')), realpath($finder->find('collections.index')));
    }

    public function test_aarons_root_assets_and_css_relative_dependencies_resolve(): void
    {
        $this->assertStringEndsWith(
            '/themes/SSPM-Larchive-Theme/css/styles.css',
            Theme::asset('css/styles.css', 'sspm'),
        );
        $this->assertStringEndsWith(
            '/themes/SSPM-Larchive-Theme/js/main.js',
            Theme::asset('js/main.js', 'sspm'),
        );
        $this->assertFileExists(public_path('themes/SSPM-Larchive-Theme/css/../assets/single-story-paper.png'));
        $this->assertFileExists(public_path('themes/SSPM-Larchive-Theme/css/../fonts/mon-grotesk-reg/ABCMonumentGrotesk-Regular.woff2'));
    }

    public function test_assets_fall_back_to_front_ends_and_traversal_is_rejected(): void
    {
        $this->createResolutionFixture();

        $this->assertStringEndsWith('/themes/theme-resolution-test/css/styles.css', Theme::asset('css/styles.css', 'resolution-test'));
        $this->assertStringEndsWith('/themes/theme-resolution-test/js/main.js', Theme::asset('js/main.js', 'resolution-test'));
        $this->assertStringEndsWith('/themes/theme-resolution-test/front-ends/assets/hero-paper.png', Theme::asset('assets/hero-paper.png', 'resolution-test'));
        $this->assertStringEndsWith('/themes/theme-resolution-test/front-ends/bootstrap/css/bootstrap.min.css', Theme::asset('bootstrap/css/bootstrap.min.css', 'resolution-test'));
        $this->assertStringEndsWith('/themes/theme-resolution-test/front-ends/fonts/example.woff2', Theme::asset('fonts/example.woff2', 'resolution-test'));
        $this->assertNull(Theme::asset('../theme.json', 'resolution-test'));
        $this->assertNull(Theme::asset('/etc/passwd', 'resolution-test'));
        $this->assertNull(Theme::asset('missing.css', 'resolution-test'));
    }

    private function createResolutionFixture(): void
    {
        $files = [
            'css/styles.css',
            'js/main.js',
            'front-ends/assets/hero-paper.png',
            'front-ends/bootstrap/css/bootstrap.min.css',
            'front-ends/fonts/example.woff2',
        ];

        foreach ($files as $file) {
            $path = "{$this->fixturePath}/{$file}";
            mkdir(dirname($path), 0777, true);
            touch($path);
        }

        file_put_contents(
            "{$this->fixturePath}/theme.json",
            json_encode(['key' => 'resolution-test', 'name' => 'Resolution Test'], JSON_THROW_ON_ERROR),
        );

        $this->resetThemeCache();
    }

    private function resetThemeCache(): void
    {
        $cache = new ReflectionProperty(Theme::class, 'cached');
        $cache->setValue(null, null);
    }
}
