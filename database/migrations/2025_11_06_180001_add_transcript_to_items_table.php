<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds a foreign key to the media table for transcript files.
     * This approach keeps transcripts as regular Media records with all
     * the benefits (storage, versioning, metadata) while giving them
     * a first-class relationship on the Item.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->foreignId('transcript_id')
                  ->nullable()
                  ->after('item_type')
                  ->constrained('media')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['transcript_id']);
            $table->dropColumn('transcript_id');
        });
    }
};
