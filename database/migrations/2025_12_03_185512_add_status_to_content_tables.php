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
        // Add status to items
        Schema::table('items', function (Blueprint $table) {
            $table->enum('status', ['draft', 'in_review', 'published', 'archived'])
                  ->default('draft')
                  ->after('visibility');
        });

        // Add status to collections
        Schema::table('collections', function (Blueprint $table) {
            $table->enum('status', ['draft', 'in_review', 'published', 'archived'])
                  ->default('draft')
                  ->after('visibility');
        });

        // Add status to exhibits
        Schema::table('exhibits', function (Blueprint $table) {
            $table->enum('status', ['draft', 'in_review', 'published', 'archived'])
                  ->default('draft')
                  ->after('visibility');
        });

        // Add status to exhibit_pages
        Schema::table('exhibit_pages', function (Blueprint $table) {
            $table->enum('status', ['draft', 'in_review', 'published', 'archived'])
                  ->default('draft')
                  ->after('exhibit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('exhibits', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('exhibit_pages', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
