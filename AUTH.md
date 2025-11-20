# Authentication System

Simple authentication implementation using Laravel's built-in features only.

## Features

- ✅ Two user roles: `standard` and `admin`
- ✅ Login/logout functionality
- ✅ Session-based authentication
- ✅ Password hashing
- ✅ Admin middleware for protected routes
- ✅ Command-line user creation

## User Roles

### Standard User
- Can access all authenticated routes
- Basic access level

### Admin User
- Can access all authenticated routes
- Can access admin-protected routes (using `admin` middleware)
- Displays "Admin" badge in navbar

## Creating Users

Use the artisan command to create users:

```bash
php artisan user:create
```

The command will prompt for:
1. **Name** - User's full name
2. **Email** - Must be unique and valid email format
3. **Role** - Choose between `standard` or `admin`
4. **Password** - Minimum 8 characters, with confirmation

Example session:
```
Create a new user

 Name:
 > Joel Salisbury

 Email:
 > joel@example.com

 User role [standard]:
  [0] standard
  [1] admin
 > 1

 Password (min 8 characters):
 >

 Confirm password:
 >

┌────────┬──────────────────┐
│ Field  │ Value            │
├────────┼──────────────────┤
│ Name   │ Joel Salisbury   │
│ Email  │ joel@example.com │
│ Role   │ admin            │
└────────┴──────────────────┘

 Create this user? (yes/no) [yes]:
 > yes

✓ User created successfully!
┌────┬────────────────┬──────────────────┬───────┐
│ ID │ Name           │ Email            │ Role  │
├────┼────────────────┼──────────────────┼───────┤
│ 1  │ Joel Salisbury │ joel@example.com │ admin │
└────┴────────────────┴──────────────────┴───────┘
```

## Login

Navigate to `/login` or click "Login" in the navbar.

- Email and password authentication
- "Remember me" option for persistent sessions
- Redirects to intended page after login (or home)

## Logout

Click your name in the navbar dropdown and select "Logout".

## Protected Routes

All routes are protected by `auth` middleware:
- Collections (CRUD)
- Items (CRUD)
- Media (upload, manage)
- OHMS (upload)
- Exhibits (CRUD)
- Export

Public routes:
- `/` - Home/welcome page
- `/login` - Login page

## Admin Middleware

To protect routes for admin-only access, use the `admin` middleware:

```php
Route::middleware(['auth', 'admin'])->group(function () {
    // Admin-only routes here
});
```

Or on individual routes:
```php
Route::get('/admin/settings', [SettingsController::class, 'index'])
    ->middleware('admin');
```

## User Model Helpers

```php
// Check if user is admin
Auth::user()->isAdmin(); // returns boolean

// Check if user is standard
Auth::user()->isStandard(); // returns boolean
```

## Blade Directives

```blade
@auth
    <p>User is logged in</p>
@endauth

@guest
    <p>User is not logged in</p>
@endguest

@if(Auth::user()->isAdmin())
    <p>User is an admin</p>
@endif
```

## Database Schema

The `users` table includes:
- `id` - Primary key
- `name` - User's full name
- `email` - Unique email address
- `role` - Enum: `standard` or `admin` (default: `standard`)
- `password` - Hashed password
- `remember_token` - For "remember me" functionality
- `email_verified_at` - Email verification timestamp (nullable)
- `created_at` / `updated_at` - Timestamps

## Files Modified/Created

### Migrations
- `2025_11_20_134422_add_role_to_users_table.php` - Adds role enum to users

### Models
- `app/Models/User.php` - Added `role` to fillable, `isAdmin()` and `isStandard()` helpers

### Controllers
- `app/Http/Controllers/Auth/LoginController.php` - Login/logout logic

### Middleware
- `app/Http/Middleware/EnsureUserIsAdmin.php` - Admin role check
- `bootstrap/app.php` - Register `admin` middleware alias

### Commands
- `app/Console/Commands/CreateUser.php` - Interactive user creation

### Views
- `resources/views/auth/login.blade.php` - Login form
- `resources/views/layouts/app.blade.php` - Updated navbar with user dropdown

### Routes
- `routes/web.php` - Login routes and `auth` middleware group

## Security Notes

- Passwords are hashed using Laravel's bcrypt
- CSRF protection enabled on all forms
- Session regeneration on login
- Session invalidation on logout
- Email validation and uniqueness enforced
- No password reset functionality (admin creates accounts)
