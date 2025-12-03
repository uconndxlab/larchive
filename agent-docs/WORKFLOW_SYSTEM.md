# Content Workflow & User Roles System

## Overview

Larchive now includes a comprehensive content workflow system with role-based access control, enabling organizations to manage content creation, review, and publication efficiently.

## User Roles

### Three-Tier Role System

1. **Contributor**
   - Can create and edit draft content
   - Can mark items as "In Review" when ready for publication
   - Cannot publish or archive content
   - Access to Items Workspace for managing their content

2. **Curator**
   - All Contributor permissions
   - Can create, edit, and manage Collections and Exhibits
   - Can review and publish content
   - Can archive content
   - Full access to workflow management

3. **Admin**
   - All Curator permissions
   - Can manage users
   - Can manage system settings (taxonomies, site notices, themes)
   - Full system access

### Role Helpers (User Model)

```php
$user->isContributor()  // true for contributor, curator, or admin
$user->isCurator()      // true for curator or admin
$user->isAdmin()        // true for admin only
```

## Content Status Workflow

### Four Status States

All content types (Items, Collections, Exhibits, Exhibit Pages) support these statuses:

1. **Draft**
   - Initial state for new content
   - Private working state
   - Visible only to contributors, curators, and admins in back-end workspace
   - Not shown on public-facing pages

2. **In Review**
   - Content ready for curator review
   - Contributors can move drafts to this state
   - Curators/admins can review and approve or send back to draft
   - Not shown on public-facing pages

3. **Published**
   - Content approved for public viewing
   - Only curators and admins can set this status
   - Visibility controlled by the separate "visibility" field (public/authenticated/hidden)
   - Shown on public-facing pages according to visibility settings

4. **Archived**
   - Retired content
   - Only curators and admins can archive
   - Not normally shown on public pages
   - Preserved for historical reference

### Status + Visibility

The system combines two independent fields:

- **Status**: Controls workflow state (draft → in_review → published → archived)
- **Visibility**: Controls audience (public / authenticated / hidden)

Only items with `status = 'published'` appear on public pages, then filtered by visibility:
- `public`: Everyone can see
- `authenticated`: Only logged-in users
- `hidden`: Only admins (useful for internal reference)

## Permission System

### Policy-Based Authorization

All content operations check permissions via Laravel policies:

#### Items
- **View**: 
  - Admins/curators: see everything
  - Contributors: see drafts, in_review, and published items they can access
  - Public: only published items matching visibility

- **Create**: Contributors and above

- **Update**:
  - Contributors: can edit drafts and in_review items
  - Curators/admins: can edit everything

- **Publish/Archive**: Only curators and admins

- **Delete**:
  - Contributors: can delete own drafts
  - Curators/admins: can delete anything

#### Collections & Exhibits
- Restricted to curators and admins for all operations
- Same status workflow applies

## Admin Workspace

### Items Workspace (`/admin/items/workspace`)

Accessible to contributors, curators, and admins via user dropdown menu.

**Features:**
- Status-based tabs (Draft, In Review, Published, Archived) with counts
- Filtering by collection and search
- Quick status overview with badges
- Role-appropriate actions (edit, delete, view)

**Tab Access:**
- Contributors: Can see all tabs but mainly work in Draft and In Review
- Curators/Admins: Full access to all tabs and bulk operations

### Creating Content

1. Navigate to workspace via user dropdown → "Items Workspace"
2. Click "Create Item"
3. Fill in metadata, upload files
4. Select **Status**:
   - Contributors: Choose "Draft" or "In Review"
   - Curators/Admins: Can also choose "Published" or "Archived"
5. Select **Visibility**: public / authenticated / hidden
6. Save

### Workflow Example

1. **Contributor** creates a new oral history interview:
   - Uploads audio file
   - Adds transcript
   - Sets metadata
   - Status: `draft`, Visibility: `hidden`
   - Saves and continues editing

2. When ready, **Contributor** marks as ready for review:
   - Changes status to `in_review`
   - Curator is notified (or checks workspace)

3. **Curator** reviews the item:
   - Views in "In Review" tab of workspace
   - Checks metadata, files, etc.
   - If approved: Changes status to `published`, sets visibility to `public`
   - If needs work: Changes back to `draft` with notes

4. **Public** can now see the item:
   - Appears in public Items index
   - Appears in Collection views
   - Can be tagged and filtered
   - Shows up in search results

5. Later, **Curator** can archive:
   - Changes status to `archived`
   - Item no longer appears on public pages
   - Still accessible in admin workspace for reference

## Public vs Admin Views

### Public Pages (Front-End)
- Items index: Only `status = 'published'`
- Collections: Only published collections with published items
- Exhibits: Only published exhibits with published pages
- Term browsing: Only published tagged content
- All filtered by visibility settings

### Admin Workspace (Back-End)
- All statuses visible based on role
- Filter and search across any status
- Status badges for quick identification
- Bulk operations available

## Managing Users

Admins can manage user roles via `/admin/users`:

1. Navigate to user dropdown → "Users"
2. Create/edit users
3. Assign role: Contributor / Curator / Admin
4. Set email and password

## Command Line User Creation

For deployment environments (like Laravel Cloud) that don't support interactive commands:

```bash
# Interactive mode
php artisan user:create

# Non-interactive mode
php artisan user:create --name="Jane Curator" --email="jane@example.com" --password="secret123" --role=curator
```

## Database Schema

### Added Fields

**users table:**
- `role` (enum): contributor, curator, admin

**items, collections, exhibits, exhibit_pages tables:**
- `status` (enum): draft, in_review, published, archived

### Model Scopes

All content models include:
```php
Model::published()              // Only published items
Model::withStatus('draft')      // Filter by specific status
Model::visibleTo($user)         // Apply visibility rules
```

## Migration Notes

When migrating from previous versions:
- Existing users with `role = 'standard'` are converted to `contributor`
- Existing users with `role = 'admin'` remain `admin`
- All existing content defaults to `status = 'draft'`
- Review and publish existing content as needed

## Best Practices

1. **Contributors**: Work in drafts, request review when ready
2. **Curators**: Regular review of "In Review" queue
3. **Admins**: Periodic user management and permissions review
4. **Status hygiene**: Archive old content instead of deleting
5. **Visibility planning**: Use `authenticated` for member-only content
6. **Workflow documentation**: Train staff on status transitions

## Future Enhancements

Potential additions:
- Email notifications for status changes
- Ownership tracking (created_by field)
- Revision history
- Batch status updates
- Custom workflow states per content type
- Editorial calendar
