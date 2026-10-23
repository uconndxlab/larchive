<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Media;
use App\Models\Taxonomy;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
}
