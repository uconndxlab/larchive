<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            // Processing status for async media handling
            $table->enum('processing_status', ['uploading', 'uploaded', 'processing', 'ready', 'failed'])
                  ->default('uploading')
                  ->after('sort_order');
            
            // Store error message if processing fails
            $table->text('processing_error')->nullable()->after('processing_status');
            
            // Timestamp when processing completed
            $table->timestamp('processed_at')->nullable()->after('processing_error');
            
            // Metadata extracted from the file (JSON)
            $table->json('metadata')->nullable()->after('processed_at');
            
            // Add index for querying by status
            $table->index('processing_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex(['processing_status']);
            $table->dropColumn([
                'processing_status',
                'processing_error',
                'processed_at',
                'metadata',
            ]);
        });
    }
};
