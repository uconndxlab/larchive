<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Services\WebVttGenerator;
use App\Transcripts\TranscriptSourceFactory;
use Illuminate\Console\Command;

class RegenerateVttFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transcripts:regenerate-vtt {--force : Force regeneration even if VTT files exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate WebVTT files for all transcripts with proper UTF-8 encoding';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        
        $this->info('Finding all transcript files...');
        
        // Find all media marked as transcripts
        $transcripts = Media::where('is_transcript', true)->get();
        
        if ($transcripts->isEmpty()) {
            $this->info('No transcripts found.');
            return 0;
        }
        
        $this->info("Found {$transcripts->count()} transcript(s).");
        
        $regenerated = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($transcripts as $transcript) {
            $format = $transcript->format ?? 'unknown';
            
            // Skip VTT files (they don't need conversion)
            if ($format === 'vtt') {
                $this->line("  Skipping {$transcript->filename} (already VTT format)");
                $skipped++;
                continue;
            }
            
            // Skip binary formats (PDF, DOC, DOCX - can't be parsed as text)
            if (in_array($format, ['pdf', 'doc', 'docx'])) {
                $this->line("  Skipping {$transcript->filename} (binary format - cannot convert to VTT)");
                $skipped++;
                continue;
            }
            
            // Check if VTT exists and we're not forcing
            if (!$force && WebVttGenerator::vttExists($transcript)) {
                $this->line("  Skipping {$transcript->filename} (VTT already exists, use --force to regenerate)");
                $skipped++;
                continue;
            }
            
            // Delete existing VTT if forcing
            if ($force) {
                WebVttGenerator::deleteVtt($transcript);
            }
            
            try {
                // Create transcript source and generate VTT
                $source = TranscriptSourceFactory::create($transcript);
                
                if (!$source) {
                    $this->warn("  Could not create transcript source for {$transcript->filename}");
                    $errors++;
                    continue;
                }
                
                $vttPath = $source->getVttPath();
                
                if ($vttPath) {
                    $this->info("  ✓ Generated VTT for {$transcript->filename} → {$vttPath}");
                    $regenerated++;
                } else {
                    $this->warn("  Failed to generate VTT for {$transcript->filename}");
                    $errors++;
                }
                
            } catch (\Exception $e) {
                $this->error("  Error processing {$transcript->filename}: {$e->getMessage()}");
                $errors++;
            }
        }
        
        $this->newLine();
        $this->info("Summary:");
        $this->info("  Regenerated: {$regenerated}");
        $this->info("  Skipped: {$skipped}");
        $this->info("  Errors: {$errors}");
        
        return 0;
    }
}
