<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Media;
use App\Models\SiteSetting;
use App\Models\Taxonomy;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class ApiAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_requires_a_bearer_token_and_exposes_items_transcripts_and_keywords(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => 'admin',
        ]);
        $token = $user->createToken('api-test')->plainTextToken;

        $taxonomy = Taxonomy::create([
            'key' => 'tags',
            'name' => 'Tags',
            'hierarchical' => false,
        ]);

        $tagOne = Term::create([
            'taxonomy_id' => $taxonomy->id,
            'name' => 'History',
            'slug' => 'history',
        ]);

        $tagTwo = Term::create([
            'taxonomy_id' => $taxonomy->id,
            'name' => 'Oral History',
            'slug' => 'oral-history',
        ]);

        $item = Item::create([
            'title' => 'Sample Oral History',
            'slug' => 'sample-oral-history',
            'status' => 'published',
            'visibility' => 'public',
            'description' => 'Example item for API testing.',
        ]);

        $item->terms()->sync([$tagOne->id, $tagTwo->id]);

        $transcriptPath = 'items/' . $item->id . '/transcripts/sample.txt';
        Storage::disk('public')->put($transcriptPath, "Speaker 1 (00:00): Hello world\nSpeaker 1 (00:03): More transcript");

        $transcript = Media::create([
            'item_id' => $item->id,
            'filename' => 'sample.txt',
            'path' => $transcriptPath,
            'mime_type' => 'text/plain',
            'size' => 120,
            'is_transcript' => true,
            'format' => 'txt',
        ]);

        $item->update(['transcript_id' => $transcript->id]);

        $unauthenticated = $this->getJson('/api/v1/items');
        $unauthenticated->assertStatus(401);

        $itemsResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/items');

        $itemsResponse->assertOk()
            ->assertJsonPath('data.0.title', 'Sample Oral History')
            ->assertJsonPath('data.0.id', $item->id);

        $transcriptResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/items/' . $item->id . '/transcript');

        $transcriptResponse->assertOk()
            ->assertJsonPath('item_id', $item->id)
            ->assertJsonPath('format', 'txt')
            ->assertJsonFragment(['Speaker 1: Hello world']);

        $keywordsResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/keywords');

        $keywordsResponse->assertOk()
            ->assertJsonFragment(['name' => 'History'])
            ->assertJsonFragment(['name' => 'Oral History']);
    }

    public function test_private_keyword_counts_and_media_access_are_restricted_by_visibility(): void
    {
        Storage::fake('public');

        $viewer = User::factory()->create([
            'role' => 'contributor',
        ]);
        $token = $viewer->createToken('api-test')->plainTextToken;

        $taxonomy = Taxonomy::create([
            'key' => 'tags',
            'name' => 'Tags',
            'hierarchical' => false,
        ]);

        $publicTag = Term::create([
            'taxonomy_id' => $taxonomy->id,
            'name' => 'Public Tag',
            'slug' => 'public-tag',
        ]);

        $privateTag = Term::create([
            'taxonomy_id' => $taxonomy->id,
            'name' => 'Private Tag',
            'slug' => 'private-tag',
        ]);

        $publicItem = Item::create([
            'title' => 'Public Item',
            'slug' => 'public-item',
            'status' => 'published',
            'visibility' => 'public',
        ]);
        $publicItem->terms()->sync([$publicTag->id]);

        $privateItem = Item::create([
            'title' => 'Private Item',
            'slug' => 'private-item',
            'status' => 'published',
            'visibility' => 'hidden',
        ]);
        $privateItem->terms()->sync([$privateTag->id]);

        $keywordsResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/keywords');

        $keywordsResponse->assertOk();
        $this->assertSame(1, $keywordsResponse->json('count'));
        $this->assertSame('Public Tag', $keywordsResponse->json('data.0.name'));
        $this->assertNotSame('Private Tag', $keywordsResponse->json('data.0.name'));

        $privateMedia = Media::create([
            'item_id' => $privateItem->id,
            'filename' => 'secret.mp3',
            'path' => 'items/' . $privateItem->id . '/secret.mp3',
            'mime_type' => 'audio/mpeg',
            'size' => 10,
            'is_transcript' => false,
        ]);

        Storage::disk('public')->put($privateMedia->path, 'secret audio');

        $mediaResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->get('/media/' . $privateMedia->id . '/stream');

        $mediaResponse->assertStatus(403);
    }

    public function test_admin_can_create_and_revoke_api_tokens_for_a_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $targetUser = User::factory()->create(['role' => 'contributor']);

        $this->assertCount(0, $targetUser->tokens()->get());

        $createResponse = $this->actingAs($admin)
            ->post('/admin/users/' . $targetUser->id . '/tokens', [
                'name' => 'External importer',
            ]);

        $createResponse->assertRedirect('/admin/users/' . $targetUser->id . '/edit');

        $this->assertCount(1, $targetUser->fresh()->tokens()->get());
        $this->assertNotNull(session('api_token'));

        $tokenId = $targetUser->fresh()->tokens()->first()->id;

        $revokeResponse = $this->actingAs($admin)
            ->delete('/admin/users/' . $targetUser->id . '/tokens/' . $tokenId);

        $revokeResponse->assertRedirect('/admin/users/' . $targetUser->id . '/edit');
        $this->assertCount(0, $targetUser->fresh()->tokens()->get());
    }

    public function test_admin_menu_survives_theme_layout_overrides(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $themeKey = 'custom-theme';
        $themeDir = resource_path("views/themes/{$themeKey}");
        $themePartialDir = $themeDir . '/partials';
        $themeLayoutDir = $themeDir . '/layouts';

        if (!is_dir($themePartialDir)) {
            mkdir($themePartialDir, 0777, true);
        }
        if (!is_dir($themeLayoutDir)) {
            mkdir($themeLayoutDir, 0777, true);
        }

        file_put_contents($themePartialDir . '/admin-nav.blade.php', '<nav>THEME-ONLY NAV</nav>');
        file_put_contents($themeLayoutDir . '/app.blade.php', '<html><body>THEME-ONLY LAYOUT</body></html>');

        SiteSetting::set('active_theme', $themeKey);

        $reflection = new \ReflectionProperty(\App\Support\Theme::class, 'cached');
        $reflection->setValue(null, null);

        $response = $this->actingAs($admin)->get('/admin/items/workspace');

        $response->assertOk();
        $response->assertSeeText('Admin');
        $response->assertSeeText('Items Workspace');
        $response->assertDontSeeText('THEME-ONLY NAV');
        $response->assertDontSeeText('THEME-ONLY LAYOUT');

        unlink($themePartialDir . '/admin-nav.blade.php');
        unlink($themeLayoutDir . '/app.blade.php');
        rmdir($themeLayoutDir);
        rmdir($themePartialDir);
        rmdir($themeDir);
    }

    public function test_profile_page_survives_theme_layout_overrides(): void
    {
        $user = User::factory()->create();

        $themeKey = 'profile-theme';
        $themeDir = resource_path("views/themes/{$themeKey}");
        $themeLayoutDir = $themeDir . '/layouts';

        if (!is_dir($themeLayoutDir)) {
            mkdir($themeLayoutDir, 0777, true);
        }

        file_put_contents($themeLayoutDir . '/app.blade.php', '<html><body>THEME-ONLY LAYOUT</body></html>');

        SiteSetting::set('active_theme', $themeKey);

        $reflection = new \ReflectionProperty(\App\Support\Theme::class, 'cached');
        $reflection->setValue(null, null);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertSeeText('My Profile');
        $response->assertDontSeeText('THEME-ONLY LAYOUT');

        unlink($themeLayoutDir . '/app.blade.php');
        rmdir($themeLayoutDir);
        rmdir($themeDir);
    }
}
