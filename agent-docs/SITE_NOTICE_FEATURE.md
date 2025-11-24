# Site Notice / Clickwrap Modal Feature

## Overview
A manageable clickwrap modal system that appears on a user's first visit to the site and does not reappear after acceptance.

## Components Created

### 1. Database
- **Migration**: `2025_11_24_144856_create_site_notices_table.php`
  - Fields: `id`, `enabled`, `title`, `body`, `created_at`, `updated_at`
  - Single-row table (singleton pattern)

- **Model**: `app/Models/SiteNotice.php`
  - Singleton pattern with `instance()` method
  - Helper method `shouldShow()` to check if notice should display

- **Seeder**: `database/seeders/SiteNoticeSeeder.php`
  - Creates initial record with default content
  - Run with: `php artisan db:seed --class=SiteNoticeSeeder`

### 2. Controller
- **File**: `app/Http/Controllers/SiteNoticeController.php`
- **Routes**:
  - `GET /admin/site-notice` - Edit form (admin only)
  - `PUT /admin/site-notice` - Update notice (admin only)
  - `POST /notice/acknowledge` - Public endpoint to acknowledge notice

### 3. Views
- **Admin Interface**: `resources/views/admin/site-notice/edit.blade.php`
  - Toggle to enable/disable notice
  - Text input for title
  - Textarea for HTML body content
  - Live preview of content
  - Bootstrap 5 form styling

- **Modal Component**: `resources/views/components/clickwrap-modal.blade.php`
  - Automatically included in base layout
  - Conditionally renders based on:
    - Notice is enabled
    - Notice has title and body content
    - User hasn't acknowledged (no cookie present)
  - Uses Bootstrap 5 modal
  - Modal has static backdrop (can't dismiss by clicking outside)

### 4. Integration
- Modal is included in `resources/views/layouts/app.blade.php`
- Appears on every page load (public and authenticated)
- No changes needed to individual views

## How It Works

### Admin Workflow
1. Navigate to `/admin/site-notice` (admin users only)
2. Toggle "Enable Site Notice" checkbox
3. Enter a title (e.g., "Welcome to Larchive")
4. Enter HTML content in the body field
5. Preview updates live as you type
6. Click "Save Changes"

### User Experience
1. User visits any page on the site
2. If notice is enabled and user hasn't acknowledged it:
   - Bootstrap modal appears automatically
   - Modal shows the configured title and HTML body
   - User clicks "I Agree" button
3. System sets a cookie: `larchive_notice_acknowledged=true` (expires in 1 year)
4. Modal hides and won't show again until cookie expires or is deleted

### Persistence
- Uses browser cookie: `larchive_notice_acknowledged`
- Expires after 1 year (525,600 minutes)
- Path: `/` (site-wide)
- Set both client-side and server-side for reliability

## HTML Content
The body field accepts HTML. Common tags you can use:
- `<p>` - Paragraphs
- `<strong>`, `<em>` - Bold, italic
- `<a href="">` - Links
- `<ul>`, `<ol>`, `<li>` - Lists
- `<h1>` through `<h6>` - Headings

Example:
```html
<p>Welcome to Larchive, our digital archive platform.</p>
<p>By continuing to use this site, you acknowledge that you have read and agree to our <a href="/terms">terms of use</a>.</p>
<ul>
  <li>All content is for educational purposes</li>
  <li>Please cite sources appropriately</li>
</ul>
```

## Routes Added

### Admin Routes (require authentication + admin role)
- `GET /admin/site-notice` → `admin.site-notice.edit`
- `PUT /admin/site-notice` → `admin.site-notice.update`

### Public Routes
- `POST /notice/acknowledge` → `notice.acknowledge`

## Testing the Feature

### 1. Set up the database:
```bash
php artisan migrate
php artisan db:seed --class=SiteNoticeSeeder
```

### 2. Access the admin interface:
- Log in as an admin user
- Navigate to `/admin/site-notice`
- Enable the notice and add content
- Save changes

### 3. Test as a visitor:
- Open the site in an incognito/private window
- Modal should appear automatically
- Click "I Agree"
- Refresh the page - modal should not appear again

### 4. Reset the cookie:
To test multiple times, clear the cookie in browser DevTools:
- Open DevTools → Application/Storage → Cookies
- Delete `larchive_notice_acknowledged`
- Refresh page to see modal again

## Notes
- Only one site notice record exists (singleton pattern)
- Modal uses Bootstrap 5's built-in modal component
- No external JavaScript libraries needed beyond Bootstrap
- Modal has static backdrop (user must click "I Agree" to dismiss)
- If you want to allow closing without acceptance, modify the modal in `clickwrap-modal.blade.php`
