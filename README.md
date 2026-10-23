# Larchive

Larchive is a Laravel application for managing archival content, public-facing exhibits, transcript files, media, and site settings. It supports authenticated admin workflows, theme switching, and API access for external integrations.

## Requirements

- PHP 8.3+
- Composer
- Node.js and npm
- A MySQL or SQLite-compatible database

## Setup

1. Copy the environment file:
   ```bash
   cp .env.example .env
   ```
2. Generate an app key:
   ```bash
   php artisan key:generate
   ```
3. Configure the database in `.env`.
4. Install PHP dependencies:
   ```bash
   composer install
   ```
5. Run the database migrations:
   ```bash
   php artisan migrate
   ```

## Run the app

```bash
php artisan serve
```

Then open the site in a browser.

## Admin

Logged-in admin users can manage items, collections, exhibits, users, API tokens, theme settings, and site notices.

## Tests

```bash
php artisan test
```

## Theme installation

Theme packages live under `public/themes/` and can be selected from the admin theme settings.

## License

MIT
