<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Media;
use App\Models\User;
use App\Services\WebVttGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateTranscriptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('transcripts');
    }

    public function test_downloads_require_authentication_and_item_permission(): void
    {
        $media = $this->transcript();
        Storage::disk('transcripts')->put($media->path, 'Protected transcript');
        $url = route('transcripts.download', $media);
        $this->getJson($url)->assertUnauthorized();

        $user = User::factory()->create(['role' => 'contributor']);
        $token = $user->createToken('download')->plainTextToken;
        $response = $this->withToken($token)->get($url);
        $response->assertOk()->assertDownload('interview.txt');
        $this->assertSame('Protected transcript', $response->streamedContent());
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));

        $this->withToken($token)->getJson('/api/v1/items/'.$media->item_id.'/transcript')
            ->assertOk()->assertJsonPath('text', 'Protected transcript')->assertJsonPath('url', $url);

        $media->item->update(['visibility' => 'hidden']);
        $this->withToken($token)->get($url)->assertForbidden();
    }

    public function test_browser_downloads_and_captions_work_after_migration(): void
    {
        $media = $this->transcript();
        Storage::disk('transcripts')->put($media->path, 'Protected transcript');
        $user = User::factory()->create(['role' => 'contributor']);
        $this->actingAs($user)->get(route('transcripts.download', $media))->assertOk()->assertDownload();
        $this->actingAs($user)->get(route('transcripts.captions', $media))
            ->assertOk()->assertSee('Protected transcript');
        Storage::disk('transcripts')->assertExists(WebVttGenerator::getVttPath($media));
        Storage::disk('public')->assertMissing(WebVttGenerator::getVttPath($media));
    }

    public function test_public_item_captions_remain_public_but_original_download_requires_login(): void
    {
        $media = $this->transcript('public');
        Storage::disk('transcripts')->put($media->path, 'Public captions');
        $this->getJson(route('transcripts.download', $media))->assertUnauthorized();
        $this->get(route('transcripts.captions', $media))->assertOk()->assertSee('Public captions');
        $this->getJson(route('media.stream', $media))->assertUnauthorized();
    }

    public function test_caption_routes_and_stream_cannot_bypass_restricted_item_access(): void
    {
        $media = $this->transcript('authenticated');
        $media->update(['format' => 'vtt', 'path' => 'items/1/interview.vtt']);
        Storage::disk('transcripts')->put($media->path, "WEBVTT\n\n00:00:00.000 --> 00:00:05.000\nProtected transcript\n");
        foreach ([route('transcripts.captions', $media), route('vtt.serve', ['path' => $media->path]), route('media.stream', $media)] as $url) {
            $this->getJson($url)->assertForbidden();
        }

        $user = User::factory()->create(['role' => 'contributor']);
        $this->actingAs($user)->get(route('transcripts.captions', $media))->assertOk();
        $this->actingAs($user)->get(route('vtt.serve', ['path' => $media->path]))->assertOk();
    }

    public function test_existing_files_and_generated_captions_are_verified_then_removed_from_public_storage(): void
    {
        $media = $this->transcript();
        // Include legacy records linked by transcript_id even without the flag.
        $media->update(['is_transcript' => false]);
        $vtt = WebVttGenerator::getVttPath($media);
        Storage::disk('public')->put($media->path, 'Original transcript');
        Storage::disk('public')->put($vtt, 'Generated captions');

        $this->artisan('transcripts:privatize', ['--dry-run' => true])->assertSuccessful();
        Storage::disk('transcripts')->assertMissing($media->path);
        $this->artisan('transcripts:privatize')->assertSuccessful();
        $this->assertSame('Original transcript', Storage::disk('transcripts')->get($media->path));
        $this->assertSame('Generated captions', Storage::disk('transcripts')->get($vtt));
        Storage::disk('public')->assertExists([$media->path, $vtt]);

        $this->artisan('transcripts:privatize', ['--remove-public' => true])->assertSuccessful();
        Storage::disk('public')->assertMissing([$media->path, $vtt]);
        $this->artisan('transcripts:privatize', ['--remove-public' => true])->assertSuccessful();
        $this->assertSame($media->path, $media->fresh()->path);
    }

    public function test_migration_preserves_public_files_when_verification_fails(): void
    {
        $media = $this->transcript();
        Storage::disk('public')->put($media->path, 'Original transcript');
        Storage::disk('transcripts')->put($media->path, 'Different content');
        $this->artisan('transcripts:privatize', ['--remove-public' => true])->assertFailed();
        $this->assertSame('Original transcript', Storage::disk('public')->get($media->path));
    }

    public function test_migration_covers_soft_deleted_items_and_reports_missing_originals(): void
    {
        $media = $this->transcript();
        $media->update(['is_transcript' => false]);
        $media->item->delete();
        $this->artisan('transcripts:privatize')->assertFailed();
        Storage::disk('public')->put($media->path, 'Archived transcript');
        $this->artisan('transcripts:privatize', ['--remove-public' => true])->assertSuccessful();
        Storage::disk('public')->assertMissing($media->path);
        $this->assertSame('Archived transcript', Storage::disk('transcripts')->get($media->path));
    }

    public function test_new_uploads_are_private_and_replacements_remove_old_captions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $data = ['title' => 'Uploaded interview', 'slug' => 'uploaded-interview', 'item_type' => 'audio', 'visibility' => 'authenticated', 'status' => 'draft'];
        $this->actingAs($admin)->post('/items', $data + [
            'transcript' => UploadedFile::fake()->createWithContent('interview.txt', 'Original transcript'),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $item = Item::where('slug', 'uploaded-interview')->firstOrFail();
        $old = $item->transcript;
        Storage::disk('transcripts')->assertExists($old->path);
        Storage::disk('public')->assertMissing($old->path);
        WebVttGenerator::generateFromSegments([['text' => 'Original transcript']], $old);

        $this->actingAs($admin)->put('/items/'.$item->id, $data + [
            'transcript' => UploadedFile::fake()->createWithContent('replacement.txt', 'Replacement transcript'),
        ])->assertSessionHasNoErrors()->assertRedirect();
        Storage::disk('transcripts')->assertMissing([$old->path, WebVttGenerator::getVttPath($old)]);
        Storage::disk('transcripts')->assertExists($item->fresh()->transcript->path);
    }

    private function transcript(string $visibility = 'authenticated'): Media
    {
        $item = Item::create(['title' => 'Interview', 'slug' => 'interview', 'status' => 'published', 'visibility' => $visibility]);
        $media = Media::create([
            'item_id' => $item->id, 'filename' => 'interview.txt', 'path' => 'items/1/upload.txt',
            'mime_type' => 'text/plain', 'size' => 20, 'format' => 'txt', 'is_transcript' => true,
        ]);
        $item->update(['transcript_id' => $media->id]);

        return $media;
    }
}
