# Route Restructuring Summary

## Changes Made

The routing structure has been updated to make browsing routes publicly accessible while keeping management routes behind authentication and authorization.

## Route Structure

### Public Routes (No Middleware)

These routes are accessible to everyone, but content is filtered by the `visibleTo()` scope:

```php
GET  /collections              → collections.index (shows filtered list)
GET  /collections/{slug}       → collections.show (403 if not allowed)
GET  /items                    → items.index (shows filtered list)
GET  /items/{slug}             → items.show (403 if not allowed)
GET  /exhibits                 → exhibits.index (shows filtered list)
GET  /exhibits/{slug}          → exhibits.show (403 if not allowed)
GET  /exhibits/{slug}/pages/{page} → exhibits.pages.show (403 if not allowed)
```

**Filtering Behavior:**
- **Guest users**: See only `visibility = 'public'` resources
- **Authenticated users**: See `visibility IN ('public', 'authenticated')` resources
- **Admin users**: See ALL resources regardless of visibility

### Admin Routes (`auth` + `admin` middleware)

These routes require both authentication and admin role:

```php
GET    /collections/create         → collections.create
POST   /collections                → collections.store
GET    /collections/{slug}/edit    → collections.edit
PUT    /collections/{slug}         → collections.update
DELETE /collections/{slug}         → collections.destroy

GET    /items/create               → items.create
POST   /items                      → items.store
GET    /items/{slug}/edit          → items.edit
PUT    /items/{slug}               → items.update
DELETE /items/{slug}               → items.destroy

GET    /exhibits/create            → exhibits.create
POST   /exhibits                   → exhibits.store
GET    /exhibits/{slug}/edit       → exhibits.edit
PUT    /exhibits/{slug}            → exhibits.update
DELETE /exhibits/{slug}            → exhibits.destroy

... all exhibit page management routes
... all media management routes
... all OHMS routes
... all export routes
```

## How It Works

### 1. Index Pages (`/collections`, `/items`, `/exhibits`)

**Controller logic:**
```php
public function index()
{
    $query = Collection::visibleTo(Auth::user());
    // Auth::user() returns null for guests
    // Returns authenticated User for logged-in users
    
    $collections = $query->latest()->paginate(20);
    return view('collections.index', compact('collections'));
}
```

**Visibility Scope (in Model):**
```php
public function scopeVisibleTo(Builder $query, ?User $user): Builder
{
    if ($user && $user->isAdmin()) {
        return $query; // Admins see everything
    }
    
    if ($user) {
        return $query->whereIn('visibility', ['public', 'authenticated']);
    }
    
    return $query->where('visibility', 'public'); // Guests see only public
}
```

### 2. Show Pages (`/collections/{slug}`)

**Controller logic:**
```php
public function show(Collection $collection)
{
    $this->authorize('view', $collection);
    // Throws 403 if user not allowed to view
    
    $collection->load('items');
    return view('collections.show', compact('collection'));
}
```

**Policy logic:**
```php
public function view(?User $user, Collection $collection): bool
{
    if ($user && $user->isAdmin()) {
        return true; // Admins can view everything
    }
    
    if ($collection->visibility === 'public') {
        return true; // Anyone can view public
    }
    
    if ($collection->visibility === 'authenticated' && $user) {
        return true; // Logged-in users can view authenticated
    }
    
    return false; // Hidden or unauthorized
}
```

### 3. Management Pages (Create/Edit/Delete)

**Controller logic:**
```php
public function create()
{
    $this->authorize('create', Collection::class);
    // Throws 403 if not admin
    
    return view('collections.create');
}

public function edit(Collection $collection)
{
    $this->authorize('update', $collection);
    // Throws 403 if not admin
    
    return view('collections.edit', compact('collection'));
}
```

**Policy logic:**
```php
public function create(User $user): bool
{
    return $user->isAdmin(); // Only admins
}

public function update(User $user, Collection $collection): bool
{
    return $user->isAdmin(); // Only admins
}

public function delete(User $user, Collection $collection): bool
{
    return $user->isAdmin(); // Only admins
}
```

## Security Notes

1. **Double Protection**: Admin routes have both:
   - Route middleware (`auth` + `admin`)
   - Policy authorization (`$this->authorize()`)

2. **Visibility Filtering**: Index pages filter at the database level using scopes, preventing information leakage.

3. **Individual Authorization**: Show pages use policies to authorize each request, preventing direct URL access.

4. **Default Safe**: New resources default to `visibility = 'authenticated'`, preventing accidental public exposure.

## Testing

See `ROUTES_TESTING.md` for comprehensive testing instructions.

### Quick Smoke Test

```bash
# As guest (use incognito browser)
curl http://localhost/collections
# Should return 200 with only public collections

curl http://localhost/collections/create
# Should redirect to login (302)

# As admin (after login)
curl -H "Cookie: laravel_session=..." http://localhost/collections/create
# Should return 200
```

## Files Modified

1. `routes/web.php` - Restructured route definitions
2. `VISIBILITY.md` - Updated route documentation
3. `ROUTES_TESTING.md` - Created testing guide (new)
4. `ROUTE_RESTRUCTURE_SUMMARY.md` - This file (new)

## Migration Notes

No database changes required - this is purely a routing and middleware update.

## What Didn't Change

- Controllers already had `visibleTo()` scopes and `authorize()` calls
- Policies already existed with correct logic
- Models already had visibility scopes
- Forms already had visibility selects
- Database already had visibility columns

This was simply reorganizing which routes require authentication middleware.
