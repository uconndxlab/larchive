<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Exhibit;
use App\Models\ExhibitPage;
use App\Models\Item;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating sample collections...');
        
        // Create main collection
        $oralHistoryCollection = Collection::create([
            'title' => 'Connecticut Oral History Collection',
            'slug' => 'ct-oral-history',
            'description' => 'A collection of oral history interviews documenting the lives and experiences of Connecticut residents from the 1950s through the present day.',
            'published_at' => now(),
        ]);

        $communityArchiveCollection = Collection::create([
            'title' => 'Community Archive Project',
            'slug' => 'community-archive',
            'description' => 'Community-submitted photographs, documents, and stories from Hartford neighborhoods.',
            'published_at' => now(),
        ]);

        $this->command->info('Creating 100 sample items...');

        // Item type distribution
        $itemTypes = ['audio', 'video', 'image', 'document', 'other'];
        $typeWeights = [40, 20, 25, 10, 5]; // 40% audio, 20% video, etc.

        // Sample interview topics
        $topics = [
            'immigration', 'labor history', 'civil rights', 'education',
            'healthcare', 'arts and culture', 'environmental history',
            'urban development', 'family life', 'military service'
        ];

        // Sample interviewer names
        $interviewers = [
            'Dr. Jane Smith', 'Prof. Robert Johnson', 'Sarah Williams',
            'Michael Chen', 'Dr. Maria Garcia', 'James Anderson'
        ];

        // Sample locations
        $locations = [
            'Hartford, CT', 'New Haven, CT', 'Bridgeport, CT', 'Stamford, CT',
            'Waterbury, CT', 'Norwalk, CT', 'Danbury, CT', 'New Britain, CT'
        ];

        $items = [];

        for ($i = 1; $i <= 100; $i++) {
            // Determine item type based on weights
            $rand = rand(1, 100);
            $cumulative = 0;
            $itemType = 'other';
            foreach ($itemTypes as $index => $type) {
                $cumulative += $typeWeights[$index];
                if ($rand <= $cumulative) {
                    $itemType = $type;
                    break;
                }
            }

            // Generate realistic data based on item type
            $year = rand(1950, 2024);
            $month = rand(1, 12);
            $day = rand(1, 28);
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);

            $topic = $topics[array_rand($topics)];
            $interviewer = $interviewers[array_rand($interviewers)];
            $location = $locations[array_rand($locations)];
            
            $firstName = $this->getRandomFirstName();
            $lastName = $this->getRandomLastName();
            $intervieweeName = "{$firstName} {$lastName}";

            // Create title based on item type
            if ($itemType === 'audio' || $itemType === 'video') {
                $title = "Interview with {$intervieweeName}";
            } elseif ($itemType === 'image') {
                $imageTypes = ['photograph', 'portrait', 'group photo', 'street scene'];
                $imageType = $imageTypes[array_rand($imageTypes)];
                $title = ucfirst($imageType) . " - {$intervieweeName}, {$year}";
            } elseif ($itemType === 'document') {
                $docTypes = ['letter', 'diary entry', 'transcript', 'certificate', 'newspaper clipping'];
                $docType = $docTypes[array_rand($docTypes)];
                $title = ucfirst($docType) . " from {$intervieweeName}";
            } else {
                $title = "Item {$i} - {$topic}";
            }

            $description = $this->generateDescription($itemType, $intervieweeName, $topic, $year);

            // Assign to collection (80% to oral history, 20% to community archive)
            $collection = rand(1, 100) <= 80 ? $oralHistoryCollection : $communityArchiveCollection;

            $item = Item::create([
                'collection_id' => $collection->id,
                'item_type' => $itemType,
                'title' => $title,
                'slug' => Str::slug($title) . '-' . $i,
                'description' => $description,
                'published_at' => rand(1, 100) <= 85 ? now() : null, // 85% published
            ]);

            // Add Dublin Core metadata
            $item->metadata()->createMany([
                ['key' => 'dc.creator', 'value' => $interviewer],
                ['key' => 'dc.contributor', 'value' => $intervieweeName],
                ['key' => 'dc.date', 'value' => $dateStr],
                ['key' => 'dc.subject', 'value' => ucwords($topic)],
                ['key' => 'dc.type', 'value' => ucfirst($itemType)],
                ['key' => 'dc.language', 'value' => 'en'],
                ['key' => 'dc.rights', 'value' => 'CC BY-NC 4.0'],
                ['key' => 'oh.interviewer', 'value' => $interviewer],
                ['key' => 'oh.interviewee', 'value' => $intervieweeName],
                ['key' => 'oh.location', 'value' => $location],
            ]);

            // Add duration for audio/video
            if ($itemType === 'audio' || $itemType === 'video') {
                $hours = rand(0, 2);
                $minutes = rand(0, 59);
                $seconds = rand(0, 59);
                $duration = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                
                $item->metadata()->create([
                    'key' => 'oh.duration',
                    'value' => $duration,
                ]);
            }

            $items[] = $item;

            if ($i % 20 === 0) {
                $this->command->info("  Created {$i} items...");
            }
        }

        $this->command->info('Creating sample exhibit...');

        // Create a sample exhibit
        $exhibit = Exhibit::create([
            'title' => 'Voices of Connecticut: A Century of Stories',
            'slug' => 'voices-of-connecticut',
            'description' => 'This exhibit presents oral histories from Connecticut residents spanning seven decades, organized thematically to explore immigration, labor, civil rights, and community life.',
            'credits' => 'Curated by the Digital Archive Team. Special thanks to all interviewees and their families.',
            'theme' => 'timeline',
            'featured' => true,
            'sort_order' => 0,
            'published_at' => now(),
        ]);

        // Create exhibit pages
        $introPage = ExhibitPage::create([
            'exhibit_id' => $exhibit->id,
            'title' => 'Introduction',
            'slug' => 'introduction',
            'content' => "Welcome to 'Voices of Connecticut,' an exhibit showcasing oral histories that document the diverse experiences of Connecticut residents over the past century.\n\nThrough these interviews, photographs, and documents, we explore themes of immigration, work, family, and community that have shaped our state's history.",
            'sort_order' => 0,
        ]);

        // Attach some items to intro page
        $introPage->items()->attach($items[0]->id, [
            'sort_order' => 0,
            'caption' => 'Featured interview highlighting the immigrant experience',
            'layout_position' => 'full',
        ]);

        $immigrationPage = ExhibitPage::create([
            'exhibit_id' => $exhibit->id,
            'title' => 'Immigration Stories',
            'slug' => 'immigration',
            'content' => "Connecticut has been a destination for immigrants from around the world. These oral histories capture the challenges and triumphs of building new lives in a new land.",
            'sort_order' => 1,
        ]);

        // Attach immigration-related items
        $immigrationItems = collect($items)
            ->filter(function($item) {
                return $item->metadata()->where('key', 'dc.subject')->where('value', 'Immigration')->exists();
            })
            ->take(5);

        foreach ($immigrationItems as $index => $item) {
            $immigrationPage->items()->attach($item->id, [
                'sort_order' => $index,
                'caption' => "Interview conducted " . $item->metadata()->where('key', 'dc.date')->first()->value,
                'layout_position' => $index % 2 === 0 ? 'left' : 'right',
            ]);
        }

        $laborPage = ExhibitPage::create([
            'exhibit_id' => $exhibit->id,
            'title' => 'Labor and Industry',
            'slug' => 'labor',
            'content' => "Connecticut's industrial heritage is captured in these interviews with factory workers, union organizers, and business owners who shaped the state's economy.",
            'sort_order' => 2,
        ]);

        // Attach labor-related items
        $laborItems = collect($items)
            ->filter(function($item) {
                return $item->metadata()->where('key', 'dc.subject')->where('value', 'Labor history')->exists();
            })
            ->take(4);

        foreach ($laborItems as $index => $item) {
            $laborPage->items()->attach($item->id, [
                'sort_order' => $index,
                'caption' => $item->description ? Str::limit($item->description, 100) : '',
                'layout_position' => 'gallery',
            ]);
        }

        // Create a sub-page under labor
        $unionPage = ExhibitPage::create([
            'exhibit_id' => $exhibit->id,
            'parent_id' => $laborPage->id,
            'title' => 'Union Organizing',
            'slug' => 'unions',
            'content' => "The labor movement played a crucial role in improving working conditions and wages for Connecticut workers.",
            'sort_order' => 0,
        ]);

        $civilRightsPage = ExhibitPage::create([
            'exhibit_id' => $exhibit->id,
            'title' => 'Civil Rights Movement',
            'slug' => 'civil-rights',
            'content' => "Connecticut's civil rights activists fought for equality and justice in schools, housing, and employment. These stories document their courage and perseverance.",
            'sort_order' => 3,
        ]);

        // Attach civil rights items
        $civilRightsItems = collect($items)
            ->filter(function($item) {
                return $item->metadata()->where('key', 'dc.subject')->where('value', 'Civil rights')->exists();
            })
            ->take(6);

        foreach ($civilRightsItems as $index => $item) {
            $civilRightsPage->items()->attach($item->id, [
                'sort_order' => $index,
                'caption' => "Narrator: " . $item->metadata()->where('key', 'oh.interviewee')->first()->value,
                'layout_position' => $index < 2 ? 'full' : 'gallery',
            ]);
        }

        $this->command->info('Sample data created successfully!');
        $this->command->info('');
        $this->command->info("Collections: 2");
        $this->command->info("Items: 100");
        $this->command->info("Exhibit: 1 (with " . $exhibit->pages()->count() . " pages)");
        $this->command->info('');
        $this->command->info("View the exhibit at: /exhibits/{$exhibit->slug}");
    }

    private function getRandomFirstName(): string
    {
        $names = [
            'James', 'Mary', 'John', 'Patricia', 'Robert', 'Jennifer', 'Michael', 'Linda',
            'William', 'Barbara', 'David', 'Elizabeth', 'Richard', 'Susan', 'Joseph', 'Jessica',
            'Thomas', 'Sarah', 'Charles', 'Karen', 'Christopher', 'Nancy', 'Daniel', 'Lisa',
            'Matthew', 'Betty', 'Anthony', 'Margaret', 'Mark', 'Sandra', 'Donald', 'Ashley',
            'Steven', 'Kimberly', 'Paul', 'Emily', 'Andrew', 'Donna', 'Joshua', 'Michelle'
        ];
        return $names[array_rand($names)];
    }

    private function getRandomLastName(): string
    {
        $names = [
            'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis',
            'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson',
            'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Perez', 'Thompson',
            'White', 'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson', 'Walker',
            'Young', 'Allen', 'King', 'Wright', 'Scott', 'Torres', 'Nguyen', 'Hill', 'Flores'
        ];
        return $names[array_rand($names)];
    }

    private function generateDescription(string $type, string $name, string $topic, int $year): string
    {
        $descriptions = [
            'audio' => "Oral history interview with {$name} discussing their experiences with {$topic} in Connecticut during the {$year}s. The interview covers personal memories, community involvement, and reflections on social change.",
            'video' => "Video interview with {$name} recorded in {$year}. {$name} shares stories about {$topic} and its impact on their life and community in Connecticut.",
            'image' => "Photograph from {$year} related to {$topic}. This image provides visual documentation of {$name}'s involvement in community activities.",
            'document' => "Written document from {$year} related to {$topic}. This primary source offers insight into {$name}'s perspective and experiences during this period.",
            'other' => "Archival material from {$year} concerning {$topic}. Part of {$name}'s contribution to the collection.",
        ];

        return $descriptions[$type] ?? $descriptions['other'];
    }
}
