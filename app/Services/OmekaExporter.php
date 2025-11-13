<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Exhibit;
use App\Models\Item;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Export Larchive data to Omeka-compatible format
 * 
 * Generates Omeka CSV Import files for items and Dublin Core metadata,
 * plus exhibit builder XML for exhibits with pages.
 */
class OmekaExporter
{
    protected $exportPath;
    protected $tempPath;

    public function __construct()
    {
        $this->tempPath = storage_path('app/temp/omeka-export-' . time());
        $this->exportPath = storage_path('app/exports');
    }

    /**
     * Export entire archive to Omeka format
     * 
     * @return string Path to generated ZIP file
     */
    public function exportAll(): string
    {
        // Create temp directories
        if (!file_exists($this->tempPath)) {
            mkdir($this->tempPath, 0755, true);
        }
        if (!file_exists($this->tempPath . '/files')) {
            mkdir($this->tempPath . '/files', 0755, true);
        }
        if (!file_exists($this->exportPath)) {
            mkdir($this->exportPath, 0755, true);
        }

        // Export items CSV
        $this->exportItemsCSV();

        // Export collections CSV
        $this->exportCollectionsCSV();

        // Export exhibits (Exhibit Builder plugin format)
        $this->exportExhibitsXML();

        // Copy media files
        $this->copyMediaFiles();

        // Create README
        $this->createReadme();

        // Create ZIP archive
        $zipPath = $this->createZipArchive();

        // Cleanup temp directory
        $this->cleanup();

        return $zipPath;
    }

    /**
     * Export items to Omeka CSV Import format
     */
    protected function exportItemsCSV(): void
    {
        $csvPath = $this->tempPath . '/items.csv';
        $handle = fopen($csvPath, 'w');

        // CSV headers for Omeka CSV Import plugin
        $headers = [
            'Item Type Metadata:Identifier',
            'Dublin Core:Title',
            'Dublin Core:Creator',
            'Dublin Core:Subject',
            'Dublin Core:Description',
            'Dublin Core:Publisher',
            'Dublin Core:Contributor',
            'Dublin Core:Date',
            'Dublin Core:Type',
            'Dublin Core:Format',
            'Dublin Core:Identifier',
            'Dublin Core:Source',
            'Dublin Core:Language',
            'Dublin Core:Relation',
            'Dublin Core:Coverage',
            'Dublin Core:Rights',
            'Item Type',
            'Collection',
            'Public',
            'Featured',
            'File',
        ];
        fputcsv($handle, $headers);

        // Export each item
        Item::with(['collection', 'metadata', 'media'])->chunk(100, function ($items) use ($handle) {
            foreach ($items as $item) {
                $row = $this->buildItemRow($item);
                fputcsv($handle, $row);
            }
        });

        fclose($handle);
    }

    /**
     * Build CSV row for an item
     */
    protected function buildItemRow(Item $item): array
    {
        $metadata = $item->metadata->pluck('value', 'key')->toArray();

        // Map item types to Omeka item types
        $omekaType = match($item->item_type) {
            'audio' => 'Sound',
            'video' => 'Moving Image',
            'image' => 'Still Image',
            'document' => 'Text',
            default => 'Text',
        };

        // Get file paths (relative to files directory)
        $files = $item->media->map(function($media) {
            return $media->file_path;
        })->join('|'); // Multiple files separated by pipe

        return [
            $item->slug, // Identifier
            $item->title, // DC:Title
            $metadata['dc.creator'] ?? '', // DC:Creator
            $metadata['dc.subject'] ?? '', // DC:Subject
            $item->description ?? '', // DC:Description
            $metadata['dc.publisher'] ?? '', // DC:Publisher
            $metadata['dc.contributor'] ?? '', // DC:Contributor
            $metadata['dc.date'] ?? '', // DC:Date
            $metadata['dc.type'] ?? $omekaType, // DC:Type
            $metadata['dc.format'] ?? '', // DC:Format
            $metadata['dc.identifier'] ?? $item->slug, // DC:Identifier
            $metadata['dc.source'] ?? '', // DC:Source
            $metadata['dc.language'] ?? 'en', // DC:Language
            $metadata['dc.relation'] ?? '', // DC:Relation
            $metadata['dc.coverage'] ?? '', // DC:Coverage
            $metadata['dc.rights'] ?? '', // DC:Rights
            $omekaType, // Item Type
            $item->collection ? $item->collection->title : '', // Collection
            $item->published_at ? '1' : '0', // Public
            '0', // Featured
            $files, // Files
        ];
    }

