# Testing Visibility-Based Routes

## Quick Testing Guide

The routes are now configured so that index and show pages are public, but the content is filtered based on the viewer's authentication level.

## Test Scenarios

### 1. Anonymous User (Guest)

**What they can access:**
- ✅ `/collections` - Lists only public collections
- ✅ `/items` - Lists only public items
- ✅ `/exhibits` - Lists only public exhibits
- ✅ `/collections/{slug}` - View public collections (403 on authenticated/hidden)
- ✅ `/items/{slug}` - View public items (403 on authenticated/hidden)
- ✅ `/exhibits/{slug}` - View public exhibits (403 on authenticated/hidden)

**What they CANNOT access:**
- ❌ `/collections/create` - Redirected to login
- ❌ `/items/create` - Redirected to login
- ❌ Any edit/delete routes - Redirected to login
- ❌ Collections/Items/Exhibits with `visibility = 'authenticated'` or `'hidden'` - 403 Forbidden

### 2. Authenticated Standard User

**What they can access:**
- ✅ `/collections` - Lists public + authenticated collections
- ✅ `/items` - Lists public + authenticated items
- ✅ `/exhibits` - Lists public + authenticated exhibits
- ✅ View any public or authenticated resource (403 on hidden)

**What they CANNOT access:**
- ❌ `/collections/create` - 403 Forbidden (admin only)
- ❌ `/items/create` - 403 Forbidden (admin only)
- ❌ Any edit/delete routes - 403 Forbidden (admin only)
- ❌ Resources with `visibility = 'hidden'` - 403 Forbidden

### 3. Admin User

**What they can access:**
- ✅ Everything - no restrictions
- ✅ All index pages show ALL resources (public, authenticated, hidden)
- ✅ Can view, create, edit, delete any resource
- ✅ Full access to admin routes

## Manual Testing Steps

### Step 1: Test as Guest

1. Open an incognito/private browser window
2. Navigate to `http://your-app.test/collections`
3. You should only see collections with `visibility = 'public'`
4. Try to access an authenticated collection directly - should get 403
5. Try to visit `/collections/create` - should redirect to login

### Step 2: Create Test Data

As an admin, create test collections/items with different visibility levels:

```sql
-- Create public collection
INSERT INTO collections (title, slug, visibility) VALUES ('Public Collection', 'public-collection', 'public');

-- Create authenticated collection
INSERT INTO collections (title, slug, visibility) VALUES ('Members Only', 'members-only', 'authenticated');

-- Create hidden collection
INSERT INTO collections (title, slug, visibility) VALUES ('Admin Only', 'admin-only', 'hidden');
```

Or use the web interface as admin to create collections with different visibility settings.

### Step 3: Test as Standard User

1. Create a standard user (if needed):
   ```bash
   php artisan user:create
   # Choose role: standard
   ```

2. Log in as that user
3. Visit `/collections`
4. You should see both public AND authenticated collections
5. You should NOT see hidden collections
6. Try to edit a collection - should get 403 Forbidden

### Step 4: Test as Admin

1. Log in as admin
2. Visit `/collections`
3. You should see ALL collections (public, authenticated, hidden)
4. You should be able to access all create/edit/delete routes

## Using Artisan Tinker for Testing

```php
php artisan tinker

// Get current user (or null if not authenticated)
$user = auth()->user();

// Test visibility scope as guest
Collection::visibleTo(null)->get();
// Returns only public collections

// Test visibility scope as authenticated user (assuming user id 1 is standard)
$standardUser = User::find(1);
Collection::visibleTo($standardUser)->get();
// Returns public + authenticated collections

// Test visibility scope as admin (assuming user id 2 is admin)
$adminUser = User::find(2);
Collection::visibleTo($adminUser)->get();
// Returns ALL collections
```

## Automated Test Example

Here's a PHPUnit test you could create:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_only_public_collections()
    {
        Collection::factory()->create(['visibility' => 'public']);
        Collection::factory()->create(['visibility' => 'authenticated']);
        Collection::factory()->create(['visibility' => 'hidden']);

        $response = $this->get('/collections');
        
        $response->assertStatus(200);
        // Should show 1 collection (only public)
    }

    public function test_authenticated_user_sees_public_and_authenticated()
    {
        $user = User::factory()->create(['role' => 'standard']);
        
        Collection::factory()->create(['visibility' => 'public']);
        Collection::factory()->create(['visibility' => 'authenticated']);
        Collection::factory()->create(['visibility' => 'hidden']);

        $response = $this->actingAs($user)->get('/collections');
        
        $response->assertStatus(200);
        // Should show 2 collections (public + authenticated)
    }

    public function test_admin_sees_all_collections()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Collection::factory()->create(['visibility' => 'public']);
        Collection::factory()->create(['visibility' => 'authenticated']);
        Collection::factory()->create(['visibility' => 'hidden']);

        $response = $this->actingAs($admin)->get('/collections');
        
        $response->assertStatus(200);
        // Should show 3 collections (all)
    }

    public function test_guest_cannot_view_authenticated_collection()
    {
        $collection = Collection::factory()->create(['visibility' => 'authenticated']);

        $response = $this->get("/collections/{$collection->slug}");
        
        $response->assertStatus(403);
    }

    public function test_standard_user_cannot_access_create_route()
    {
        $user = User::factory()->create(['role' => 'standard']);

        $response = $this->actingAs($user)->get('/collections/create');
        
        $response->assertStatus(403);
    }

    public function test_admin_can_access_create_route()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/collections/create');
        
        $response->assertStatus(200);
    }
}
```

## Expected Behavior Summary

| Route Type | Guest | Standard User | Admin |
|------------|-------|---------------|-------|
| `GET /collections` | Only public | Public + Auth | All |
| `GET /collections/{slug}` (public) | ✅ 200 | ✅ 200 | ✅ 200 |
| `GET /collections/{slug}` (authenticated) | ❌ 403 | ✅ 200 | ✅ 200 |
| `GET /collections/{slug}` (hidden) | ❌ 403 | ❌ 403 | ✅ 200 |
| `GET /collections/create` | ❌ Redirect to login | ❌ 403 | ✅ 200 |
| `POST /collections` | ❌ Redirect to login | ❌ 403 | ✅ 201 |
| `GET /collections/{slug}/edit` | ❌ Redirect to login | ❌ 403 | ✅ 200 |
| `DELETE /collections/{slug}` | ❌ Redirect to login | ❌ 403 | ✅ 302 |

## Troubleshooting

### Issue: Standard user sees "403 Forbidden" on index pages

**Solution:** This shouldn't happen. Index pages use the `visibleTo()` scope which filters results, not authorization checks. Check that the route is NOT in the `admin` middleware group.

### Issue: Guest sees authenticated collections

**Solution:** Check that:
1. The collection's `visibility` is actually set to `authenticated` in the database
2. The controller is using `Auth::user()` (which returns `null` for guests)
3. The `scopeVisibleTo` method in the model is correct

### Issue: Admin middleware redirects to login instead of showing 403

**Solution:** Ensure the `EnsureUserIsAdmin` middleware returns a 403 response, not a redirect:

```php
if (!$request->user() || !$request->user()->isAdmin()) {
    abort(403, 'Unauthorized action.');
}
```

### Issue: Routes not working after changes

**Solution:**
```bash
# Clear route cache
php artisan route:clear

# Verify routes
php artisan route:list

# Clear application cache
php artisan cache:clear
```
