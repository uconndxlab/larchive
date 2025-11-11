# Exhibits Feature Documentation

## Overview

The Larchive exhibits feature is inspired by Omeka's robust exhibit system, providing a flexible way to create narrative-driven presentations of your archival items. Exhibits allow you to organize items into pages with hierarchical structure, custom layouts, and rich content.

## Features

### ✅ Core Capabilities

- **Hierarchical Page Structure**: Create top-level pages with nested sub-pages (sections)
- **Flexible Item Attachment**: Attach items to exhibits globally or to specific pages
- **Multiple Layout Options**: Display items in full-width, side-by-side, or gallery layouts
- **Rich Metadata**: Cover images, credits, descriptions, and themes
- **Featured Exhibits**: Highlight important exhibits on your homepage
- **Draft/Publish Workflow**: Work on exhibits privately before publishing
- **HTMX-Powered Interactivity**: Real-time item management without page refreshes

### 🎨 Design Philosophy

Like the rest of Larchive, the exhibits feature follows the "grug-simple" philosophy:
- **Bootstrap + HTMX only** - No complex JavaScript frameworks
- **Standard Laravel patterns** - Resource controllers, Eloquent relationships
- **Production-ready** - Proper validation, indexes, soft deletes

## Database Schema

### Exhibits Table

```php
Schema::create('exhibits', function (Blueprint $table) {
    $table->id();
    $table->string('title')->index();
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->text('credits')->nullable();
    $table->string('theme')->default('default');
    $table->string('cover_image')->nullable();
    $table->boolean('featured')->default(false)->index();
    $table->integer('sort_order')->default(0);
    $table->timestamp('published_at')->nullable()->index();
    $table->timestamps();
    $table->softDeletes();
});
```

**Key Fields:**
- `credits` - Acknowledge curators, contributors, sponsors
- `theme` - Support for different visual themes (default, timeline, gallery)
- `featured` - Flag for homepage/featured section
- `sort_order` - Control ordering of featured exhibits
- `cover_image` - Hero image for the exhibit

### Exhibit Pages Table

```php
Schema::create('exhibit_pages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('exhibit_id')->constrained()->cascadeOnDelete();
    $table->foreignId('parent_id')->nullable()->constrained('exhibit_pages')->cascadeOnDelete();
    $table->string('title');
    $table->string('slug');
    $table->text('content')->nullable();
    $table->json('layout_blocks')->nullable();
    $table->integer('sort_order')->default(0);
    $table->timestamps();
    
    $table->index(['exhibit_id', 'sort_order']);
    $table->index(['parent_id', 'sort_order']);
    $table->unique(['exhibit_id', 'slug']);
});
```

**Features:**
- **Hierarchical**: `parent_id` enables nested pages (sections)
- **Flexible Content**: `layout_blocks` JSON field for future expansion
- **Optimized Queries**: Composite indexes for fast page retrieval

### Pivot Tables

**exhibit_item** (exhibit-level item attachment):
```php
Schema::create('exhibit_item', function (Blueprint $table) {
    $table->id();
    $table->foreignId('exhibit_id')->constrained()->cascadeOnDelete();
    $table->foreignId('item_id')->constrained()->cascadeOnDelete();
    $table->integer('sort_order')->default(0);
    $table->text('caption')->nullable();
    $table->timestamps();
    
    $table->unique(['exhibit_id', 'item_id']);
    $table->index(['exhibit_id', 'sort_order']);
});
```

**exhibit_page_item** (page-level item attachment):
```php
Schema::create('exhibit_page_item', function (Blueprint $table) {
    $table->id();
    $table->foreignId('exhibit_page_id')->constrained()->cascadeOnDelete();
    $table->foreignId('item_id')->constrained()->cascadeOnDelete();
    $table->integer('sort_order')->default(0);
    $table->text('caption')->nullable();
    $table->string('layout_position')->default('full');
    $table->timestamps();
    
    $table->index(['exhibit_page_id', 'sort_order']);
});
```

**Layout Positions:**
- `full` - Full width display
- `left` / `right` - Side-by-side columns
- `gallery` - Grid layout for multiple items

## Model Relationships

### Exhibit Model

```php
// Relationships
$exhibit->items()           // Items attached to the exhibit
$exhibit->pages()           // All pages in order
$exhibit->topLevelPages()   // Only top-level pages (parent_id = null)

// Scopes
Exhibit::published()        // Only published exhibits
Exhibit::featured()         // Featured exhibits in order

// Helpers
$exhibit->isPublished()     // Check if published
$exhibit->publish()         // Publish now
$exhibit->unpublish()       // Unpublish
```

### ExhibitPage Model

