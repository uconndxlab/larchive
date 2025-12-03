<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Taxonomy;

class TaxonomySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default "Tags" taxonomy
        Taxonomy::firstOrCreate(
            ['key' => 'tags'],
            [
                'name' => 'Tags',
                'description' => 'General-purpose tags for categorizing content',
                'hierarchical' => false,
            ]
        );
    }
}
