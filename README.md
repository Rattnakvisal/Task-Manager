# WorkMind

A responsive task management application built with Laravel, Blade, Tailwind CSS, and SQLite. Each user has a private workspace for creating, organizing, filtering, and completing tasks.

## Features

- User registration, sign-in, and sign-out
- Private tasks scoped to the signed-in user
- Dashboard statistics and recent work
- Calendar and priority views
- Today, overdue, and completed task pages with search, priority filters, and pagination
- Task detail pages with checklist, completion, and pin actions
- Search and filtering by status or priority
- Due dates, end dates, and task statuses
- Responsive interface
- Nova AI chat with task creation, automatic Magic Breakdown checklists, safe completion, daily standups, and persisted history
- AI task enhancement and idempotent checklist breakdowns in English or Khmer
- Personalized AI assistance with a local heuristic fallback when Gemini is unavailable

## Task pages

| URL | Purpose |
| --- | --- |
| `/dashboard` | Task overview (`/` redirects here) |
| `/tasks` | My Tasks board with Kanban, list, and grid views |
| `/all-tasks` | Search and filter all your tasks |
| `/tasks/today` | Tasks due today, including completed tasks |
| `/tasks/overdue` | Unfinished tasks due before today |
| `/tasks/completed` | Completed tasks, with an option to reopen them |
| `/tasks/create` | Create a task using a standalone form |
| `/tasks/{task}` | Task details and checklist |
| `/tasks/{task}/edit` | Edit a task |
| `/calendar` | Scheduled tasks |
| `/priority` | Tasks grouped by priority |

All task pages require sign-in and show only the signed-in user's tasks. The old `/completed` web route has been replaced by `/tasks/completed`. Sidebar placeholders for Projects, Team, Analytics, and Settings have been removed.

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

## AI configuration

Set `GEMINI_API_KEY` in `.env` to enable Gemini-backed answers. `GEMINI_MODEL` defaults to `gemini-3.8-flash`; without a key, Nova continues to support task commands and planning through its local heuristic fallback.

The application timezone defaults to `Asia/Phnom_Penh` so phrases such as “today” and “tomorrow” resolve consistently. Override `APP_TIMEZONE` when deploying for another locale.

## Production checklist

- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Set `APP_URL` to the public application URL.
- Use a production database and secure credentials.
- Configure `APP_KEY`, database credentials, and `GEMINI_API_KEY` in the hosting provider's encrypted environment-variable settings. Never commit them to `vercel.json` or another tracked file.
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
