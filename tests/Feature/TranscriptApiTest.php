<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TranscriptApiTest extends TestCase
{
    use RefreshDatabase;

    public static function documentFormats(): array
    {
        return [
            'pdf' => ['pdf', 'application/pdf', 'pdf'],
            'doc' => ['doc', 'application/msword', 'doc'],
            'docx' => ['docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'docx'],
            'legacy pdf' => ['pdf', 'application/pdf', 'txt'],
            'legacy doc' => ['doc', 'application/msword', 'txt'],
            'legacy docx' => ['docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'txt'],
            'detected pdf' => ['pdf', 'application/pdf', null],
        ];
    }

    #[DataProvider('documentFormats')]
    public function test_document_transcripts_return_a_url_without_binary_text(string $extension, string $mimeType, ?string $format): void
    {
        $item = $this->createTranscript($extension, $mimeType, $format, "Document bytes: \xFF\xFE\x80");
        $user = User::factory()->create(['role' => 'contributor']);
        $token = $user->createToken('transcript-test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/items/'.$item->id.'/transcript')
            ->assertOk()
            ->assertJsonPath('filename', 'transcript.'.$extension)
            ->assertJsonPath('url', route('transcripts.download', $item->transcript))
            ->assertJsonPath('text', null)
            ->assertJsonPath('segments', []);

        $item->update(['visibility' => 'hidden']);
        $this->withToken($token)->getJson('/api/v1/items/'.$item->id.'/transcript')->assertNotFound();
    }

    public function test_text_with_invalid_utf8_returns_replacement_characters(): void
    {
        $item = $this->createTranscript('txt', 'text/plain', 'txt', "Hello \xFF world");
        $user = User::factory()->create(['role' => 'contributor']);

        $this->withToken($user->createToken('transcript-test')->plainTextToken)
            ->getJson('/api/v1/items/'.$item->id.'/transcript')
            ->assertOk()
            ->assertJsonPath('text', "Hello \u{FFFD} world")
            ->assertJsonPath('segments.0.text', "Hello \u{FFFD} world");
    }

    public function test_api_parses_the_linked_primary_transcript(): void
    {
        $item = $this->createTranscript('txt', 'text/plain', 'txt', 'Primary transcript');
        Storage::disk('public')->put('other.txt', 'Other transcript');
        Media::create([
            'item_id' => $item->id,
            'filename' => 'other.txt',
            'path' => 'other.txt',
            'mime_type' => 'text/plain',
            'size' => 16,
            'is_transcript' => true,
            'format' => 'txt',
            'sort_order' => -1,
        ]);
        $user = User::factory()->create(['role' => 'admin']);

        $this->withToken($user->createToken('transcript-test')->plainTextToken)
            ->getJson('/api/v1/items/'.$item->id.'/transcript')
            ->assertOk()
            ->assertJsonPath('text', 'Primary transcript');
    }

    private function createTranscript(string $extension, string $mimeType, ?string $format, string $content): Item
    {
        Storage::fake('public');
        $item = Item::create([
            'title' => 'Transcript item',
            'slug' => 'transcript-item',
            'status' => 'published',
            'visibility' => 'public',
        ]);
        $path = 'transcripts/transcript.'.$extension;
        Storage::disk('public')->put($path, $content);
        $transcript = Media::create([
            'item_id' => $item->id,
            'filename' => 'transcript.'.$extension,
            'path' => $path,
            'mime_type' => $mimeType,
            'size' => strlen($content),
            'is_transcript' => true,
            'format' => $format,
        ]);
        $item->update(['transcript_id' => $transcript->id]);

        return $item;
    }
}
