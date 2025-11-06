# Larchive Oral History Architecture

## Overview

Larchive has been enhanced to support oral history use cases while maintaining flexibility and production sanity. The design uses **flexible metadata** via key-value pairs for Dublin Core fields, **item types** for categorization, and a **first-class transcript relationship** for audio/video items.

---

## Schema Changes

### 1. Item Type Enum

**Migration:** `2025_11_06_180000_add_item_type_to_items_table.php`

```php
$table->enum('item_type', ['audio', 'video', 'image', 'document', 'other'])
      ->default('other')
      ->after('collection_id')
      ->index();
```

**Purpose:** Categorize items by their primary media type. This enables:
- Type-specific UI rendering (audio player, video player, image gallery)
- Filtering and search by type
- Type-specific validation rules
- Conditional display of transcripts (audio/video only)

**Index:** Indexed for fast filtering queries like `WHERE item_type = 'audio'`.

---

### 2. Transcript Relationship

**Migration:** `2025_11_06_180001_add_transcript_to_items_table.php`

```php
$table->foreignId('transcript_id')
      ->nullable()
      ->after('item_type')
      ->constrained('media')
      ->nullOnDelete();
```

**Design Decision:** Transcript as a **foreign key to Media** (preferred approach).

#### Pros:
✅ Transcripts inherit all Media benefits: storage management, versioning, metadata  
✅ No duplicate file handling logic  
✅ Transcripts can have alt text, MIME types, file sizes automatically tracked  
✅ Easier to implement multiple transcript formats (VTT, SRT, TXT, PDF) per item  
✅ Consistent backup/restore with other media files  
✅ Can reorder transcripts with other media if needed  

#### Cons:
❌ Slightly less explicit than a dedicated `transcripts` table  
❌ Requires filtering media to distinguish transcript from other files  

#### Alternative Considered: Dedicated `transcript_path` column

```php
// NOT RECOMMENDED - included for comparison
$table->string('transcript_path')->nullable();
$table->boolean('has_transcript')->default(false);
```

**Why we rejected this:**
- Duplicate file handling logic (upload, storage, deletion)
- No built-in metadata (MIME type, size, version tracking)
- Harder to support multiple formats per item
- More code to maintain

---

## Dublin Core Metadata

### Storage Strategy

Dublin Core fields are stored in the **existing `metadata` table** as key-value pairs:

```
| item_id | key          | value               |
|---------|--------------|---------------------|
| 42      | dc.title     | Interview with Jane |
| 42      | dc.creator   | John Smith          |
| 42      | dc.date      | 2023-05-15          |
| 42      | dc.language  | en                  |
| 42      | oh.interviewer | Sarah Williams    |
```

### Why Key-Value Instead of Columns?

✅ **Flexible:** Add new fields without migrations  
✅ **Sparse:** Don't waste columns for rarely-used fields  
✅ **Extensible:** Support custom fields beyond DC 15  
✅ **Future-proof:** Easy to add oral history extensions (`oh.*` namespace)  
✅ **Search-friendly:** Can index on `(item_id, key)` composite for fast lookups  

❌ **Trade-off:** No database-level type enforcement (handled in app layer)  
❌ **Trade-off:** Joins required for filtering by metadata (mitigated with indexes)  

### Dublin Core Constants

**File:** `app/Models/Concerns/DublinCore.php`

Provides:
- `DublinCore::FIELDS` - Standard DC 15 elements
- `DublinCore::ORAL_HISTORY_FIELDS` - Extensions (`oh.*` namespace)
- `DublinCore::ALL_FIELDS` - Combined whitelist for validation
- `DublinCore::getValidationRules($key)` - Field-specific rules
- `DublinCore::getLabel($key)` - Human-readable labels
- `DublinCore::getGroups()` - UI grouping

### Example: Setting Metadata

