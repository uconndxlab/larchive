<?php

namespace Tests\Unit;

use App\Models\Media;
use App\Services\WebVttGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebVttGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_vtt_from_segments_with_timestamps()
    {
        // Create a mock media object
        $media = new Media([
            'filename' => 'test_transcript.srt',
            'path' => 'transcripts/test_transcript.srt',
        ]);

        $segments = [
            ['start' => 0, 'end' => 5, 'text' => 'Hello, this is the first segment.'],
            ['start' => 5, 'end' => 10, 'text' => 'This is the second segment.'],
            ['start' => 10, 'end' => 15, 'text' => 'And this is the third segment.'],
        ];

        $vttPath = WebVttGenerator::generateFromSegments($segments, $media);

        $this->assertNotNull($vttPath);
        $this->assertEquals('transcripts/test_transcript.vtt', $vttPath);
        
        // Check that the file starts with WEBVTT
        $content = \Storage::disk('public')->get($vttPath);
        $this->assertStringStartsWith('WEBVTT', $content);
        $this->assertStringContainsString('Hello, this is the first segment.', $content);
        $this->assertStringContainsString('00:00:00.000 --> 00:00:05.000', $content);
    }

    public function test_formats_timestamp_correctly()
    {
        $reflection = new \ReflectionClass(WebVttGenerator::class);
        $method = $reflection->getMethod('formatTimestamp');
        $method->setAccessible(true);

        // Test various timestamps
        $this->assertEquals('00:00:05.000', $method->invoke(null, 5));
        $this->assertEquals('00:01:30.500', $method->invoke(null, 90.5));
        $this->assertEquals('01:02:03.250', $method->invoke(null, 3723.25));
    }

    public function test_handles_null_timestamps()
    {
        $media = new Media([
            'filename' => 'test_transcript.txt',
            'path' => 'transcripts/test_transcript.txt',
        ]);

        $segments = [
            ['start' => null, 'end' => null, 'text' => 'Plain text without timestamps.'],
        ];

        $vttPath = WebVttGenerator::generateFromSegments($segments, $media);

        $this->assertNotNull($vttPath);
        
        $content = \Storage::disk('public')->get($vttPath);
        $this->assertStringContainsString('00:00:00.000 --> 00:00:05.000', $content);
    }
}
