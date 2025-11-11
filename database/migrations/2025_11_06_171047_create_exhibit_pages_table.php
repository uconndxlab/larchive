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
        Schema::create('exhibit_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exhibit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('exhibit_pages')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('content')->nullable(); // Main text content
            $table->json('layout_blocks')->nullable(); // JSON structure for flexible content blocks
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            // Composite index for ordered queries within an exhibit
            $table->index(['exhibit_id', 'sort_order']);
            
            // Index for hierarchical queries
            $table->index(['parent_id', 'sort_order']);
            
            // Unique slug per exhibit
            $table->unique(['exhibit_id', 'slug']);
        });
        
        // Pivot table for attaching items to exhibit pages with specific layout
        Schema::create('exhibit_page_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exhibit_page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->text('caption')->nullable();
            $table->string('layout_position')->default('full'); // full, left, right, gallery
            $table->timestamps();
            
            // Index for ordered queries
            $table->index(['exhibit_page_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exhibit_page_item');
        Schema::dropIfExists('exhibit_pages');
    }
};