```php
// Using helper methods on Item model
$item->setDC('dc.creator', 'Jane Doe');
$item->setDC('dc.date', '2023-05-15');
$item->setDC('oh.interviewer', 'John Smith');

// Retrieve
$creator = $item->getDC('dc.creator'); // "Jane Doe"

// Get all DC fields
$dc = $item->getDublinCore(); 
// ['dc.creator' => 'Jane Doe', 'dc.date' => '2023-05-15']
```

### Validation Example

```php
use App\Models\Concerns\DublinCore;
use Illuminate\Validation\Rule;

$request->validate([
    'metadata' => 'array',
    'metadata.*.key' => ['required', Rule::in(DublinCore::ALL_FIELDS)],
    'metadata.*.value' => 'required|string|max:5000',
]);
```

---

## Model Updates

### Item Model Additions

**File:** `app/Models/Item.php`

```php
protected $fillable = [
    'collection_id',
    'item_type',        // NEW
    'transcript_id',    // NEW
    'title',
    // ... existing fields
];
```

#### New Relationships

```php
// Transcript file (a Media record)
public function transcript()
{
    return $this->belongsTo(Media::class, 'transcript_id');
}
```

#### Helper Methods

```php
// Dublin Core
$item->getDC('dc.creator');
$item->setDC('dc.creator', 'Jane Doe');
$item->getDublinCore(); // all DC fields

// Type checks
$item->isAudio();
$item->isVideo();
$item->hasTranscript();
```

---

## Indexes for Performance

### Existing (from original migrations)

```php
// metadata table (2025_11_06_171045_create_metadata_table.php)
$table->index('key');
$table->index(['item_id', 'key']); // Composite for fast key lookups per item
```

### New

```php
// items table
$table->index('item_type'); // Fast filtering by type
```

### Recommended Future Indexes

If you need to filter by specific metadata values:

```sql
-- For frequent searches on dc.creator, dc.date, etc.
CREATE INDEX idx_metadata_value ON metadata (key, value(255));
```

---

## Validation Rules

### Item Type

```php
use Illuminate\Validation\Rule;

$request->validate([
    'item_type' => ['required', Rule::in(['audio', 'video', 'image', 'document', 'other'])],
]);
```

### Metadata

```php
$request->validate([
    'metadata.*.key' => ['required', Rule::in(DublinCore::ALL_FIELDS)],
    'metadata.*.value' => 'required|string|max:5000',
]);

// Field-specific validation
if ($key === 'dc.date') {
    $rules = ['required', 'date_format:Y-m-d'];
} elseif ($key === 'dc.language') {
    $rules = ['required', 'string', 'size:2']; // ISO 639-1
}
```

### Transcript Upload

```php
$request->validate([
    'transcript' => 'required|file|mimes:txt,vtt,srt,pdf|max:10240', // 10MB
]);

// Store as Media, then set relationship
$media = $item->media()->create([...]);
$item->update(['transcript_id' => $media->id]);
```

---

## Usage Examples

### Creating an Oral History Item

```php
$item = Item::create([
    'collection_id' => 1,
    'item_type' => 'audio',
    'title' => 'Interview with Jane Doe',
    'slug' => 'interview-jane-doe-2023',
    'description' => 'Oral history interview conducted May 2023',
    'published_at' => now(),
]);

// Add Dublin Core metadata
$item->setDC('dc.creator', 'Interviewer: John Smith');
$item->setDC('dc.contributor', 'Narrator: Jane Doe');
$item->setDC('dc.date', '2023-05-15');
$item->setDC('dc.language', 'en');
$item->setDC('dc.rights', 'CC-BY-NC 4.0');
$item->setDC('oh.interviewer', 'John Smith');
$item->setDC('oh.interviewee', 'Jane Doe');
$item->setDC('oh.location', 'Chicago, IL');
$item->setDC('oh.duration', '01:23:45');

// Upload audio file
$audioMedia = $item->media()->create([
    'filename' => 'interview.mp3',
    'path' => $request->file('audio')->store('items/' . $item->id, 'public'),
    'mime_type' => 'audio/mpeg',
    'size' => $request->file('audio')->getSize(),
]);

// Upload transcript
$transcriptMedia = $item->media()->create([
    'filename' => 'interview-transcript.txt',
    'path' => $request->file('transcript')->store('items/' . $item->id, 'public'),
    'mime_type' => 'text/plain',
    'size' => $request->file('transcript')->getSize(),
]);

$item->update(['transcript_id' => $transcriptMedia->id]);
```

