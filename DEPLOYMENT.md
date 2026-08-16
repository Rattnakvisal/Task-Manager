# Vercel deployment

This Laravel application uses the community PHP 8.3 runtime and deploys its
function in Singapore (`sin1`), close to users in Southeast Asia.

## Quick import (demo mode)

Upload the generated `task-manager-vercel.zip` archive to a Git repository and
import that repository in Vercel. The archive contains a generated `APP_KEY`
and uses a writable temporary SQLite copy when no remote database is configured.
This removes the HTTP 500 and is useful for evaluation, but data can reset when
Vercel replaces or cold-starts the function.

For persistent accounts and tasks, connect PostgreSQL and use the production
variables below.

## Required production environment variables

Configure these in Vercel for Production, Preview, and Development as needed:

```dotenv
APP_NAME="Task Manager"
APP_ENV=production
APP_DEBUG=false
APP_KEY=<output of: php artisan key:generate --show>
APP_URL=https://<your-vercel-domain>
LOG_CHANNEL=stderr
DB_CONNECTION=pgsql
DB_HOST=<postgres-host>
DB_PORT=5432
DB_DATABASE=<postgres-database>
DB_USERNAME=<postgres-user>
DB_PASSWORD=<postgres-password>
DB_SSLMODE=require
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
CACHE_STORE=database
QUEUE_CONNECTION=sync
```

If a Vercel Marketplace database provides `POSTGRES_URL`, the runtime maps it
to Laravel automatically. You can use either `POSTGRES_URL` or the explicit
`DB_*` variables above; do not configure both with different databases.

Do not use the repository's SQLite database in production. Vercel Functions
have ephemeral local filesystems, so accounts, sessions, and tasks require a
persistent PostgreSQL database.

## Initialize the database

After setting the PostgreSQL variables locally or through the Vercel CLI, run:

```shell
php artisan migrate --force
```

Then deploy from the repository root:

```shell
vercel deploy --prod
```
