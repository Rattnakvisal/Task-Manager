# Vercel deployment

This Laravel application uses the community PHP 8.3 runtime and deploys its
function in Singapore (`sin1`), close to users in Southeast Asia.

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