### Querying Oral History Items

```php
// All audio items with transcripts
$oralHistories = Item::where('item_type', 'audio')
    ->whereNotNull('transcript_id')
    ->get();

// Items by interviewer
$items = Item::whereHas('metadata', function($q) {
    $q->where('key', 'oh.interviewer')
      ->where('value', 'LIKE', '%John Smith%');
})->get();

// Items from a specific time period (using dc.date)
$items = Item::whereHas('metadata', function($q) {
    $q->where('key', 'dc.date')
      ->whereBetween('value', ['2020-01-01', '2023-12-31']);
})->get();
```

---

## Why This Design Works for Oral History

### 1. **Flexibility Without Chaos**
- Dublin Core provides structure via constants and validation
- Key-value storage allows custom fields (`oh.*` namespace)
- No schema changes needed for new metadata fields

### 2. **First-Class Transcript Support**
- `transcript_id` foreign key makes transcripts explicit in the data model
- Inherits all Media features (storage, versioning, MIME types)
- Easy to query: `WHERE transcript_id IS NOT NULL`
- Can display transcript prominently in UI without filtering media list

### 3. **Type-Driven UI**
- `item_type` enables conditional rendering:
  - Audio items → show audio player + transcript download
  - Video items → show video player + transcript overlay
  - Documents → show document viewer
- Helps users understand what they're looking at

### 4. **Production Sane**
- No extra packages (just Laravel + Bootstrap + HTMX)
- Indexed for common queries (type, metadata keys)
- Validation centralized in `DublinCore` class
- Existing migrations handle everything

### 5. **Scalable Metadata**
- Composite index `(item_id, key)` makes per-item lookups fast
- Can add full-text search on metadata values later
- Easy to export to XML, JSON-LD, or other DC formats

---

## Migration Checklist

Run these in order:

```bash
php artisan migrate
```

Migrations applied:
1. ✅ `2025_11_06_180000_add_item_type_to_items_table.php`
2. ✅ `2025_11_06_180001_add_transcript_to_items_table.php`

---

## Next Steps (Optional Enhancements)

1. **Metadata UI:** Build HTMX forms for adding/editing Dublin Core fields (see `MetadataController`)
2. **Transcript Viewer:** Display VTT/SRT files with timecodes synced to audio player
3. **Advanced Search:** Filter by metadata fields in UI
4. **Batch Import:** CSV import with DC field mapping
5. **Export:** Generate Dublin Core XML or JSON-LD for archival standards compliance
6. **Access Control:** Add `oh.restrictions` metadata enforcement in middleware

---

## Files Modified/Created

### Migrations
- `database/migrations/2025_11_06_180000_add_item_type_to_items_table.php`
- `database/migrations/2025_11_06_180001_add_transcript_to_items_table.php`

### Models
- `app/Models/Item.php` - Added `item_type`, `transcript_id`, Dublin Core helpers
- `app/Models/Concerns/DublinCore.php` - Constants, validation, labels

### Controllers
- `app/Http/Controllers/ItemController.php` - Updated validation for `item_type`
- `app/Http/Controllers/MetadataController.php` - CRUD for Dublin Core metadata

### Documentation
- `ORAL_HISTORY_ARCHITECTURE.md` (this file)

---

## Summary

This architecture keeps Larchive **grug-simple** while giving oral history projects:

✅ Strong Dublin Core support via validated key-value metadata  
✅ First-class transcripts via foreign key to Media  
✅ Type-driven UI with `item_type` enum  
✅ No extra dependencies or complex abstractions  
✅ Production-ready with proper indexes and validation  

**The key insight:** Use the flexibility of key-value storage with the structure of constants and validation. This avoids both the rigidity of hardcoded columns and the chaos of unvalidated free-form fields.
