# Visibility Controls

Larchive implements a three-tier visibility system for controlling access to Collections, Items, Exhibits, and ExhibitPages.

## Overview

The visibility system provides admins with granular control over which resources are accessible to different user types:

- **Public**: Visible to everyone (logged in or not)
- **Authenticated**: Requires users to be logged in
- **Hidden**: Only visible to administrators

## Database Schema

Each resource has a `visibility` column:

```php
$table->string('visibility')->default('authenticated');
```

The default is `authenticated` to ensure new resources require login by default, preventing accidental public exposure.

## Models

All four models (`Collection`, `Item`, `Exhibit`, `ExhibitPage`) include:

### Fillable Attribute

```php
protected $fillable = [
    // ... other fields
    'visibility',
];
```

### Query Scope

```php
public function scopeVisibleTo(Builder $query, ?User $user): Builder
{
    // Admins see everything
    if ($user && $user->isAdmin()) {
        return $query;
    }

    // Authenticated users see public and authenticated
    if ($user) {
        return $query->whereIn('visibility', ['public', 'authenticated']);
    }

    // Guests see only public
    return $query->where('visibility', 'public');
}
```

## Authorization Policies

Each resource has a corresponding Policy (`CollectionPolicy`, `ItemPolicy`, `ExhibitPolicy`, `ExhibitPagePolicy`) with:

### View Method

```php
public function view(?User $user, Model $model): bool
{
    // Admins can always view
    if ($user && $user->isAdmin()) {
        return true;
    }

    // Check visibility level
    if ($model->visibility === 'public') {
        return true;
    }

    if ($model->visibility === 'authenticated' && $user) {
        return true;
    }

    return false;
}
```

### Admin-Only Methods

```php
public function create(User $user): bool
{
    return $user->isAdmin();
}

public function update(User $user, Model $model): bool
{
    return $user->isAdmin();
}

public function delete(User $user, Model $model): bool
{
    return $user->isAdmin();
}
```

## Controllers

Controllers implement visibility in two ways:

### 1. Index Methods - Use Query Scope

```php
public function index()
{
    $query = Collection::visibleTo(Auth::user());
    
    if (request('search')) {
        $query->where('title', 'like', '%' . request('search') . '%');
    }
    
    $collections = $query->latest()->paginate(20);
    
    return view('collections.index', compact('collections'));
}
```

### 2. Show/Edit/Delete Methods - Use Authorization

```php
public function show(Collection $collection)
{
    $this->authorize('view', $collection);
    
    $collection->load('items');
    return view('collections.show', compact('collection'));
}

public function edit(Collection $collection)
{
    $this->authorize('update', $collection);
    
    return view('collections.edit', compact('collection'));
}

public function destroy(Collection $collection)
{
    $this->authorize('delete', $collection);
    
    $collection->delete();
    return redirect()->route('collections.index')
        ->with('success', 'Collection deleted successfully.');
}
```

## Forms

All create/edit forms include a visibility select field:

```blade
<div class="mb-3">
    <label for="visibility" class="form-label">Visibility <span class="text-danger">*</span></label>
    <select 
        class="form-select @error('visibility') is-invalid @enderror" 
        id="visibility" 
        name="visibility" 
        required
    >
        <option value="public" {{ old('visibility', $model->visibility ?? 'authenticated') == 'public' ? 'selected' : '' }}>
            Public - Visible to everyone
        </option>
        <option value="authenticated" {{ old('visibility', $model->visibility ?? 'authenticated') == 'authenticated' ? 'selected' : '' }}>
            Authenticated - Requires login
        </option>
        <option value="hidden" {{ old('visibility', $model->visibility ?? 'authenticated') == 'hidden' ? 'selected' : '' }}>
            Hidden - Admin only
        </option>
    </select>
    <div class="form-text">Control who can view this resource.</div>
    @error('visibility')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
```

## Validation

All store/update methods validate the visibility field:

```php
$validated = $request->validate([
    // ... other fields
    'visibility' => 'required|in:public,authenticated,hidden',
]);
```

## Usage Examples

### Creating a Public Collection

```php
Collection::create([
    'title' => 'Historical Photos',
    'slug' => 'historical-photos',
    'visibility' => 'public',
]);
```

### Querying Visible Items

