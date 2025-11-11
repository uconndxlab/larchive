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
        Schema::create('exhibits', function (Blueprint $table) {
            $table->id();
            $table->string('title')->index();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('credits')->nullable(); // Curator, contributors, etc.
            $table->string('theme')->default('default'); // For future theming
            $table->string('cover_image')->nullable(); // Path to cover image
            $table->boolean('featured')->default(false)->index(); // Feature on homepage
            $table->integer('sort_order')->default(0); // For ordering featured exhibits
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exhibits');
    }
};
