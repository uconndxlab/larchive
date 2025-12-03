<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing role values: standard -> contributor
        DB::table('users')
            ->where('role', 'standard')
            ->update(['role' => 'contributor']);
        
        // SQLite doesn't have enum, so the role column is already flexible
        // The validation happens at the application level
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert role values: contributor -> standard, curator -> standard
        DB::table('users')
            ->whereIn('role', ['contributor', 'curator'])
            ->update(['role' => 'standard']);
    }
};