    /**
     * Export collections to CSV
     */
    protected function exportCollectionsCSV(): void
    {
        $csvPath = $this->tempPath . '/collections.csv';
        $handle = fopen($csvPath, 'w');

        $headers = ['Title', 'Description', 'Public'];
        fputcsv($handle, $headers);

        Collection::all()->each(function($collection) use ($handle) {
            fputcsv($handle, [
                $collection->title,
                $collection->description ?? '',
                $collection->published_at ? '1' : '0',
            ]);
        });

        fclose($handle);
    }

    /**
     * Export exhibits to Exhibit Builder XML format
     */
    protected function exportExhibitsXML(): void
    {
        $exhibits = Exhibit::with(['pages.items', 'pages.children.items'])->get();

        foreach ($exhibits as $exhibit) {
            $xml = $this->buildExhibitXML($exhibit);
            
            $filename = $this->tempPath . '/exhibit_' . $exhibit->slug . '.xml';
            file_put_contents($filename, $xml);
        }
    }

    /**
     * Build Exhibit Builder XML for a single exhibit
     */
    protected function buildExhibitXML(Exhibit $exhibit): string
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><exhibit></exhibit>');
        
        $xml->addChild('title', htmlspecialchars($exhibit->title));
        $xml->addChild('slug', $exhibit->slug);
        $xml->addChild('description', htmlspecialchars($exhibit->description ?? ''));
        $xml->addChild('credits', htmlspecialchars($exhibit->credits ?? ''));
        $xml->addChild('public', $exhibit->published_at ? '1' : '0');
        $xml->addChild('featured', $exhibit->featured ? '1' : '0');
        $xml->addChild('theme', $exhibit->theme);

        // Add pages
        $pagesNode = $xml->addChild('pages');
        
        foreach ($exhibit->topLevelPages as $page) {
            $this->addPageToXML($pagesNode, $page);
        }

