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
        Schema::create('exhibit_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exhibit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->text('caption')->nullable();
            $table->timestamps();
            
            // Ensure unique item per exhibit
            $table->unique(['exhibit_id', 'item_id']);
            
            // Index for ordered queries
            $table->index(['exhibit_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exhibit_item');
    }
};
