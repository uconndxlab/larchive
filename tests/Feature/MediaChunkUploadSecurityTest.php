<?php

namespace Tests\Feature;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

class MediaChunkUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_chunk_upload_rejects_path_components_in_the_original_filename(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $item = Item::create([
            'title' => 'Upload security test',
            'slug' => 'upload-security-test',
        ]);

        $response = $this->actingAs($user)->postJson(route('items.media.chunk', $item), [
            'dzchunkindex' => 0,
            'dztotalchunkcount' => 2,
            'dzuuid' => (string) Str::uuid(),
            'original_filename' => '../outside.txt',
            'file' => UploadedFile::fake()->create('chunk', 1),
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('original_filename');
    }

    public function test_chunk_upload_rejects_a_non_uuid_storage_identifier(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $item = Item::create([
            'title' => 'Upload UUID test',
            'slug' => 'upload-uuid-test',
        ]);

        $response = $this->actingAs($user)->postJson(route('items.media.chunk', $item), [
            'dzchunkindex' => 0,
            'dztotalchunkcount' => 2,
            'dzuuid' => '../../outside',
            'original_filename' => 'recording.mp3',
            'file' => UploadedFile::fake()->create('chunk', 1),
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('dzuuid');
    }
}