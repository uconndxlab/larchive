<?php

namespace Database\Seeders;

use App\Models\SiteNotice;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SiteNoticeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure exactly one site notice exists
        if (SiteNotice::count() === 0) {
            SiteNotice::create([
                'enabled' => false,
                'title' => 'Welcome to Larchive',
                'body' => '<p>Welcome to Larchive, our digital archive platform.</p><p>By continuing to use this site, you acknowledge that you have read and agree to our terms of use.</p>',
            ]);
        }
    }
}
