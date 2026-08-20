# Task Manager

A responsive task management application built with Laravel, Blade, Tailwind CSS, and SQLite. Each user has a private workspace for creating, organizing, filtering, and completing tasks.

## Features

- User registration, sign-in, and sign-out
- Private tasks scoped to the signed-in user
- Dashboard statistics and recent work
- Calendar and priority views
- Search and filtering by status or priority
- Due dates, end dates, and task statuses
- Responsive interface

## Requirements

- PHP 8.3 or newer
- Composer
- Node.js 20 or newer with npm
- The PHP SQLite extensions (`pdo_sqlite` and `sqlite3`)

## Installation

Run these commands from the project directory:

```bash
composer run setup
composer run dev
```

The setup command installs dependencies, creates `.env`, generates the application key, prepares the SQLite database, runs migrations, and builds the frontend assets.

Open `http://127.0.0.1:8000`, create an account, and start adding tasks.

## Manual installation

If the automatic setup command is unavailable, run:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve
```

On Windows Command Prompt, replace `cp .env.example .env` with `copy .env.example .env`.

## Development

```bash
composer run dev
```

This starts Laravel, the queue worker, and Vite together. To run the automated checks:

```bash
composer test
vendor/bin/pint --test
npm run build
```

## Database

SQLite is configured by default. Laravel creates `database/database.sqlite` during setup. To use MySQL or PostgreSQL, update the `DB_*` values in `.env`, create the database, and run `php artisan migrate`.

## Production checklist

- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Set `APP_URL` to the public application URL.
- Use a production database and secure credentials.
- Run `php artisan migrate --force` and `npm run build`.
- Point the web server document root to the `public` directory.
- Never upload or share the local `.env` file.

## Project structure

- `app/` — application models, controllers, middleware, and providers
- `database/` — migrations, factories, and seeders
- `resources/` — Blade templates, CSS, and JavaScript
- `routes/` — web and API route definitions
- `tests/` — automated application tests
- `public/` — public web entry point and compiled assets

## License

This purchase grants the usage rights stated on the product's Gumroad page. Third-party packages remain subject to their own licenses.