```php
// Relationships
$page->exhibit()            // Parent exhibit
$page->parent()             // Parent page (if sub-page)
$page->children()           // Child pages
$page->items()              // Items on this page

// Helpers
$page->isTopLevel()         // Check if top-level page
$page->hasChildren()        // Check for sub-pages
$page->getFullSlug()        // Full path: parent/child/grandchild
$page->getBreadcrumb()      // Array of ancestor pages
```

## Usage Examples

### Creating an Exhibit

```php
$exhibit = Exhibit::create([
    'title' => 'Civil Rights Movement in Connecticut',
    'slug' => 'ct-civil-rights',
    'description' => 'An exploration of the civil rights movement...',
    'credits' => 'Curated by Dr. Jane Smith',
    'theme' => 'timeline',
    'featured' => true,
    'published_at' => now(),
]);

// Upload cover image
if ($request->hasFile('cover_image')) {
    $exhibit->cover_image = $request->file('cover_image')
        ->store('exhibits', 'public');
    $exhibit->save();
}
```

### Creating Pages

```php
// Create top-level page
$introPage = $exhibit->pages()->create([
    'title' => 'Introduction',
    'slug' => 'introduction',
    'content' => 'The civil rights movement in Connecticut...',
    'sort_order' => 0,
]);

// Create sub-page
$timeline1960s = $introPage->children()->create([
    'exhibit_id' => $exhibit->id,
    'title' => '1960s Timeline',
    'slug' => '1960s',
    'content' => 'Key events of the 1960s...',
    'sort_order' => 0,
]);
```

### Attaching Items

```php
// Attach to exhibit
$exhibit->items()->attach($item->id, [
    'sort_order' => 0,
    'caption' => 'Interview with activist...',
]);

// Attach to specific page with layout
$page->items()->attach($item->id, [
    'sort_order' => 0,
    'caption' => 'Audio recording from March 1963',
    'layout_position' => 'left',
]);
```

### Querying

```php
// Get all published exhibits with page counts
$exhibits = Exhibit::published()
    ->withCount('pages')
    ->orderBy('featured', 'desc')
    ->get();

// Get exhibit with full page hierarchy
$exhibit = Exhibit::with(['topLevelPages.children'])
    ->findOrFail($slug);

// Get all items on a page
$pageItems = $page->items()
    ->with('media')
    ->get();
```

## Routes

### Exhibit Routes

```php
GET    /exhibits                    # List all exhibits
GET    /exhibits/create             # Create form
POST   /exhibits                    # Store new exhibit
GET    /exhibits/{exhibit}          # Show exhibit
GET    /exhibits/{exhibit}/edit     # Edit form
PATCH  /exhibits/{exhibit}          # Update exhibit
DELETE /exhibits/{exhibit}          # Delete exhibit
```

### Page Routes (Nested)

```php
GET    /exhibits/{exhibit}/pages                    # List pages
GET    /exhibits/{exhibit}/pages/create             # Create page
POST   /exhibits/{exhibit}/pages                    # Store page
GET    /exhibits/{exhibit}/pages/{page}             # Show page
GET    /exhibits/{exhibit}/pages/{page}/edit        # Edit page
PATCH  /exhibits/{exhibit}/pages/{page}             # Update page
DELETE /exhibits/{exhibit}/pages/{page}             # Delete page
```

### HTMX Endpoints

```php
POST   /exhibits/{exhibit}/items/attach             # Attach item
DELETE /exhibits/{exhibit}/items/{item}             # Detach item
PATCH  /exhibits/{exhibit}/items/reorder            # Reorder items

POST   /exhibits/{exhibit}/pages/{page}/items/attach    # Attach item to page
PATCH  /exhibits/{exhibit}/pages/{page}/items/{item}    # Update item on page
DELETE /exhibits/{exhibit}/pages/{page}/items/{item}    # Detach item from page
PATCH  /exhibits/{exhibit}/pages/reorder                # Reorder pages
PATCH  /exhibits/{exhibit}/pages/{page}/items/reorder   # Reorder page items
```

## UI Components

### Key Views

1. **exhibits/index.blade.php** - Grid of exhibit cards with cover images
2. **exhibits/show.blade.php** - Full exhibit with sidebar navigation
3. **exhibits/create.blade.php** - Create exhibit form
4. **exhibits/edit.blade.php** - Edit exhibit form
5. **exhibits/pages/show.blade.php** - Individual page view with breadcrumbs
6. **exhibits/pages/edit.blade.php** - Page editor with item attachment modal

### HTMX Partials

- **exhibits/partials/items_list.blade.php** - Dynamic exhibit items list
- **exhibits/pages/partials/items_list.blade.php** - Dynamic page items list

These partials enable real-time updates when adding/removing items without full page refresh.

## Comparison with Omeka

### Similarities ✅

