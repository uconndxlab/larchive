<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class TestApi extends Command
{
    protected $signature = 'api:test
        {--key= : API bearer token (omit to enter it at a hidden prompt)}
        {--url= : App base URL (defaults to APP_URL)}
        {--item= : Test this item transcript instead of discovering items}
        {--limit=5 : Maximum discovered transcripts to test; 0 tests all}
        {--strict : Fail when cached transcript text or recording duration is missing}';

    protected $description = 'Test the deployed read-only API, transcript text and recording durations';

    private int $failures = 0;

    private int $warnings = 0;

    public function handle(): int
    {
        $this->failures = $this->warnings = 0;
        $url = rtrim((string) ($this->option('url') ?: config('app.url')), '/');
        $parts = parse_url($url);
        if (! $parts || ! in_array($parts['scheme'] ?? '', ['http', 'https']) || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            $this->error('Supply an HTTP(S) app base URL without credentials, query or fragment.');

            return self::FAILURE;
        }
        $item = $this->option('item');
        $limit = (string) $this->option('limit');
        if (! ctype_digit($limit) || ($item !== null && (! ctype_digit((string) $item) || (int) $item < 1))) {
            $this->error('--limit must be a nonnegative integer and --item must be a positive item ID.');

            return self::FAILURE;
        }
        $key = trim((string) ($this->option('key') ?: $this->secret('API bearer token')));
        if ($key === '' || preg_match('/[\r\n]/', $key)) {
            $this->error('A valid API bearer token is required.');

            return self::FAILURE;
        }

        $this->info("Testing {$url}/api/v1 (GET requests only)");
        $this->request($url, '/api/v1/items', null, 401);
        $items = $this->request($url, '/api/v1/items', $key);
        $keywords = $this->request($url, '/api/v1/keywords', $key);
        $itemsValid = $this->checkList($items, 'items');
        $this->checkList($keywords, 'keywords');

        $ids = [];
        if ($item !== null) {
            $ids = [(int) $item];
        } elseif ($itemsValid) {
            foreach ($items['data'] as $entry) {
                if (is_array($entry) && isset($entry['id']) && is_numeric($entry['id']) && ! empty($entry['transcript'])) {
                    $ids[] = (int) $entry['id'];
                }
            }
            $ids = array_values(array_unique($ids));
            if ((int) $limit > 0) {
                $ids = array_slice($ids, 0, (int) $limit);
            }
        }
        if ($ids === []) {
            $this->warning('No visible primary transcripts found to test. Use --item=ID to select an item.');
        }
        foreach ($ids as $id) {
            $payload = $this->request($url, "/api/v1/items/{$id}/transcript", $key);
            if ($payload !== null) {
                $this->checkTranscript($payload, $id);
            }
        }
        $this->info('Finished: '.count($ids)." transcript(s) checked; {$this->failures} failure(s); {$this->warnings} warning(s).");

        return $this->failures > 0 || ($this->option('strict') && $this->warnings > 0) ? self::FAILURE : self::SUCCESS;
    }

    private function request(string $url, string $path, ?string $key, int $expectedStatus = 200): ?array
    {
        $label = 'GET '.$path.($key === null ? ' (without key)' : '');
        try {
            $request = Http::acceptJson()->connectTimeout(5)->timeout(30)->withoutRedirecting();
            if ($key !== null) {
                $request = $request->withToken($key);
            }
            $response = $request->get($url.$path);
            if ($response->status() !== $expectedStatus) {
                $this->failure("{$label}: HTTP {$response->status()}, expected {$expectedStatus}.");

                return null;
            }
            if ($expectedStatus === 401) {
                $this->line("PASS {$label}: HTTP 401");

                return null;
            }
            $payload = $response->json();
            if (! is_array($payload)) {
                $this->failure("{$label}: response is not a JSON object.");

                return null;
            }
            $this->line("PASS {$label}: HTTP {$expectedStatus}");

            return $payload;
        } catch (Throwable) {
            // Never print exceptions/response bodies: they can contain secrets or transcripts.
            $this->failure("{$label}: request failed; check connectivity, TLS and server logs.");

            return null;
        }
    }

    private function checkList(?array $payload, string $name): bool
    {
        if ($payload === null) {
            return false;
        }
        if (! isset($payload['data'], $payload['count']) || ! is_array($payload['data']) || ! array_is_list($payload['data'])
            || ! is_int($payload['count']) || $payload['count'] !== count($payload['data'])) {
            $this->failure("{$name}: expected a data array and matching integer count.");

            return false;
        }
        $this->line("PASS {$name}: {$payload['count']} accessible record(s)");

        return true;
    }

    private function checkTranscript(array $payload, int $id): void
    {
        if (($payload['item_id'] ?? null) !== $id || ! is_int($payload['media_id'] ?? null)
            || ! array_key_exists('transcript_text', $payload)
            || (! is_string($payload['transcript_text']) && $payload['transcript_text'] !== null)
            || ! array_key_exists('text', $payload) || $payload['text'] !== $payload['transcript_text']
            || ! isset($payload['segments'], $payload['recordings']) || ! is_array($payload['segments'])
            || ! is_array($payload['recordings']) || ! array_is_list($payload['recordings'])) {
            $this->failure("Item {$id}: invalid transcript response structure or text alias.");

            return;
        }
        foreach (['url', 'filename', 'path', 'metadata', 'meta', 'author', 'revision_history'] as $field) {
            if (array_key_exists($field, $payload)) {
                $this->failure("Item {$id}: original document information is exposed ({$field}).");
            }
        }
        if ($payload['transcript_text'] === null) {
            $this->warning("Item {$id}: transcript text is null (pending, failed or unsupported extraction).");
        } else {
            $this->line("PASS item {$id}: ".mb_strlen($payload['transcript_text']).' transcript characters; content omitted');
        }
        foreach ($payload['recordings'] as $recording) {
            if (! is_array($recording) || ! is_int($recording['media_id'] ?? null)
                || ! is_string($recording['mime_type'] ?? null)
                || ! preg_match('#^(audio|video)/#', $recording['mime_type'])
                || ! array_key_exists('duration_seconds', $recording)) {
                $this->failure("Item {$id}: invalid recording metadata.");

                continue;
            }
            foreach (['url', 'path', 'metadata', 'meta'] as $field) {
                if (array_key_exists($field, $recording)) {
                    $this->failure("Item {$id}: recording contains unexpected source information ({$field}).");
                }
            }
            $mediaId = $recording['media_id'];
            $duration = $recording['duration_seconds'];
            if ($duration === null) {
                $this->warning("Item {$id}, recording {$mediaId}: duration is null.");
            } elseif ((! is_float($duration) && ! is_int($duration)) || ! is_finite((float) $duration) || $duration <= 0) {
                $this->failure("Item {$id}, recording {$mediaId}: duration must be a positive JSON number.");
            } else {
                $this->line("PASS item {$id}, recording {$mediaId}: {$duration} seconds");
            }
        }
        if ($payload['recordings'] === []) {
            $this->line("INFO item {$id}: no visible associated audio/video recordings");
        }
    }

    private function failure(string $message): void
    {
        $this->failures++;
        $this->error('FAIL '.$message);
    }

    private function warning(string $message): void
    {
        $this->warnings++;
        $this->warn('WARN '.$message);
    }
}
