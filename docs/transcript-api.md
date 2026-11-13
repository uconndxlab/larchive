# Transcript text and recording duration API

`GET /api/v1/items/{item}/transcript` requires `Authorization: Bearer <token>` (Sanctum). Existing access rules apply: administrators can access all items; other API users can access published items allowed by the item's visibility. Inaccessible items return 404. Media-level `metadata.visibility` is also respected: hidden media are available only to administrators. Responses use `Cache-Control: private, no-store`.

The endpoint follows the existing primary `items.transcript_id` association. It does not choose a different transcript or change existing associations. A missing primary transcript returns 404.

Example response:

```json
{
  "item_id": 123,
  "media_id": 124,
  "title": "Interview",
  "format": "docx",
  "transcript_text": "00:00:12 Interviewer: Can you tell us about…\nSpeaker: Yes…",
  "text": "00:00:12 Interviewer: Can you tell us about…\nSpeaker: Yes…",
  "segments": [],
  "recordings": [
    {"media_id": 456, "mime_type": "audio/mpeg", "duration_seconds": 5432.75}
  ]
}
```

`transcript_text` is cached UTF-8 plain text. `text` is retained as an alias for compatibility; it now preserves source formatting and timestamps rather than flattening parsed segments. The item-list transcript summary also omits filenames and respects media visibility. The transcript endpoint no longer returns `url` or `filename`, original document bytes, storage paths, or document properties. The existing authenticated original-download route remains available to authorized archival users, as documented in [private-transcripts.md](private-transcripts.md).

`recordings` contains all visible audio/video Media records belonging to the item, ordered by `sort_order`, then ID. Transcripts are excluded, including legacy transcripts linked without the `is_transcript` flag. Multiple recordings remain separate; durations are not summed and no primary recording is inferred. No source recording URLs or storage paths are returned. An empty array means no accessible recordings are associated with this item.

`duration_seconds` is a numeric value measured by FFprobe, including fractional seconds. It is independent of the older whole-second item `oh.duration` metadata and getID3 `metadata.duration`; neither is used as an estimate or fallback. Null means duration has not been extracted or extraction failed.

`transcript_text: null` means extraction is pending, unsupported, or failed. Empty successfully extracted text is `""`. PDFs remain unsupported for text extraction and do not expose a download URL through this endpoint. Existing malformed documents remain stored and can be investigated by an authorized archivist.

DOCX extraction reads only visible main-document body text, retaining speaker text, timestamp strings, paragraph breaks, tabs, and explicit line breaks. It excludes package properties, comments, deleted/moved-from revisions, field instructions, and hidden runs. Legacy DOC uses the local antiword converter. Layout and table formatting can vary with the source; extraction does not invent missing content or timestamps. Word documents return an empty `segments` array. Existing TXT, VTT, SRT and OHMS parsers supply cached segments for compatibility. TXT segment ends are null because their existing caption parser estimates them; explicit SRT/VTT ends are retained. The plain-text field retains source timestamps for downstream interpretation. This enhancement generates no chapters, keywords, or VTT files.

## Deployment and backfill

Requirements on the web/queue/command hosts:

- PHP ZIP, DOM/XML and mbstring extensions (DOCX extraction uses ZIP and DOM directly; no new Composer library).
- `ffprobe`, normally supplied by the FFmpeg package, for audio/video duration.
- `antiword` with its UTF-8 mapping for legacy `.doc` files. No local DOC transcripts were found during implementation; the converter is covered by a synthetic DOC fixture.
- Existing Laravel queue workers and access to persistent transcript and recording disks. Conversion stays on the server; no external document or AI service is used.

Binary paths may be configured with `FFPROBE_BINARY` and `ANTIWORD_BINARY`; refresh Laravel's config cache through the usual deployment procedure after changing them. FFprobe has a 120-second timeout and only the file protocol is allowed. DOC conversion has a 60-second timeout. DOCX document XML is limited to 20 MiB after decompression. Probe failures, missing tools, unsupported or malformed files leave cached values null and are logged with Media ID, extraction kind and exception class. Source bytes, process output and storage paths are excluded from these new extraction logs.

Deploy the migration before enabling the new application code and restart queue workers:

```bash
php artisan migrate --force
php artisan queue:restart

# Preview extraction, without writing cached metadata.
php artisan transcripts:extract-text --dry-run
php artisan media:backfill-durations --dry-run

# Cache existing transcripts and recordings.
php artisan transcripts:extract-text
php artisan media:backfill-durations
```

Both commands process records in batches, skip non-null cached values, continue after individual failures, and exit with code 1 if any extraction fails. They create no Media records, modify no associations, and never modify/delete original files. Each supports `--media=123` to select a single record. Recording backfill additionally supports `--disk=archives`; its default is the existing `media.disk` configuration, with fallback to public storage for older uploads/incoming attachments when the configured disk has no source file. Legacy `public/` path prefixes are stripped for the public disk. An explicit `--disk` override uses only that disk. Private transcript storage retains the existing legacy public-disk fallback. See [private-transcripts.md](private-transcripts.md) for removing legacy public copies.

New Media uploads and source changes enqueue `ExtractMediaMetadata` after the database transaction commits, on the existing `media.processing.queue` (default `media`). Attaching a legacy transcript through `transcript_id` also enqueues extraction. Ensure a worker consumes that queue, for example `php artisan queue:work --queue=media`; the sync queue connection runs inline. This metadata job operates independently of the optional thumbnail/getID3 upload-processing switch. API requests only read the database and never parse documents or run FFprobe. If extraction fails, fix the source/tool/storage issue and rerun the relevant command.

Cached text lives in `media.transcript_text`, parsed segments in `transcript_segments`, extraction provenance in `transcript_extracted_at`, and measured duration in `duration_seconds`. Setting `transcript_text` through the Media model marks it as curated and clears generated segments/provenance. Backfills never overwrite non-null text, including an intentionally empty string. Replacing a source via the Media model clears generated text and duration and enqueues fresh extraction; curated text is retained. Bulk SQL writes and replacing file bytes in place bypass model hooks; import scripts must explicitly enqueue extraction and invalidate generated caches when doing that. The new columns are included in normal database backups; text should receive the same access protections as the original private documents.