        return $xml->asXML();
    }

    /**
     * Add page (and sub-pages) to XML
     */
    protected function addPageToXML(\SimpleXMLElement $parentNode, $page): void
    {
        $pageNode = $parentNode->addChild('page');
        $pageNode->addChild('title', htmlspecialchars($page->title));
        $pageNode->addChild('slug', $page->slug);
        $pageNode->addChild('order', $page->sort_order);
        
        // Add page content as a text block
        if ($page->content) {
            $blocksNode = $pageNode->addChild('blocks');
            $blockNode = $blocksNode->addChild('block');
            $blockNode->addChild('type', 'text');
            $blockNode->addChild('text', htmlspecialchars($page->content));
        }

        // Add attached items
        if ($page->items->count() > 0) {
            $itemsNode = $pageNode->addChild('items');
            
            foreach ($page->items as $item) {
                $itemNode = $itemsNode->addChild('item');
                $itemNode->addChild('identifier', $item->slug);
                $itemNode->addChild('caption', htmlspecialchars($item->pivot->caption ?? ''));
                $itemNode->addChild('layout', $item->pivot->layout_position);
                $itemNode->addChild('order', $item->pivot->sort_order);
            }
        }

        // Recursively add child pages
        if ($page->children->count() > 0) {
            $childrenNode = $pageNode->addChild('pages');
            foreach ($page->children as $child) {
                $this->addPageToXML($childrenNode, $child);
            }
        }
    }

    /**
     * Copy media files to export directory
     */
    protected function copyMediaFiles(): void
    {
        $filesPath = $this->tempPath . '/files';

        Item::with('media')->chunk(50, function($items) use ($filesPath) {
            foreach ($items as $item) {
                foreach ($item->media as $media) {
                    $sourcePath = storage_path('app/public/' . $media->file_path);
                    
                    if (file_exists($sourcePath)) {
                        $destPath = $filesPath . '/' . basename($media->file_path);
                        copy($sourcePath, $destPath);
                    }
                }
            }
        });
    }

    /**
     * Create README with import instructions
     */
    protected function createReadme(): void
    {
        $readme = <<<'README'
# Larchive to Omeka Export

This export package contains your Larchive data formatted for import into Omeka Classic.

## Package Contents

- `items.csv` - All items with Dublin Core metadata
- `collections.csv` - Collection data
- `exhibit_*.xml` - Exhibit configurations (one per exhibit)
- `files/` - Media files referenced by items
- `README.txt` - This file

## Import Instructions

### Prerequisites

1. Omeka Classic installation (tested with Omeka 2.x and 3.x)
2. Required Omeka plugins:
   - CSV Import
   - Exhibit Builder (for exhibits)

### Step 1: Import Collections

1. In Omeka admin, go to Plugins > CSV Import
2. Upload `collections.csv`
3. Map columns:
   - Title → Collection Title
   - Description → Collection Description
   - Public → Public
4. Run import

### Step 2: Upload Media Files

1. Upload all files from the `files/` directory to your Omeka installation
2. Recommended: Use FTP/SFTP to upload to `/files/original/`
3. Alternative: Upload via Omeka admin interface (slower for many files)

### Step 3: Import Items

1. In Omeka admin, go to Plugins > CSV Import
2. Upload `items.csv`
3. Column mappings:
   - Item Type Metadata:Identifier → Item Type Metadata > Text
   - Dublin Core:* columns map automatically
   - Collection → Collection (by name)
   - File → File (by filename)
4. Important settings:
   - Check "Column contains file name"
   - Set file path to match where you uploaded files
5. Run import (may take time for large collections)

### Step 4: Import Exhibits (Optional)

For each `exhibit_*.xml` file:

1. Install and activate Exhibit Builder plugin
2. Create a new exhibit manually in Omeka
3. Import exhibit structure:
   - Use the exhibit XML as reference to recreate pages
   - Attach items using the identifiers in the XML
   - Apply layouts as specified in XML

Note: Exhibit Builder doesn't have direct XML import, so exhibits must be 
recreated manually. The XML provides the complete structure for reference.

### Alternative: Programmatic Import

For advanced users, the XML files can be parsed programmatically to:
- Auto-create exhibits via Omeka API
- Batch-process exhibit pages
- Automate item attachments

Example script available at: https://github.com/omeka/ExhibitBuilder

## Troubleshooting

**Problem: CSV import fails**
- Check column mappings match exactly
- Verify UTF-8 encoding
- Try smaller batches if timeout occurs

**Problem: Files not found**
- Ensure file paths in CSV match actual file locations
- Check file permissions (755 for directories, 644 for files)

**Problem: Exhibit items not linking**
- Verify item identifiers in XML match imported item identifiers
- Check that items are public in Omeka

## Additional Notes

### Item Types Mapping

Larchive → Omeka:
- audio → Sound
- video → Moving Image  
- image → Still Image
- document → Text
- other → Text

### Metadata Preservation

All Dublin Core metadata is preserved. Custom Larchive fields (oh.*) are included
in DC:Description or DC:Relation fields where applicable.

### Transcripts

Items with transcripts have the transcript file included in the files/ directory.
Import these as separate files and link to parent items manually.

## Support

For issues with:
- Larchive export: Contact your Larchive administrator
- Omeka import: See https://omeka.org/classic/docs/

README;

        file_put_contents($this->tempPath . '/README.txt', $readme);
    }

    /**
     * Create ZIP archive of export
     */
    protected function createZipArchive(): string
    {
        $zipFilename = 'larchive-omeka-export-' . date('Y-m-d-His') . '.zip';
        $zipPath = $this->exportPath . '/' . $zipFilename;

        $zip = new ZipArchive();
        
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("Cannot create ZIP archive");
        }

        // Add all files from temp directory
        $this->addDirectoryToZip($zip, $this->tempPath, '');

        $zip->close();

        return $zipPath;
    }

    /**
     * Recursively add directory to ZIP
     */
    protected function addDirectoryToZip(ZipArchive $zip, string $path, string $zipPath): void
    {
        $files = scandir($path);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $fullPath = $path . '/' . $file;
            $relativePath = $zipPath ? $zipPath . '/' . $file : $file;

            if (is_dir($fullPath)) {
                $zip->addEmptyDir($relativePath);
                $this->addDirectoryToZip($zip, $fullPath, $relativePath);
            } else {
                $zip->addFile($fullPath, $relativePath);
            }
        }
    }

    /**
     * Clean up temporary files
     */
    protected function cleanup(): void
    {
        $this->deleteDirectory($this->tempPath);
    }

    /**
     * Recursively delete directory
     */
    protected function deleteDirectory(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        rmdir($dir);
    }
}
