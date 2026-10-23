<?php

namespace Tests\Feature;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackfillItemDurationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_measures_a_recording_and_preserves_existing_metadata_on_rerun(): void
    {
        $item = $this->recording();
        $item->metadata()->create(['key' => 'dc.title', 'value' => 'Original title']);

        $this->artisan('items:backfill-durations')->assertSuccessful();
        $this->assertSame('2', $item->getDC('oh.duration'));

        $item->setDC('oh.duration', '99');
        $this->artisan('items:backfill-durations')->assertSuccessful();
        $this->assertSame('99', $item->getDC('oh.duration'));

        $this->artisan('items:backfill-durations', ['--overwrite' => true])->assertSuccessful();
        $this->assertSame('2', $item->getDC('oh.duration'));
        $this->assertSame(1, $item->metadata()->where('key', 'oh.duration')->count());
        $this->assertSame('Original title', $item->getDC('dc.title'));
    }

    public function test_dry_run_measures_without_writing_and_item_option_limits_scope(): void
    {
        $item = $this->recording();
        $other = Item::create(['title' => 'Other item', 'slug' => 'other-item']);
        $other->media()->create([
            'filename' => 'missing.wav', 'path' => 'missing.wav',
            'mime_type' => 'audio/x-wav', 'size' => 1,
        ]);

        $this->artisan('items:backfill-durations', ['--item' => $item->id, '--dry-run' => true])
            ->expectsOutput("Would set item {$item->id}: oh.duration=2 seconds (media {$item->media->first()->id})")
            ->assertSuccessful();

        $this->assertNull($item->getDC('oh.duration'));
        $this->assertNull($other->getDC('oh.duration'));
    }

    public function test_missing_media_does_not_stop_other_items_and_transcripts_are_ignored(): void
    {
        $item = $this->recording();
        $item->media()->create([
            'filename' => 'transcript.wav', 'path' => 'missing-transcript.wav',
            'mime_type' => 'audio/x-wav', 'size' => 1, 'sort_order' => -1, 'is_transcript' => true,
        ]);
        $item->media()->create([
            'filename' => 'missing.wav', 'path' => 'missing.wav',
            'mime_type' => 'audio/x-wav', 'size' => 1, 'sort_order' => 0,
        ]);
        $missing = Item::create(['title' => 'Missing', 'slug' => 'missing']);
        $missing->media()->create([
            'filename' => 'missing.mp4', 'path' => 'missing.mp4',
            'mime_type' => 'video/mp4', 'size' => 1,
        ]);

        $this->artisan('items:backfill-durations')->assertFailed();

        $this->assertSame('2', $item->getDC('oh.duration'));
        $this->assertNull($missing->getDC('oh.duration'));
    }

    public function test_it_streams_files_from_a_remote_disk_to_a_temporary_file(): void
    {
        $item = $this->recording('remote-media');
        // The fake still supplies streams, while this configuration exercises the remote download branch.
        config(['filesystems.disks.remote-media.driver' => 's3']);

        $this->artisan('items:backfill-durations', ['--disk' => 'remote-media'])->assertSuccessful();

        $this->assertSame('2', $item->getDC('oh.duration'));
    }

    public function test_it_saves_the_longest_recording_and_reports_its_media_id(): void
    {
        $item = $this->recording();
        $wav = $this->wav(5);
        Storage::disk('public')->put('longest.wav', $wav);
        $longest = $item->media()->create([
            'filename' => 'longest.wav', 'path' => 'longest.wav',
            'mime_type' => 'audio/x-wav', 'size' => strlen($wav), 'sort_order' => 2,
        ]);
        Storage::disk('public')->put('shorter.wav', $this->wav(3));
        $item->media()->create([
            'filename' => 'shorter.wav', 'path' => 'shorter.wav',
            'mime_type' => 'audio/x-wav', 'size' => strlen($this->wav(3)), 'sort_order' => 3,
        ]);
        $item->media()->create([
            'filename' => 'missing.wav', 'path' => 'missing.wav',
            'mime_type' => 'audio/x-wav', 'size' => 1, 'sort_order' => 4,
        ]);
        Storage::disk('public')->put('transcript.wav', $this->wav(10));
        $item->media()->create([
            'filename' => 'transcript.wav', 'path' => 'transcript.wav',
            'mime_type' => 'audio/x-wav', 'size' => strlen($this->wav(10)),
            'sort_order' => 5, 'is_transcript' => true,
        ]);

        $this->artisan('items:backfill-durations')
            ->expectsOutput("Set item {$item->id}: oh.duration=5 seconds (media {$longest->id})")
            ->assertSuccessful();

        $this->assertSame('5', $item->getDC('oh.duration'));
    }

    private function recording(string $diskName = 'public'): Item
    {
        Storage::fake($diskName);
        config(['media.disk' => 'public']);

        $wav = $this->wav(2);
        Storage::disk($diskName)->put('recording.wav', $wav);

        $item = Item::create(['title' => 'Recording', 'slug' => 'recording']);
        $item->media()->create([
            'filename' => 'recording.wav', 'path' => 'recording.wav',
            'mime_type' => 'audio/x-wav', 'size' => strlen($wav), 'sort_order' => 1,
        ]);

        return $item;
    }

    private function wav(int $seconds): string
    {
        // Generate 8 kHz, mono, 16-bit PCM WAV without external files or tools.
        $pcm = str_repeat("\0", 8000 * 2 * $seconds);

        return 'RIFF'.pack('V', 36 + strlen($pcm)).'WAVEfmt '.pack('VvvVVvv', 16, 1, 1, 8000, 16000, 2, 16)
            .'data'.pack('V', strlen($pcm)).$pcm;
    }
}
