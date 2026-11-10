# Moving existing transcripts into private storage

New transcript uploads and generated captions are stored under `storage/app/transcripts`, outside the public storage symlink. Keep this directory on persistent storage and include it in backups. Do not expose it through a web server alias or a storage symlink. On multiple app servers, this directory must be shared or replicated consistently.

The transcript API returns `/transcripts/{media}/download`. Downloads require either a signed-in browser session or an `Authorization: Bearer ...` header, plus permission to view the item. API clients must send their token when fetching this URL; it does not contain a token. Access is checked for every download. Responses use `Cache-Control: private, no-store`.

Captions are served through `/transcripts/{media}/captions` and follow the item's view policy. Public published items can show captions without login; authenticated and hidden items require the corresponding permissions. Changing an item's visibility applies to subsequent requests. Uploaded originals always require authentication, including originals belonging to public items.

## Production rollout

1. Back up the database and public files to a location outside the web root. Arrange a maintenance window so uploads, replacements, deletions, and caption regeneration cannot run during migration. Pause any processes that write transcript files.
2. Deploy this code, refresh Laravel's configuration and route caches using the site's usual deployment procedure, and ensure the app can write `storage/app/transcripts`. Legacy files remain readable through the app until moved.
3. Inventory the existing files:

   ```sh
   php artisan transcripts:privatize --dry-run
   ```

4. Copy originals and generated `.vtt` captions, verifying SHA-256 checksums:

   ```sh
   php artisan transcripts:privatize
   ```

   This keeps public copies. Investigate any nonzero exit status, missing originals, or checksum mismatches before continuing. The command includes records linked by `transcript_id`, even if `is_transcript` is false or the item is soft-deleted. It leaves database paths unchanged.

5. Test an authenticated download, an unauthorized request, API text parsing, and caption playback. Then remove the verified public copies:

   ```sh
   php artisan transcripts:privatize --remove-public
   ```

   The command rechecks each checksum before deleting that public file. It refuses to delete paths shared with unrelated media records and reports failures with a nonzero exit status. It can be rerun after interruption. Originals missing from both disks are reported as errors.

6. Confirm the old `/storage/...` URLs for originals and captions return 404. Purge those URLs from any CDN or reverse-proxy caches, including cached `/vtt/...` responses: previous caption responses allowed long-lived public caching. Audit public storage for orphaned transcripts or caption files from replaced/deleted records; files with no remaining database reference cannot be identified by this command. Resume application traffic and paused jobs.

The migration is not complete while public copies or externally cached copies remain. Laravel maintenance mode alone does not block static `/storage/...` URLs. If those files need immediate protection during the window, block their old paths at the web server/CDN before migration. Previously downloaded copies cannot be revoked.

## Rollback

After public removal, keep the private files and this code available. Rolling back to a release that reads only public storage breaks transcript access. Restore only through an explicitly planned rollback; copying protected transcripts back into public storage exposes their old URLs again.
