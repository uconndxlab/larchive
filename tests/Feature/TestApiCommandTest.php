<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TestApiCommandTest extends TestCase
{
    public function test_checks_authentication_lists_transcript_and_fractional_duration(): void
    {
        $this->fakeApi();
        $this->artisan('api:test', ['--url' => 'https://archive.test', '--key' => 'secret-test-key'])
            ->doesntExpectOutputToContain('secret-test-key')
            ->doesntExpectOutputToContain('PRIVATE CONTENT')
            ->expectsOutputToContain('1.25 seconds')
            ->expectsOutputToContain('0 failure(s); 0 warning(s)')
            ->assertSuccessful();
        Http::assertSentCount(4);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://archive.test/api/v1/items/12/transcript'
            && $request->hasHeader('Authorization', 'Bearer secret-test-key'));
    }

    public function test_rejected_keys_fail_without_requesting_transcripts(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Unauthorized'], 401)]);
        $this->artisan('api:test', ['--url' => 'https://archive.test', '--key' => 'invalid'])
            ->expectsOutputToContain('HTTP 401, expected 200')
            ->assertFailed();
        Http::assertSentCount(3);
    }

    public function test_original_document_exposure_and_invalid_durations_fail(): void
    {
        $this->fakeApi(['url' => '/original.docx', 'recordings' => [['media_id' => 20, 'mime_type' => 'audio/mpeg', 'duration_seconds' => '1.25']]]);
        $this->artisan('api:test', ['--url' => 'https://archive.test', '--key' => 'key'])
            ->expectsOutputToContain('original document information is exposed')
            ->expectsOutputToContain('duration must be a positive JSON number')
            ->assertFailed();
    }

    public function test_null_extractions_warn_and_strict_mode_fails(): void
    {
        $this->fakeApi(['transcript_text' => null, 'text' => null]);
        $options = ['--url' => 'https://archive.test', '--key' => 'key'];
        $this->artisan('api:test', $options)->expectsOutputToContain('transcript text is null')->assertSuccessful();
        $this->artisan('api:test', $options + ['--strict' => true])->assertFailed();
    }

    public function test_explicit_item_and_hidden_key_prompt_are_supported(): void
    {
        $this->fakeApi();
        $this->artisan('api:test', ['--url' => 'https://archive.test', '--item' => '12'])
            ->expectsQuestion('API bearer token', 'key')->assertSuccessful();
    }

    public function test_bad_json_http_errors_and_network_failures_fail_cleanly(): void
    {
        Http::fake([
            '*/items' => fn (Request $request) => $request->hasHeader('Authorization')
                ? Http::response('not json') : Http::response([], 401),
            '*/keywords' => Http::failedConnection(),
        ]);
        $this->artisan('api:test', ['--url' => 'https://archive.test', '--key' => 'key'])
            ->expectsOutputToContain('response is not a JSON object')
            ->expectsOutputToContain('request failed; check connectivity')->assertFailed();
    }

    public function test_redirects_are_not_followed_and_invalid_options_make_no_requests(): void
    {
        Http::fake(['*' => Http::response('', 302, ['Location' => 'https://elsewhere.test'])]);
        $this->artisan('api:test', ['--url' => 'https://archive.test', '--key' => 'key'])->assertFailed();
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'elsewhere.test'));
        Http::fake();
        $this->artisan('api:test', ['--url' => 'https://archive.test', '--key' => 'key', '--item' => '../bad'])->assertFailed();
        $this->artisan('api:test', ['--url' => 'file:///tmp/api', '--key' => 'key'])->assertFailed();
        Http::assertNothingSent();
    }

    public function test_discovery_limit_and_all_option_control_the_transcript_requests(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*/items' => fn (Request $request) => $request->hasHeader('Authorization')
                ? Http::response(['data' => [
                    ['id' => 12, 'transcript' => ['id' => 13]],
                    ['id' => 14, 'transcript' => ['id' => 15]],
                ], 'count' => 2]) : Http::response([], 401),
            '*/keywords' => Http::response(['data' => [], 'count' => 0]),
            '*/items/*/transcript' => fn (Request $request) => Http::response([
                'item_id' => str_contains($request->url(), '/12/') ? 12 : 14,
                'media_id' => 13, 'transcript_text' => 'Text', 'text' => 'Text', 'segments' => [], 'recordings' => [],
            ]),
        ]);
        $options = ['--url' => 'https://archive.test', '--key' => 'key'];
        $this->artisan('api:test', $options + ['--limit' => '1'])->assertSuccessful();
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/14/'));
        $this->artisan('api:test', $options + ['--limit' => '0'])->assertSuccessful();
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/14/'));
    }

    private function fakeApi(array $transcriptOverrides = []): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*/items' => fn (Request $request) => $request->hasHeader('Authorization')
                ? Http::response(['data' => [['id' => 12, 'transcript' => ['id' => 13]]], 'count' => 1])
                : Http::response([], 401),
            '*/keywords' => Http::response(['data' => [], 'count' => 0]),
            '*/items/12/transcript' => Http::response(array_replace([
                'item_id' => 12, 'media_id' => 13, 'transcript_text' => 'PRIVATE CONTENT', 'text' => 'PRIVATE CONTENT', 'segments' => [],
                'recordings' => [['media_id' => 20, 'mime_type' => 'audio/mpeg', 'duration_seconds' => 1.25]],
            ], $transcriptOverrides)),
        ]);
    }
}
