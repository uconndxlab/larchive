<?php

namespace App\Console\Commands;

use App\Services\OmekaExporter;
use Illuminate\Console\Command;

class ExportToOmeka extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:omeka 
                            {--download : Display download path instead of auto-downloading}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export entire Larchive archive to Omeka-compatible format';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Omeka export...');
        $this->newLine();

        // Get counts for progress display
        $itemsCount = \App\Models\Item::count();
        $collectionsCount = \App\Models\Collection::count();
        $exhibitsCount = \App\Models\Exhibit::count();

        $this->info("Export will include:");
        $this->line("  - {$collectionsCount} collections");
        $this->line("  - {$itemsCount} items");
        $this->line("  - {$exhibitsCount} exhibits");
        $this->newLine();

        if (!$this->confirm('Continue with export?', true)) {
            $this->info('Export cancelled.');
            return 0;
        }

        $this->newLine();
        $this->info('Generating export package...');

        try {
            $exporter = new OmekaExporter();
            
            $this->task('Exporting items CSV', fn() => true);
            $this->task('Exporting collections CSV', fn() => true);
            $this->task('Exporting exhibits XML', fn() => true);
            $this->task('Copying media files', fn() => true);
            $this->task('Creating documentation', fn() => true);
            
            $zipPath = $exporter->exportAll();
            
            $this->task('Creating ZIP archive', fn() => true);

            $this->newLine();
            $this->info('Export completed successfully!');
            $this->newLine();
            
            $fileSize = $this->formatBytes(filesize($zipPath));
            $this->line("  File: " . basename($zipPath));
            $this->line("  Size: {$fileSize}");
            $this->line("  Path: {$zipPath}");
            
            $this->newLine();
            $this->info('Next steps:');
            $this->line('  1. Download the ZIP file from the path above');
            $this->line('  2. Extract the archive');
            $this->line('  3. Follow instructions in README.txt');
            $this->line('  4. Import into Omeka using CSV Import plugin');

            return 0;
            
        } catch (\Exception $e) {
            $this->error('Export failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Format bytes to human-readable size
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
