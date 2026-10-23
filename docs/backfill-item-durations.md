# Backfill item durations

Run these commands from the application directory on the environment that has access to the media files:

```sh
# Measure one item without changing the database.
php artisan items:backfill-durations --item=53 --dry-run

# Preview all items, then save their durations.
php artisan items:backfill-durations --dry-run
php artisan items:backfill-durations

# Recalculate existing durations if needed.
php artisan items:backfill-durations --overwrite

# Override MEDIA_STORAGE_DISK / config('media.disk') if needed.
php artisan items:backfill-durations --disk=archives
```

The command visits items in batches, using the same soft-delete scope as `Item::get()`. It excludes transcripts and selects media whose MIME type starts with `audio/` or `video/`. For each item, it measures all readable recordings and saves the longest duration. Ties use the first recording in `sort_order`, then media ID order. It does not sum multiple tracks or alternate versions. If a recording cannot be measured, the maximum is taken from the remaining readable recordings.

Durations are extracted using the existing Composer getID3 dependency and stored as rounded whole seconds in item metadata with key `oh.duration`. Existing values are skipped unless `--overwrite` is provided. Saving uses `updateOrCreate`, so repeated runs do not add metadata rows. Media records and other item metadata are left unchanged.

Local files are analyzed directly. Files on remote storage are streamed to a temporary local file and deleted after analysis; temporary storage must have room for the largest recording. The configured disk needs read access to those files. There is no ffmpeg requirement.

Missing, unreadable, corrupt, or unsupported media produce a warning, then the command tries the next recording or item. If an item has recordings but none can be measured, its metadata is left unchanged and the command finishes with exit code 1. Items with no audio/video or an existing duration are counted as skipped.

Local verification uses generated WAV data and a fake storage disk, including the streamed download path. Actual production recordings and remote storage credentials still need verification in your remote environment.
