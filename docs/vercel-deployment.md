# Vercel: website and Android API

The website and Android app share one Laravel backend and database at
`https://taskflow-ai-kh.vercel.app`. Android uses `/api/mobile`; website JavaScript
uses the session-authenticated `/api/*` endpoints. The app has no server-address form.

The production domain follows deployments of GitHub's `master` branch. Preview
branches keep their own deployment URLs. Deploy a preview and verify it before
merging to `master`; then verify the production domain again.

```powershell
node tools/check-deployment.mjs https://your-preview.vercel.app
node tools/check-deployment.mjs
```

These checks read the website and assets, confirm unauthenticated API responses,
and submit an empty login request to verify validation. They do not create users
or tasks. A protected preview needs Vercel access before the checks can pass.

`api/index.php` is the only PHP function entry point. It loads
`bootstrap/vercel-request.php` so Symfony preserves `/api` instead of inferring it
as the application's base path. Keep that bootstrap file outside `api/` so it
does not become another serverless function. Static files are served before the
Laravel catch-all; PHP source and local Vite development markers are not public.

The Android client defaults to the stable production HTTPS URL. Old saved LAN
addresses are removed at startup. The compatibility route for older deployments
stays on the same HTTPS origin and is only selected after a successful health
check. New deployments use the canonical `/api/mobile` path automatically.

Set `APP_KEY`, database credentials and optional `GEMINI_API_KEY` in Vercel's
environment settings. Keep `VERCEL_RUNTIME_MIGRATE=false`; run reviewed migrations
as a release step against the intended database. Do not run migrations during
requests or use an ephemeral SQLite database for production data.

`.vercelignore` excludes Flutter sources/builds, dependencies supplied by the
remote build, local PHP caches and Vite development files. GitHub's verification
workflow runs the backend suite, website build and Android regression tests.
