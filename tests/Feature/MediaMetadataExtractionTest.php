<?php

namespace Tests\Feature;

use App\Jobs\ExtractMediaMetadata;
use App\Models\Item;
use App\Models\Media;
use App\Models\User;
use App\Services\MediaMetadataCache;
use App\Services\MediaTextExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Tests\TestCase;
use ZipArchive;

class MediaMetadataExtractionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('transcripts');
        Storage::fake('public');
        config(['media.disk' => 'public']);
    }

    public function test_docx_extracts_body_with_utf8_timestamps_and_paragraphs_without_properties_or_revisions(): void
    {
        $bytes = $this->docx();
        $media = $this->transcript('interview.docx', $bytes, 'txt');
        $this->assertSame("00:00:12 Interviewer: Café — hello\nSpeaker:\tSecond line\nNext paragraph", $media->fresh()->transcript_text);
        $this->assertSame($bytes, Storage::disk('transcripts')->get($media->path));

        $user = User::factory()->create(['role' => 'contributor']);
        $url = '/api/v1/items/'.$media->item_id.'/transcript';
        $this->withToken($user->createToken('test')->plainTextToken)->getJson($url)
            ->assertOk()->assertJsonPath('media_id', $media->id)
            ->assertJsonPath('transcript_text', $media->fresh()->transcript_text)
            ->assertJsonPath('segments', [])
            ->assertJsonMissingPath('filename')->assertJsonMissingPath('path')->assertJsonMissingPath('url')
            ->assertDontSee('SECRET')->assertDontSee('DELETED');
        // Requests use cached text, even if the source is temporarily unavailable.
        Storage::disk('transcripts')->delete($media->path);
        $this->mock(MediaTextExtractor::class)->shouldNotReceive('transcript');
        $this->getJson($url)->assertOk()->assertJsonPath('transcript_text', $media->fresh()->transcript_text);
        $media->item->update(['visibility' => 'hidden']);
        $this->getJson($url)->assertNotFound();
    }

    public function test_backfill_is_idempotent_handles_legacy_links_and_does_not_overwrite_curated_text(): void
    {
        Queue::fake([ExtractMediaMetadata::class]);
        $media = $this->transcript('legacy.docx', $this->docx());
        $media->update(['is_transcript' => false]);
        $this->artisan('transcripts:extract-text', ['--dry-run' => true])->assertSuccessful();
        $this->assertNull($media->fresh()->transcript_text);
        $this->artisan('transcripts:extract-text')->assertSuccessful();
        $first = $media->fresh()->transcript_extracted_at;
        $this->artisan('transcripts:extract-text')->assertSuccessful();
        $this->assertEquals($first, $media->fresh()->transcript_extracted_at);
        $media->refresh()->update(['transcript_text' => 'Curated transcript']);
        $this->artisan('transcripts:extract-text')->assertSuccessful();
        $this->assertSame('Curated transcript', $media->fresh()->transcript_text);
        $this->assertNull($media->fresh()->transcript_extracted_at);
        $this->assertSame(1, Media::count());
    }

    public function test_missing_malformed_and_unsupported_files_do_not_stop_the_batch(): void
    {
        Queue::fake([ExtractMediaMetadata::class]);
        $missing = $this->transcript('missing.docx', 'missing');
        Storage::disk('transcripts')->delete($missing->path);
        $this->transcript('bad.docx', 'not a zip');
        $this->transcript('unsupported.pdf', '%PDF');
        $good = $this->transcript('good.docx', $this->docx());
        $this->artisan('transcripts:extract-text')->assertFailed();
        $this->assertNotNull($good->fresh()->transcript_text);
        $this->assertNull($missing->fresh()->transcript_text);
    }

    public function test_docx_rejects_doctypes_and_never_expands_entities(): void
    {
        $bytes = $this->docx('<!DOCTYPE w:document [<!ENTITY secret SYSTEM "file:///etc/passwd">]>');
        $media = $this->transcript('unsafe.docx', $bytes);
        $this->assertNull($media->fresh()->transcript_text);
    }

    public function test_legacy_doc_extraction_when_antiword_is_available(): void
    {
        $process = new Process(['antiword', '-h']);
        try {
            $process->run();
        } catch (\Throwable) {
            $this->markTestSkipped('antiword is not installed.');
        }
        if (! is_file(__DIR__.'/../Fixtures/legacy.doc')) {
            $this->fail('Missing synthetic legacy DOC fixture.');
        }
        $media = $this->transcript('legacy.doc', file_get_contents(__DIR__.'/../Fixtures/legacy.doc'));
        $text = $media->fresh()->transcript_text;
        $this->assertNotNull($text);
        $this->assertStringContainsString('00:00:12 Interviewer: Café', $text);
        $this->assertStringContainsString('00:00:15 Speaker: Second paragraph.', $text);
    }

    public function test_new_item_upload_automatically_extracts_transcript_text(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user)->post('/items', [
            'title' => 'New interview', 'item_type' => 'audio', 'status' => 'draft', 'visibility' => 'hidden',
            'transcript' => UploadedFile::fake()->createWithContent('interview.docx', $this->docx()),
        ])->assertRedirect();
        $media = Item::firstOrFail()->transcript;
        $this->assertNotNull($media->transcript_text);
        Storage::disk('transcripts')->assertExists($media->path);
    }

    public function test_source_replacement_invalidates_generated_cache_but_preserves_curated_text(): void
    {
        $media = $this->transcript('old.txt', 'Old text');
        Storage::disk('transcripts')->put('new.txt', 'New text');
        $media->refresh()->update(['path' => 'new.txt']);
        $this->assertSame('New text', $media->fresh()->transcript_text);
        $media->refresh()->update(['transcript_text' => 'Curated']);
        $media->update(['path' => 'absent.txt']);
        $this->assertSame('Curated', $media->fresh()->transcript_text);
    }

    public function test_cached_plain_text_preserves_timestamps_without_estimated_segment_ends(): void
    {
        $media = $this->transcript('timed.txt', "Speaker (01:00): First\n\nSpeaker (01:30): Last");
        $media->refresh();
        $this->assertStringContainsString('(01:30)', $media->transcript_text);
        $this->assertSame(60, (int) $media->transcript_segments[0]['start']);
        $this->assertNull($media->transcript_segments[0]['end']);
        $this->assertNull($media->transcript_segments[1]['end']);
    }

    public function test_srt_preserves_explicit_cue_ends(): void
    {
        $media = $this->transcript('timed.srt', "1\n00:00:01,250 --> 00:00:03,750\nSpeaker: Hello\n");
        $this->assertSame(3.75, $media->fresh()->transcript_segments[0]['end']);
    }

    public function test_ffprobe_caches_fractional_duration_for_new_and_existing_recordings(): void
    {
        $this->requireFfprobe();
        $media = $this->recording('recording.wav');
        $this->assertEqualsWithDelta(1.25, $media->fresh()->duration_seconds, 0.0001);
        $media->updateQuietly(['duration_seconds' => null]);
        $this->artisan('media:backfill-durations', ['--dry-run' => true])->assertSuccessful();
        $this->assertNull($media->fresh()->duration_seconds);
        $this->artisan('media:backfill-durations')->assertSuccessful();
        $this->assertEqualsWithDelta(1.25, $media->fresh()->duration_seconds, 0.0001);
        Storage::disk('public')->delete($media->path);
        $this->artisan('media:backfill-durations')->assertSuccessful();
        $this->assertSame(1, Media::count());
    }

    public function test_probe_failures_continue_and_private_recordings_are_excluded_from_api(): void
    {
        $this->requireFfprobe();
        $transcript = $this->transcript('api.txt', 'Hello');
        $good = $this->recording('good.wav', $transcript->item);
        $hidden = $this->recording('hidden.wav', $transcript->item);
        $hidden->update(['metadata' => ['visibility' => 'hidden']]);
        $missing = $this->recording('missing.wav', $transcript->item);
        Storage::disk('public')->delete($missing->path);
        $missing->updateQuietly(['duration_seconds' => null]);
        $bad = $this->recording('bad.wav', $transcript->item);
        Storage::disk('public')->put($bad->path, 'invalid recording');
        $bad->updateQuietly(['duration_seconds' => null]);
        $this->artisan('media:backfill-durations')->assertFailed();
        $this->assertNull($missing->fresh()->duration_seconds);
        $this->assertNull($bad->fresh()->duration_seconds);
        $user = User::factory()->create(['role' => 'contributor']);
        $url = '/api/v1/items/'.$transcript->item_id.'/transcript';
        $this->getJson($url)->assertUnauthorized();
        $this->withToken($user->createToken('test')->plainTextToken)->getJson($url)
            ->assertOk()->assertJsonCount(3, 'recordings')
            ->assertJsonPath('recordings.0.media_id', $good->id)
            ->assertJsonPath('recordings.0.duration_seconds', 1.25);
        $transcript->refresh()->update(['metadata' => ['visibility' => 'hidden']]);
        $this->getJson($url)->assertNotFound();
    }

    public function test_queued_jobs_are_dispatched_for_attachment_and_run_against_current_records(): void
    {
        Queue::fake([ExtractMediaMetadata::class]);
        $media = $this->transcript('queue.txt', 'Queued text');
        Queue::assertPushed(ExtractMediaMetadata::class, fn ($job) => $job->mediaId === $media->id && $job->afterCommit);
        $this->assertNull($media->fresh()->transcript_text);
        (new ExtractMediaMetadata($media->id))->handle(app(MediaMetadataCache::class));
        $this->assertSame('Queued text', $media->fresh()->transcript_text);
    }

    public function test_video_upload_automatically_caches_duration_even_with_optional_processing_disabled(): void
    {
        $this->requireFfprobe();
        config(['media.processing.enabled' => false]);
        $item = Item::create(['title' => 'Video', 'slug' => 'video', 'item_type' => 'video']);
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user)->post('/items/'.$item->id.'/media', [
            'files' => [UploadedFile::fake()->createWithContent('recording.mp4', file_get_contents(__DIR__.'/../Fixtures/recording.mp4'))],
        ])->assertOk();
        $media = $item->media()->firstOrFail();
        $this->assertEqualsWithDelta(1.25, $media->duration_seconds, 0.001);
    }

    public function test_remote_sources_are_streamed_privately_and_temporary_files_are_removed_on_failure(): void
    {
        $source = fopen('php://temp', 'w+b');
        fwrite($source, 'Remote content');
        rewind($source);
        $disk = \Mockery::mock(\Illuminate\Contracts\Filesystem\Filesystem::class);
        $disk->shouldReceive('exists')->with('remote')->andReturn(true);
        $disk->shouldReceive('readStream')->with('remote')->andReturn($source);
        $disk->shouldReceive('size')->with('remote')->andReturn(strlen('Remote content'));
        $temporary = null;
        try {
            app(MediaTextExtractor::class)->withLocalFile($disk, 'remote', function ($path) use (&$temporary) {
                $temporary = $path;
                $this->assertSame('Remote content', file_get_contents($path));
                $this->assertSame(0600, fileperms($path) & 0777);
                throw new \RuntimeException('Synthetic probe failure');
            });
            $this->fail('Expected callback failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Synthetic probe failure', $exception->getMessage());
        }
        $this->assertFileDoesNotExist($temporary);
        $this->assertFalse(is_resource($source));
    }

    public function test_curated_text_wins_when_it_changes_during_extraction(): void
    {
        Queue::fake([ExtractMediaMetadata::class]);
        $media = $this->transcript('race.txt', 'Source');
        $extractor = $this->mock(MediaTextExtractor::class);
        $extractor->shouldReceive('transcript')->once()->andReturnUsing(function () use ($media) {
            $media->update(['transcript_text' => 'Curated during extraction']);

            return ['text' => 'Generated', 'segments' => []];
        });
        app(MediaMetadataCache::class)->extract($media, true);
        $this->assertSame('Curated during extraction', $media->fresh()->transcript_text);
    }

    public function test_legacy_public_prefixed_recordings_are_probed_with_an_archive_disk_configured(): void
    {
        $this->requireFfprobe();
        Storage::fake('archives');
        config(['media.disk' => 'archives']);
        $media = $this->recording('incoming.wav');
        $media->refresh()->update(['path' => 'public/incoming.wav']);
        $this->assertEqualsWithDelta(1.25, $media->fresh()->duration_seconds, 0.001);
    }

    private function requireFfprobe(): void
    {
        $process = new Process(['ffprobe', '-version']);
        try {
            $process->mustRun();
        } catch (\Throwable) {
            $this->markTestSkipped('ffprobe is not installed.');
        }
    }

    private function transcript(string $name, string $content, ?string $format = null): Media
    {
        $item = Item::create(['title' => 'Interview', 'slug' => uniqid('interview-'), 'status' => 'published', 'visibility' => 'public']);
        Storage::disk('transcripts')->put($name, $content);
        $media = $item->media()->create([
            'filename' => $name, 'path' => $name, 'mime_type' => 'application/octet-stream',
            'size' => strlen($content), 'is_transcript' => true, 'format' => $format,
        ]);
        $item->update(['transcript_id' => $media->id]);

        return $media;
    }

    private function recording(string $name, ?Item $item = null): Media
    {
        $item ??= Item::create(['title' => 'Recording', 'slug' => uniqid('recording-')]);
        $pcm = str_repeat("\0", 20000);
        $wav = 'RIFF'.pack('V', 36 + strlen($pcm)).'WAVEfmt '.pack('VvvVVvv', 16, 1, 1, 8000, 16000, 2, 16).'data'.pack('V', strlen($pcm)).$pcm;
        Storage::disk('public')->put($name, $wav);

        return $item->media()->create(['filename' => $name, 'path' => $name, 'mime_type' => 'audio/wav', 'size' => strlen($wav)]);
    }

    private function docx(string $doctype = ''): string
    {
        $path = tempnam(sys_get_temp_dir(), 'test_docx_');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('docProps/core.xml', '<author>SECRET AUTHOR</author>');
        $zip->addFromString('word/comments.xml', '<comment>SECRET COMMENT</comment>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0"?>'.$doctype.'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>
            <w:p><w:r><w:t>00:00:12 Interviewer: Café — hello</w:t></w:r><w:del w:author="SECRET"><w:r><w:delText>DELETED</w:delText></w:r></w:del></w:p>
            <w:p><w:r><w:rPr><w:vanish/></w:rPr><w:t>SECRET HIDDEN</w:t></w:r><w:r><w:t>Speaker:</w:t><w:tab/><w:t>Second line</w:t><w:br/><w:t>Next paragraph</w:t></w:r></w:p>
            </w:body></w:document>');
        $zip->close();
        $bytes = file_get_contents($path);
        unlink($path);

        return $bytes;
    }
}