```php
// As guest (anonymous user)
$publicItems = Item::visibleTo(null)->get();
// Returns only items with visibility = 'public'

// As authenticated standard user
$items = Item::visibleTo(Auth::user())->get();
// Returns items with visibility = 'public' OR 'authenticated'

// As admin
$allItems = Item::visibleTo(Auth::user())->get();
// Returns all items regardless of visibility
```

### Checking Access in Blade Templates

```blade
@can('view', $collection)
    <a href="{{ route('collections.show', $collection) }}">
        View Collection
    </a>
@endcan

@can('update', $collection)
    <a href="{{ route('collections.edit', $collection) }}" class="btn btn-sm btn-primary">
        Edit
    </a>
@endcan
```

## Routes

### Public Routes (No Authentication Required)

These routes are accessible to all users, but the content is filtered based on visibility:

```php
// Browsing routes - visibility filtering applied in controllers
Route::get('collections', [CollectionController::class, 'index'])->name('collections.index');
Route::get('collections/{collection}', [CollectionController::class, 'show'])->name('collections.show');
Route::get('items', [ItemController::class, 'index'])->name('items.index');
Route::get('items/{item}', [ItemController::class, 'show'])->name('items.show');
Route::get('exhibits', [ExhibitController::class, 'index'])->name('exhibits.index');
Route::get('exhibits/{exhibit}', [ExhibitController::class, 'show'])->name('exhibits.show');
Route::get('exhibits/{exhibit}/pages/{page}', [ExhibitPageController::class, 'show'])->name('exhibits.pages.show');
```

**Behavior by User Type:**

- **Guests (anonymous)**: Index pages show only `visibility = 'public'` resources. Attempting to view an authenticated/hidden resource returns 403.
- **Authenticated Users**: Index pages show `visibility IN ('public', 'authenticated')` resources. Attempting to view a hidden resource returns 403.
- **Admins**: Index pages show ALL resources regardless of visibility. Can view any resource.

### Admin Routes (Require Authentication + Admin Role)

These routes require both authentication and the admin role:

```php
Route::middleware(['auth', 'admin'])->group(function () {
    // Collection management
    Route::get('collections/create', [CollectionController::class, 'create']);
    Route::post('collections', [CollectionController::class, 'store']);
    Route::get('collections/{collection}/edit', [CollectionController::class, 'edit']);
    Route::put('collections/{collection}', [CollectionController::class, 'update']);
    Route::delete('collections/{collection}', [CollectionController::class, 'destroy']);
    
    // Similar routes for items, exhibits, and exhibit pages...
});
```

## Security Considerations

1. **Default to Authenticated**: New resources default to `authenticated` visibility, preventing accidental public exposure.

2. **Policy-Based Authorization**: All view/edit/delete actions go through policies, ensuring consistent access control.

3. **Query Scope Filtering**: Index pages use `visibleTo()` scope to filter results at the database level, preventing information leakage.

4. **Admin Override**: Admins can always view everything, making content management easier while still protecting public/authenticated content.

5. **Form Validation**: Server-side validation ensures only valid visibility values are accepted.

## Best Practices

1. **Set Visibility Early**: Choose the appropriate visibility level when creating resources.

2. **Review Before Publishing**: Check visibility settings before marking items as published.

3. **Consistent Hierarchies**: If an exhibit is `authenticated`, its pages should generally be `authenticated` or `hidden`, not `public`.

4. **Test with Different Users**: Verify visibility works correctly by testing as:
   - Anonymous guest
   - Authenticated standard user
   - Admin user

5. **Audit Trail**: Consider adding logging when visibility changes on sensitive collections.

## Troubleshooting

### "403 Forbidden" on Public Resource

Check that:
1. The resource's `visibility` is set to `public`
2. The route doesn't require authentication middleware
3. The policy's `view()` method handles `null` users

### Can't See Resource as Authenticated User

Check that:
1. You're logged in
2. The resource's visibility is `public` or `authenticated` (not `hidden`)
3. The user account has the correct role

### Changes Not Reflected

1. Clear application cache: `php artisan cache:clear`
2. Clear view cache: `php artisan view:clear`
3. Check database to confirm visibility value was saved

## Future Enhancements

Potential improvements to consider:

- **Role-Based Visibility**: Add more granular roles (curator, researcher, etc.)
- **Time-Based Visibility**: Schedule when resources become public
- **Collection Inheritance**: Child items inherit parent collection visibility by default
- **Visibility Badges**: Display visibility status on admin index pages
- **Bulk Actions**: Change visibility for multiple resources at once
- **Audit Log**: Track visibility changes with timestamps and user info
