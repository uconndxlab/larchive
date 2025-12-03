<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Taxonomy;
use App\Models\Term;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait SyncsTerms
{
    /**
     * Sync terms for a resource from the request.
     * 
     * Handles:
     * - tag_names: comma-separated tag names (creates new tags if needed)
     * - taxonomy_terms: array of term IDs organized by taxonomy ID
     * 
     * @param Model $resource The model instance (Item, Collection, Exhibit, ExhibitPage)
     * @param Request $request
     * @return void
     */
    protected function syncTerms(Model $resource, Request $request): void
    {
        $termIds = [];

        // Handle tags (special case: create-on-the-fly)
        if ($request->filled('tag_names')) {
            $tagsTaxonomy = Taxonomy::where('key', 'tags')->first();
            
            if ($tagsTaxonomy) {
                $tagNames = array_map('trim', explode(',', $request->input('tag_names')));
                $tagNames = array_filter($tagNames); // Remove empty values
                
                foreach ($tagNames as $tagName) {
                    // Find or create the tag term
                    $term = Term::firstOrCreate(
                        [
                            'taxonomy_id' => $tagsTaxonomy->id,
                            'slug' => Str::slug($tagName),
                        ],
                        [
                            'name' => $tagName,
                        ]
                    );
                    
                    $termIds[] = $term->id;
                }
            }
        }

        // Handle other taxonomies (select from existing terms)
        if ($request->has('taxonomy_terms')) {
            foreach ($request->input('taxonomy_terms', []) as $taxonomyId => $selectedTermIds) {
                if (is_array($selectedTermIds)) {
                    $termIds = array_merge($termIds, $selectedTermIds);
                }
            }
        }

        // Sync all terms at once
        $resource->terms()->sync($termIds);
    }
}
