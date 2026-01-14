# Larchive User Instruction Manual

## Table of Contents

1. [Overview](#overview)
2. [Getting Started](#getting-started)
3. [User Roles & Permissions](#user-roles--permissions)
4. [Creating Collections](#creating-collections)
5. [Creating Items](#creating-items)
6. [Uploading Media](#uploading-media)
   - [Via Web UI](#via-web-ui)
   - [Via FTP/SFTP](#via-ftpsftp)
7. [Creating Exhibits](#creating-exhibits)
8. [Managing Exhibit Pages](#managing-exhibit-pages)
9. [Attaching Items to Exhibits](#attaching-items-to-exhibits)
10. [Publishing & Visibility](#publishing--visibility)
11. [Troubleshooting](#troubleshooting)

---

## Overview

**Larchive** is a digital archival management system built with Laravel, Bootstrap, and HTMX. It enables you to:

- Organize archival materials into **Collections**
- Create **Items** (audio, video, images, documents) with rich metadata
- Upload media files via web interface or FTP/SFTP
- Build narrative **Exhibits** with hierarchical pages
- Control visibility and publication status
- Support oral history projects with OHMS integration

---

## Getting Started

### Prerequisites

- Login credentials provided by your system administrator
- Appropriate user role for the tasks you need to perform (see [User Roles & Permissions](#user-roles--permissions))
- For FTP uploads: SFTP client (e.g., FileZilla, Cyberduck)

### Logging In

1. Navigate to `https://your-larchive-site.com/login`
2. Enter your email and password
3. Click **Login**

### Navigation

After logging in, you'll have access to features based on your role:
- **Collections** - Browse and manage collections (Curators and Admins)
- **Items** - Browse and manage archival items (all authenticated users)
- **Exhibits** - Browse and manage exhibits (Curators and Admins)
- **Admin Menu** - User management, settings, taxonomies (Admins only)
- **My Profile** - View and edit your own profile (all authenticated users)

See [User Roles & Permissions](#user-roles--permissions) for detailed information about what each role can do.

---

## User Roles & Permissions

Larchive has three user roles with different permission levels. The system uses a hierarchical structure where each higher level includes all permissions from the levels below.

### Role Hierarchy

**Contributor** → **Curator** → **Admin**

---

### Contributor (Entry-Level User)

Contributors can create and edit items but cannot publish them directly. This role is ideal for content creators who submit work for review.

#### What Contributors Can Do:

**Items:**
- ✅ Create new items
- ✅ Edit items in **Draft** and **In Review** status
- ✅ View items in **Draft** and **In Review** status
- ✅ Delete their own draft items
- ✅ Submit items for review (change status from Draft to In Review)
- ✅ Upload and manage media files for items they can edit
- ❌ **Cannot** publish items directly
- ❌ **Cannot** archive items
- ❌ **Cannot** edit published items

**Collections:** ❌ No access - cannot create, edit, or delete collections

**Exhibits:** ❌ No access - cannot create, edit, or delete exhibits

**Profile:**
- ✅ View and edit their own profile
- ✅ Change their own password
- ✅ View their activity statistics

**Access:**
- Items Workspace to see their drafts and items in review

#### Typical Workflow:
1. Create items and add metadata
2. Upload media files
3. Submit items for review (In Review status)
4. Wait for Curator or Admin to publish

---

### Curator (Content Manager)

Curators have full content management capabilities and can publish content. This role is ideal for project managers and editors.

#### What Curators Can Do:

**All Contributor permissions, plus:**

**Items:**
- ✅ Publish items (change status to **Published**)
- ✅ Archive items (change status to **Archived**)
- ✅ Edit published items
- ✅ View and edit **all items** regardless of status
- ✅ Delete any item (not just their own drafts)
- ✅ Restore deleted items

**Collections:**
- ✅ Create new collections
- ✅ Edit all collections
- ✅ Publish/archive collections
- ✅ Delete collections
- ✅ Restore deleted collections
- ✅ Manage collection visibility settings

**Exhibits:**
- ✅ Create new exhibits
- ✅ Edit all exhibits
- ✅ Create and manage exhibit pages
- ✅ Publish/archive exhibits
- ✅ Delete exhibits
- ✅ Restore deleted exhibits
- ✅ Manage exhibit visibility settings
- ✅ Attach/detach items to exhibits

**Access:**
- Full access to Items Workspace
- Can approve content submitted by Contributors

#### Typical Workflow:
1. Review items submitted by Contributors
2. Publish approved content
3. Create and manage collections and exhibits
4. Organize content for public presentation

---

### Admin (System Administrator)

Admins have all curator permissions plus full system administration capabilities.

#### What Admins Can Do:

**All Curator permissions, plus:**

**User Management:**
- ✅ View all users and their activity statistics
- ✅ Create new user accounts
- ✅ Edit user profiles (name, email, role)
- ✅ Change user passwords
- ✅ Delete users (except themselves - safety feature)
- ✅ View user activity metrics (items created, files uploaded, last login)

**System Settings:**
- ✅ Manage taxonomies (create, edit, delete custom metadata fields)
- ✅ Manage terms within taxonomies
- ✅ Configure site notices
- ✅ Configure theme settings
- ✅ Access all administrative interfaces

**Enhanced Permissions:**
- ✅ Force delete (permanent deletion) of items, collections, and exhibits
- ✅ Full system access via admin navigation bar

#### Typical Workflow:
1. Manage user accounts and permissions
2. Configure system settings
3. Oversee all content and user activity
4. Perform administrative maintenance tasks

---

### Permission Comparison Table

| Feature | Contributor | Curator | Admin |
|---------|-------------|---------|-------|
| Create/Edit Draft Items | ✓ | ✓ | ✓ |
| Publish Items | ✗ | ✓ | ✓ |
| Manage Collections | ✗ | ✓ | ✓ |
| Manage Exhibits | ✗ | ✓ | ✓ |
| User Management | ✗ | ✗ | ✓ |
| System Settings | ✗ | ✗ | ✓ |
| Taxonomies | ✗ | ✗ | ✓ |
| Own Profile | ✓ | ✓ | ✓ |

---

### Checking Your Role

To see what role you have:
1. Log in to Larchive
2. Your role is displayed in the top navigation bar next to your name
3. Or visit **My Profile** to see your role

### Requesting Role Changes

If you need different permissions:
1. Contact your system administrator
2. Explain what tasks you need to perform
3. Administrators can change your role as needed

**Note:** Only Admins can change user roles.

---

## Creating Collections

Collections group related items together (e.g., "Oral History Project 2023", "Civil Rights Movement Archive").

### Steps to Create a Collection

1. **Navigate to Collections**
   - Click **Collections** in the navigation menu
   - Click **Create New Collection** button

2. **Fill in Basic Information**
   - **Title** (required): Descriptive name for your collection
   - **Slug** (optional): URL-friendly identifier (auto-generated if left blank)
   - **Description** (optional): Overview of the collection's scope and content

3. **Set Visibility**
   - **Public**: Visible to everyone
   - **Authenticated**: Only visible to logged-in users
   - **Hidden**: Only visible to admins

4. **Set Status**
   - **Draft**: Work-in-progress, not published
   - **In Review**: Ready for review before publishing
   - **Published**: Live and visible according to visibility setting
   - **Archived**: No longer actively maintained

5. **Add Taxonomy Terms** (optional)
   - Select relevant tags, subjects, or categories
   - Terms help with filtering and discovery

6. **Save**
   - Click **Create Collection**
   - You'll be redirected to the collections list

### Best Practices

✅ Use clear, descriptive titles (e.g., "Connecticut Labor Movement, 1960-1980")  
✅ Write comprehensive descriptions to help users understand collection scope  
✅ Start with **Draft** status while building the collection  
✅ Add taxonomy terms for better organization  
✅ Use **Hidden** visibility for sensitive materials  

---

## Creating Items

Items are individual archival objects: oral history recordings, photographs, documents, etc.

### Steps to Create an Item

1. **Navigate to Items**
   - Click **Items** in the navigation menu
   - Click **Create New Item** button

2. **Select Collection** (optional)
   - Choose a collection from the dropdown
   - Items can exist without a collection

3. **Choose Item Type** (required)
   - **Audio**: Oral histories, interviews, recordings
   - **Video**: Video interviews, footage
   - **Image**: Photographs, digitized documents
   - **Document**: Text documents, PDFs
   - **Other**: Miscellaneous materials

4. **Fill in Basic Information**
   - **Title** (required): Name of the item
   - **Slug** (optional): URL-friendly identifier
   - **Description** (optional): Detailed description of content

5. **Set Visibility & Status**
   - Same options as Collections (see above)

6. **Add Dublin Core Metadata** (optional)
   - **Creator**: Person/organization who created the work
   - **Date**: Creation date (YYYY-MM-DD format)
   - **Subject**: Topics covered
   - **Language**: Primary language (e.g., "en" for English)
   - **Rights**: Copyright/usage information

7. **Upload Featured Image** (optional)
   - Select an image to represent this item
   - Max size: 10 MB
   - Formats: JPG, PNG, GIF

8. **Upload Transcript** (optional, for audio/video only)
   - Select transcript file
   - Formats: TXT, VTT, SRT, PDF, DOC, DOCX
   - Max size: 10 MB

9. **Save**
   - Click **Create Item**
   - You'll be redirected to the item edit page

### After Creating an Item

After saving, you can:
- Upload main media files (audio, video, images)
- Add supplemental files (research notes, translations)
- Attach FTP-uploaded files
- Edit metadata

---

## Uploading Media

Media files can be uploaded two ways: via the web interface or via FTP/SFTP.

### Via Web UI

Use this method for files under 200 MB or when you have a stable internet connection.

#### Steps

1. **Navigate to Item Edit Page**
   - Go to **Items** > Click on your item > Click **Edit**
   - Scroll to the **Media** section

2. **Select Files**
   - Click **Choose Files** button
   - Select one or multiple files (you can upload multiple at once)
   - Supported formats depend on item type:
     - **Audio**: MP3, WAV, FLAC, OGG, M4A
     - **Video**: MP4, MOV, AVI, WebM
     - **Image**: JPG, PNG, GIF, TIFF, BMP
     - **Document**: PDF, DOC, DOCX, TXT

3. **Add Alt Text** (optional)
   - Provide descriptive text for accessibility
   - Especially important for images

4. **Upload**
   - Click **Upload Media** button
   - Files will be streamed to cloud storage (S3)
   - You'll see upload progress

5. **Processing Status**
   - **Uploaded**: File received, queued for processing
   - **Processing**: Extracting metadata (duration, resolution, etc.)
   - **Ready**: File processed and ready to use
   - **Failed**: Processing error (hover for details)

#### Upload Limits

- **Max file size**: 200 MB (configurable by admin)
- **Max files per upload**: 20
- **Timeout**: 5 minutes

#### What Happens During Upload?

1. File is validated for type and size
2. File is streamed directly to S3 storage (not loaded into memory)
3. Media record is created with "uploaded" status
4. Background job extracts metadata:
   - **Audio/Video**: Duration, bitrate, codec, sample rate
   - **Images**: Dimensions, color profile
5. Status updates to "ready" when processing completes

### Via FTP/SFTP

Use this method for:
- Large files (over 200 MB)
- Bulk uploads of many files
- Unreliable internet connections
- Files already on a server

#### Setup (One-Time)

Contact your system administrator to:
1. Set up SFTP access credentials
2. Get server hostname and port
3. Confirm your upload directory path

#### Directory Structure

Files should be uploaded to:
```
storage/app/public/items/{ITEM_ID}/incoming/
```

**Example**: For item ID `42`, upload to:
```
storage/app/public/items/42/incoming/
```

#### Steps

1. **Get Item ID**
   - Navigate to the item in Larchive
   - Note the item ID from the URL (e.g., `/items/42/edit`)

2. **Connect via SFTP**
   - Open your SFTP client (FileZilla, Cyberduck, etc.)
   - Enter credentials provided by admin:
     - **Host**: `your-larchive-site.com`
     - **Port**: `22` (default SFTP port)
     - **Username**: Your SFTP username
     - **Password**: Your SFTP password
   - Click **Connect**

3. **Navigate to Upload Directory**
   - Navigate to: `/storage/app/public/items/{ITEM_ID}/incoming/`
   - Create the `incoming` folder if it doesn't exist

4. **Upload Files**
   - Drag and drop files from your computer
   - Wait for upload to complete
   - Files can be any size

5. **Attach Files in Larchive**
   - Return to the item edit page in Larchive
   - Scroll to **Unattached Uploads** section (below Media)
   - You'll see a table of uploaded files

6. **Configure Each File**
   - For each file, choose **Attach As**:
     - **Main Media**: Primary content (audio recording, video, etc.)
     - **Supplemental**: Supporting materials (notes, translations, etc.)
   
   - If **Supplemental**, provide:
     - **Label**: Descriptive name (e.g., "Research Notes")
     - **Role**: Type of supplemental file (e.g., "transcript", "notes")
     - **Visibility**: Public, Authenticated, or Hidden

7. **Attach**
   - Click **Attach** button for each file
   - File is moved from `incoming/` to permanent storage
   - Media record is created

#### Best Practices for FTP Uploads

✅ Use clear, descriptive filenames (e.g., `interview_smith_john_2023.mp3`)  
✅ Upload files in batches to keep track  
✅ Check the "Unattached Uploads" section after uploading  
✅ Verify files are attached correctly before deleting originals  
❌ Don't use special characters in filenames (`!@#$%^&*`)  
❌ Don't include spaces (use underscores or hyphens instead)  

#### File Validation

When attaching **Main Media**, the system validates:
- File MIME type matches item type
  - **Audio item** → must be audio/* MIME type
  - **Video item** → must be video/* MIME type
  - **Image item** → must be image/* MIME type

**Supplemental files** can be any type.

#### Troubleshooting FTP Uploads

**Files don't appear in "Unattached Uploads"**
- Verify you uploaded to correct directory: `items/{ITEM_ID}/incoming/`
- Refresh the page
- Check file permissions (should be readable)

**"File not found or not readable" error**
- File may have been moved or deleted
- Check file permissions on server
- Re-upload the file

**MIME type validation error**
- File type doesn't match item type
- Change item type or upload correct file format
- For supplemental files, use "Supplemental" option (no validation)

---

## Creating Exhibits

Exhibits are narrative-driven presentations that organize items into themed, hierarchical pages (similar to Omeka's exhibit system).

### Steps to Create an Exhibit

1. **Navigate to Exhibits**
   - Click **Exhibits** in the navigation menu
   - Click **Create New Exhibit** button

2. **Fill in Basic Information**
   - **Title** (required): Name of your exhibit (e.g., "Women in Labor Movement")
   - **Slug** (optional): URL-friendly identifier
   - **Description** (optional): Summary of exhibit content and goals
   - **Credits** (optional): Acknowledge curators, contributors, sponsors

3. **Choose Theme** (optional)
   - **Default**: Standard layout
   - **Timeline**: Chronological presentation
   - **Gallery**: Image-focused layout
   - *(Themes can be customized by developers)*

4. **Upload Cover Image** (optional)
   - Select hero image for exhibit
   - Max size: 2 MB
   - Recommended dimensions: 1200x600px

5. **Set Visibility**
   - Same options as Collections/Items

6. **Set Publication Status**
   - Check **Published** to make live
   - Leave unchecked for draft

7. **Set Featured Status** (optional)
   - Check **Featured** to highlight on homepage
   - Featured exhibits appear in priority order

8. **Add Taxonomy Terms** (optional)
   - Select relevant tags/categories

9. **Save**
   - Click **Create Exhibit**
   - You'll be redirected to the exhibit view

### After Creating an Exhibit

Next steps:
1. Create pages and sub-pages
2. Attach items to the exhibit or specific pages
3. Publish when ready

---

## Managing Exhibit Pages

Exhibits are organized into hierarchical pages: top-level pages with optional sub-pages (sections).

### Creating a Top-Level Page

1. **Navigate to Exhibit Pages**
   - Go to **Exhibits** > Click your exhibit
   - Click **Manage Pages** or **Add Page** button

2. **Fill in Page Information**
   - **Title** (required): Page name (e.g., "Introduction", "Timeline: 1960s")
   - **Slug** (required): URL segment (auto-generated from title)
   - **Content** (optional): Narrative text for the page (supports Markdown/HTML)

3. **Parent Page**
   - Leave **Parent Page** as *None* for top-level page

4. **Sort Order** (optional)
   - Controls page order in navigation
   - Lower numbers appear first

5. **Save**
   - Click **Create Page**

### Creating a Sub-Page (Section)

1. **Follow steps above** for creating a page
2. **Set Parent Page**
   - Select parent from **Parent Page** dropdown
3. **Save**
   - Sub-page will appear nested under parent in navigation

### Page Hierarchy Example

```
Exhibit: "Civil Rights Movement in Connecticut"
├── Introduction (top-level)
├── Timeline (top-level)
│   ├── 1960s (sub-page)
│   ├── 1970s (sub-page)
│   └── 1980s (sub-page)
├── Key Figures (top-level)
│   ├── Activists (sub-page)
│   └── Organizers (sub-page)
└── Conclusion (top-level)
```

### Editing Pages

1. Navigate to exhibit > Click **Manage Pages**
2. Click **Edit** next to the page
3. Make changes
4. Click **Update Page**

### Reordering Pages

1. Navigate to exhibit > Click **Manage Pages**
2. Use drag-and-drop or edit **Sort Order** field
3. Save changes

---

## Attaching Items to Exhibits

Items can be attached two ways:
1. **To the exhibit** (global) - appears in exhibit's item pool
2. **To specific pages** - appears on that page with layout control

### Attaching Items to Exhibit (Global)

1. **Navigate to Exhibit Edit Page**
   - Go to **Exhibits** > Click exhibit > Click **Edit**

2. **Scroll to Items Section**
   - You'll see attached items list

3. **Add Item**
   - Click **Add Item** or **Search Items** button
   - Search for item by title
   - Click item to attach

4. **Configure Item**
   - **Sort Order**: Controls display order
   - **Caption** (optional): Context for this item in the exhibit

5. **Save**
   - Item is now part of exhibit's item pool

### Attaching Items to Specific Pages

1. **Navigate to Page Edit**
   - Go to **Exhibits** > Click exhibit > **Manage Pages** > Click page > **Edit**

2. **Scroll to Page Items Section**

3. **Add Item**
   - Click **Add Item to Page**
   - Search and select item

4. **Configure Item**
   - **Layout Position**:
     - **Full**: Full width display
     - **Left**: Left side with text wrapping
     - **Right**: Right side with text wrapping
     - **Gallery**: Part of image grid
   - **Caption** (optional): Page-specific caption
   - **Sort Order**: Position on page

5. **Save**
   - Item appears on page with chosen layout

### HTMX Features

The item attachment system uses HTMX for real-time updates:
- Add/remove items without page refresh
- Reorder items via drag-and-drop
- Update captions inline
- See changes immediately

---

## Publishing & Visibility

Larchive uses two separate concepts: **visibility** and **status/publication**.

### Visibility Levels

Controls **who can see** content:

- **Public**: Everyone (including non-logged-in visitors)
- **Authenticated**: Only logged-in users
- **Hidden**: Only admins

### Publication Status

Controls **whether content is ready** to be viewed:

#### For Collections & Items

- **Draft**: Work in progress, not published
- **In Review**: Ready for review by admins
- **Published**: Live according to visibility setting
- **Archived**: No longer active but preserved

#### For Exhibits

- **Published checkbox**: Checked = published, Unchecked = draft
- **Published At**: Timestamp when published

### How They Work Together

| Visibility | Status | Who Can See |
|------------|--------|-------------|
| Public | Published | Everyone |
| Public | Draft | Admins only |
| Authenticated | Published | Logged-in users only |
| Authenticated | Draft | Admins only |
| Hidden | Published | Admins only |
| Hidden | Draft | Admins only |

### Publishing Workflow

**Recommended workflow**:
1. Create content with **Draft** status and **Hidden** visibility
2. Add all items, media, and metadata
3. Change status to **In Review** for peer review
4. After review, set status to **Published**
5. Change visibility to **Authenticated** or **Public**

### Featured Content

- **Featured Exhibits**: Highlighted on homepage
- **Sort Order**: Controls order of featured exhibits
- Only published exhibits appear in featured section

---

## Troubleshooting

### Common Issues & Solutions

#### Cannot Upload Files

**Symptom**: "Maximum file size exceeded" error

**Solutions**:
- Check file size (max 200 MB via web UI)
- Use FTP/SFTP for larger files
- Contact admin to increase upload limit

---

**Symptom**: "Invalid file type" error

**Solutions**:
- Verify file format matches item type
- Check allowed formats for your item type
- Convert file to supported format

---

**Symptom**: Upload stalls or times out

**Solutions**:
- Check internet connection
- Try smaller files
- Use FTP/SFTP for large files
- Retry upload

---

#### Media Processing Issues

**Symptom**: Status stuck on "Processing"

**Solutions**:
- Wait 5-10 minutes (large files take time)
- Refresh the page
- Contact admin if stuck for over 30 minutes

---

**Symptom**: Status shows "Failed"

**Solutions**:
- Hover over status badge to see error message
- File may be corrupted - try re-uploading
- Format may not be supported
- Contact admin with error details

---

#### FTP Upload Issues

**Symptom**: Files don't appear in "Unattached Uploads"

**Solutions**:
- Verify correct directory: `items/{ITEM_ID}/incoming/`
- Refresh page
- Check that item ID matches
- Verify file uploaded successfully

---

**Symptom**: "File not found" when attaching

**Solutions**:
- File may have been deleted
- Check file still exists in `incoming/` directory
- Re-upload file
- Check file permissions

---

**Symptom**: Cannot connect via SFTP

**Solutions**:
- Verify hostname, username, password
- Check port (usually 22)
- Confirm firewall allows SFTP
- Contact admin to verify credentials

---

#### Exhibit & Page Issues

**Symptom**: Items don't appear on exhibit page

**Solutions**:
- Verify item is attached to page (not just exhibit)
- Check item visibility and publication status
- Ensure page is published
- Clear browser cache

---

**Symptom**: Pages in wrong order

**Solutions**:
- Edit page sort orders
- Lower numbers appear first
- Ensure no duplicate sort orders
- Save changes

---

**Symptom**: HTMX not working (no live updates)

**Solutions**:
- Hard refresh browser (Ctrl+Shift+R)
- Check browser console for JavaScript errors
- Verify internet connection
- Clear browser cache

---

#### Visibility & Publishing

**Symptom**: Content published but not visible

**Checklist**:
- Is status set to **Published**?
- Is visibility set to **Public** or **Authenticated**?
- Are you logged in (if Authenticated visibility)?
- Is parent collection/exhibit also published?
- Clear cache and hard refresh

---

**Symptom**: "Unauthorized" or "403 Forbidden" error

**Solutions**:
- Verify you have the required role for the action you're trying to perform
- Check the [User Roles & Permissions](#user-roles--permissions) section
- Log out and log back in
- Contact your administrator to check permissions
- Some actions require Curator or Admin privileges

---

## Best Practices Summary

### General

✅ Start with **Draft** status and **Hidden** visibility  
✅ Use clear, descriptive titles  
✅ Add comprehensive descriptions and metadata  
✅ Test changes before publishing  
✅ Use taxonomy terms for organization  

### Collections

✅ Group related items logically  
✅ Write scope notes in description  
✅ Keep collections focused and manageable  

### Items

✅ Choose correct item type  
✅ Add Dublin Core metadata  
✅ Upload transcripts for audio/video  
✅ Use featured images for better discovery  

### Media Uploads

✅ Use web UI for files under 200 MB  
✅ Use FTP/SFTP for larger files or bulk uploads  
✅ Use clear filenames without special characters  
✅ Verify processing completes successfully  
✅ Add alt text for accessibility  

### Exhibits

✅ Plan page structure before creating  
✅ Use 2-3 levels max (top + sub-pages)  
✅ Write clear, engaging narrative content  
✅ Attach items where they're most relevant  
✅ Use appropriate layout positions for items  
✅ Add credits to acknowledge contributors  

---

## Quick Reference

### File Size Limits

- **Web UI Upload**: 200 MB
- **FTP Upload**: No limit
- **Featured Images**: 10 MB
- **Transcripts**: 10 MB
- **Cover Images**: 2 MB

### Supported File Formats

**Audio**: MP3, WAV, FLAC, OGG, M4A, AAC, WMA  
**Video**: MP4, MOV, AVI, WebM, MKV, FLV  
**Image**: JPG, PNG, GIF, TIFF, BMP, SVG  
**Document**: PDF, DOC, DOCX, TXT, RTF, ODT

### Keyboard Shortcuts

- **Ctrl+S**: Save form (where supported)
- **Esc**: Close modal dialogs
- **Ctrl+Shift+R**: Hard refresh page

### URL Patterns

- Collections: `/collections/{slug}`
- Items: `/items/{slug}`
- Exhibits: `/exhibits/{slug}`
- Exhibit Pages: `/exhibits/{exhibit-slug}/pages/{page-slug}`

---

## Appendix: Technical Details

### System Architecture

- **Framework**: Laravel 12.x (PHP 8.3+)
- **Frontend**: Bootstrap 5 + HTMX
- **Storage**: On-Device or S3-compatible object storage (AWS S3, DigitalOcean Spaces, etc.)
- **Metadata**: getID3 library for audio/video analysis

### Processing Workflow

1. File uploaded → streamed to S3
2. Media record created (status: "uploaded")
3. Background job dispatched
4. Metadata extracted (duration, resolution, codec, etc.)
5. Status updated to "ready"

### Database Tables

- `collections`: Collection records
- `items`: Item records
- `media`: Media files attached to items
- `exhibits`: Exhibit records
- `exhibit_pages`: Exhibit pages (hierarchical)
- `exhibit_item`: Pivot table for exhibit-item relationships
- `exhibit_page_item`: Pivot table for page-item relationships

---

**Document Version**: 1.1  
**Last Updated**: January 13, 2026  
**Larchive Version**: 1.0  
**Generated By**: Claude Sonnet 4.5