- Hierarchical page structure (pages & sub-pages)
- Flexible item attachment to pages
- Multiple layout options for items
- Rich exhibit metadata (credits, themes)
- Draft/publish workflow

### Differences (Intentional) 🎯

- **Simpler Block System**: Text content instead of complex block builders
- **No Theme Marketplace**: Fixed set of themes (extensible in code)
- **Bootstrap-First**: Uses Bootstrap components, not custom CSS
- **HTMX over JavaScript**: Progressive enhancement instead of React/Vue

### What's Not Included (Yet) 📋

- **Exhibit Builder UI**: Drag-and-drop page/item ordering (can be added with Sortable.js)
- **Item Regions**: Text above/below/beside items (use layout_position as starting point)
- **Exhibit Search**: Full-text search within exhibits
- **Public vs Admin Views**: All views are admin-facing (add public templates as needed)

## Best Practices

### Content Organization

1. **Top-Level Pages**: Use for major sections (Introduction, Context, Timeline, Conclusion)
2. **Sub-Pages**: Use for detailed topics within a section
3. **Item Placement**: Attach items to the most specific page where they're discussed
4. **Captions**: Always provide context for items on pages

### Performance

1. **Eager Loading**: Views use `with()` to avoid N+1 queries
2. **Indexes**: Composite indexes on `(exhibit_id, sort_order)` for fast queries
3. **Pagination**: Exhibits index paginates at 20 per page
4. **Image Optimization**: Resize cover images to 1200x600px before upload

### SEO & Accessibility

1. **Slugs**: Use descriptive slugs for exhibits and pages
2. **Alt Text**: Ensure items have proper metadata for screen readers
3. **Breadcrumbs**: Page views include breadcrumb navigation
4. **Semantic HTML**: Use proper heading hierarchy (h1 → h2 → h3)

## Future Enhancements

### Phase 1 (Easy Wins)
- [ ] Drag-and-drop page reordering with Sortable.js
- [ ] Public exhibit views (separate from admin views)
- [ ] Exhibit templates (duplicate existing exhibit structure)
- [ ] Exhibit statistics (views, popular pages)

### Phase 2 (Moderate Effort)
- [ ] Advanced layout blocks (image galleries, timelines, maps)
- [ ] Exhibit themes with CSS customization
- [ ] Collaborative editing (multiple curators per exhibit)
- [ ] Export exhibits to PDF or static HTML

### Phase 3 (Major Features)
- [ ] Visual page builder with live preview
- [ ] Exhibit analytics dashboard
- [ ] Accessibility checker for exhibits
- [ ] Multi-language support

## Troubleshooting

### Common Issues

**Q: Cover images not displaying**
- Ensure storage is linked: `php artisan storage:link`
- Check file permissions on `storage/app/public`
- Verify `FILESYSTEM_DISK=public` in `.env`

**Q: Slugs not auto-generating**
- Model boot() method handles this on create
- If overriding, ensure slug is set before save

**Q: HTMX not working**
- Check browser console for errors
- Verify CSRF token meta tag in layout
- Ensure htmx.org script is loaded

**Q: Pages not in correct order**
- Check `sort_order` values
- Run: `$exhibit->pages()->orderBy('sort_order')->get()`
- Re-save pages to reset order

## Files Reference

### Migrations
- `2025_11_06_171045_create_exhibits_table.php`
- `2025_11_06_171046_create_exhibit_item_table.php`
- `2025_11_06_171047_create_exhibit_pages_table.php`

### Models
- `app/Models/Exhibit.php`
- `app/Models/ExhibitPage.php`

### Controllers
- `app/Http/Controllers/ExhibitController.php`
- `app/Http/Controllers/ExhibitPageController.php`

### Views
- `resources/views/exhibits/*.blade.php`
- `resources/views/exhibits/pages/*.blade.php`
- `resources/views/exhibits/partials/*.blade.php`
- `resources/views/exhibits/pages/partials/*.blade.php`

### Routes
- `routes/web.php` (exhibits section)

---

## Summary

The Larchive exhibits feature provides Omeka-like capabilities for creating rich, narrative-driven presentations of archival materials using **only Bootstrap and HTMX**. The hierarchical page structure, flexible item attachment, and layout options give curators the tools they need without the complexity of modern JavaScript frameworks.

**Key Wins:**
✅ No JavaScript frameworks - just Bootstrap + HTMX  
✅ Hierarchical pages (parent/child relationships)  
✅ Multiple layout options per page  
✅ Real-time item management with HTMX  
✅ Featured exhibits with custom ordering  
✅ Production-ready with proper indexes and validation  

**Next Steps:**
1. Run migrations: `php artisan migrate`
2. Create your first exhibit via `/exhibits/create`
3. Add pages and attach items
4. Publish and feature on homepage

For questions or enhancements, see the Future Enhancements section above.
