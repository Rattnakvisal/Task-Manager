# Task Manager for Android

Flutter app with a new Material 3 interface, connected to the existing Laravel database through `/api/mobile`.

Signed-out users start with the onboarding walkthrough, including returning users. `Get Started` or `Skip` opens registration, `Sign In` opens sign-in, and Android Back returns to onboarding. A valid saved session opens the dashboard directly; signing out returns to onboarding.

Every signed-out app launch starts at onboarding, even after a previous visit or an expired session. Local credentials are read before choosing the screen, so guests do not briefly see the Home workspace. Saved-session verification continues to use the Home loading state.

The three onboarding pages introduce tasks, priorities and dates, and progress. They support swiping, page indicators, Android Back, reduced motion, and larger text on small screens. Previews: [Plan](previews/onboarding-1.png), [Priorities](previews/onboarding-2.png), [Progress](previews/onboarding-3.png). Regenerate with `flutter test tool/render_onboarding.dart --dart-define=FLUTTER_SDK=C:/flutter`.

Included: sign in/register/sign out, secure token storage and session restoration, dashboard progress, task create/edit/delete/complete, subtasks, tags, pinning, search, status/priority filters, a monthly calendar, account information, and in-app reminders for tasks ending today/tomorrow.

Phone-size previews with sample tasks: [Home](previews/home.png), [Tasks](previews/tasks.png), [Calendar](previews/calendar.png), [Profile](previews/profile.png). Regenerate with `flutter test tool/render_previews.dart --dart-define=FLUTTER_SDK=C:/flutter` (use your Flutter SDK path).

Code folders and naming: [Project structure](../docs/project-structure.md).

## Profile and account

The Profile page shows the signed-in account, completed/active tasks and today's focus minutes. Edit profile saves the display name through authenticated `PATCH /api/mobile/profile`; the sign-in email stays read-only. Validation and network errors keep the entered name available for retry.

Cloud Sync refreshes tasks with loading, success, error and retry states. The last refresh time reflects this session. Focus preferences opens the existing Focus tools; App tour reopens onboarding. Sign Out revokes this device's token and blocks duplicate requests.

Deploy the updated Laravel backend before distributing a mobile build that uses profile editing. No database migration is needed. The Profile page exposes implemented actions; subscription checkout, biometric locking, feedback submission and translation controls are not implemented.

Render the updated sample Profile preview with `flutter test tool/render_previews.dart --dart-define=FLUTTER_SDK=C:/flutter --dart-define=PROFILE_ONLY=true`.

## Run Laravel

From the repository root:

```powershell
composer install
php artisan migrate
php artisan serve --host=0.0.0.0 --port=8000
```

Use the existing Laravel `.env` database configuration. Review the configured database before migrating. The new migration adds `personal_access_tokens`; it does not replace task data. Never expose the development server directly to the internet.

The mobile API requires the token-table migration on the intended Laravel server before signing in from Android. To apply only the new token table migration, use `php artisan migrate --path=database/migrations/2026_10_14_000001_create_personal_access_tokens_table.php` after the existing application migrations have been applied.

## Run Android

Requires Flutter 3.47.1 / Dart 3.13.1 or compatible, Android Studio/SDK, and Java supported by Gradle 9.3.1.

```powershell
cd mobile
flutter pub get
flutter run -d <android-device-id>
```

The app automatically connects to `https://taskflow-ai-kh.vercel.app/api/mobile`, matching `env.APP_URL` in the root `vercel.json`. This stable production domain follows the current Vercel production deployment, so daily production deployments do not require a new APK or another server address. Both debug and release builds use it by default.

There is no server-address form. Old saved LAN/USB addresses are removed during startup, and connection failures keep using the configured service. The app does not scan the local network or switch accounts to another server.

The deployed backend must include `routes/mobile.php` and its `/api/mobile` routes. Verify the production endpoint before distributing an APK:

```powershell
curl.exe -H "Accept: application/json" https://taskflow-ai-kh.vercel.app/api/mobile/ping
```

Expected after deploying the routing fix: HTTP 200 with `"status":"ok"`. An older Vercel PHP deployment may strip `/api` when inferring its script base path. On a route 404, the app verifies the compatibility endpoint `https://taskflow-ai-kh.vercel.app/api/api/mobile/ping` and automatically uses that API path for the session. It stays on the same HTTPS origin and can switch back to the canonical path after a later deployment. The backend fix is in `bootstrap/vercel-request.php`, loaded by `api/index.php`.

For local backend development only, override the URL when building/running a debug app. For an emulator:

```powershell
flutter run -d <android-device-id> --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/mobile
```

For a USB-connected physical phone:

```powershell
adb -s <android-device-id> reverse tcp:8000 tcp:8000
flutter run -d <android-device-id> --dart-define=API_BASE_URL=http://127.0.0.1:8000/api/mobile
```

Local `php artisan serve` uses HTTP. Production Vercel uses HTTPS. Do not commit a local `API_BASE_URL` override into the normal IDE launch configuration. No Vercel access token or database password belongs in the app.

## Build an installable test APK

```powershell
flutter build apk --debug
```

Output: `build/app/outputs/flutter-apk/app-debug.apk`. The APK connects to Vercel automatically; a local Laravel server and USB tunnel are only needed for an explicit development override.

Release builds use the production Vercel HTTPS URL by default. A build-time `API_BASE_URL` override must also use HTTPS. Configure your own release keystore before distribution: the generated Android template currently uses the debug signing key. Do not publish this development build to Google Play.

## API contract

| Method | Path under `/api/mobile` | Purpose |
| --- | --- | --- |
| POST | `/register` | name, email, password, password_confirmation, device_name |
| POST | `/login` | email, password, device_name |
| GET | `/me` | Current account |
| POST | `/logout` | Revoke current device token |
| GET | `/tasks` | Tasks and summary statistics; supports q, status, priority |
| POST | `/tasks` | Create a task |
| GET / PUT / PATCH / DELETE | `/tasks/{id}` | Read / update / delete an owned task |
| GET | `/dashboard`, `/calendar`, `/notifications` | Existing task summaries |

Protected endpoints require `Authorization: Bearer <token>` and `Accept: application/json`. Tokens expire after 30 days. Login/register are limited to five requests per minute per IP; authenticated mobile endpoints to 120 requests per minute. Registration uses the same password requirements as the website. Existing website accounts work in the app.

Task writes require title, priority (`low`, `medium`, `high`), and status (`pending`, `in_progress`, `completed`). Other fields: description, category, due_date/end_date (`yyyy-MM-dd` or null), subtasks (`[{id,title,completed}]`), tags (string array), is_pinned (boolean).

## Code Architecture & Directory Structure

The Flutter app follows a Clean Layered Architecture (`lib/src/`):

```
mobile/lib/
├── main.dart                      # Flutter Entrypoint
└── src/
    ├── app.dart                   # Global MaterialApp, Theming & Root Navigation
    ├── models/                    # Data models & JSON serialization (AppUser, Task, Subtask)
    ├── services/                  # Network & Session storage (ApiClient, SecureSessionStore)
    ├── state/                     # Global State & Controllers (AppState, FocusTimer, FocusSession)
    ├── screens/                   # Page Views (Dashboard, Tasks, Focus, Analytics, Auth, Onboarding)
    └── widgets/                   # Reusable UI Components (AnimatedTaskbar, TaskCard, Motion)
```

See the full project architecture guide in [docs/PROJECT_STRUCTURE.md](../docs/PROJECT_STRUCTURE.md).

## Verify

The Flutter app uses lazy sliver lists, a debounced search that retains its text between tabs, and a refresh when the app returns to the foreground after 30 seconds. Concurrent refreshes share one request; writes for the same task are guarded. Pending requests abort on timeout or client disposal, and old session responses cannot restore signed-out data. Credentials are read/written in order.

Tab/card entrances, calendar selection, progress bars, counters, and form resizing use short Flutter animations without additional packages. Custom transitions respect Android's Remove animations accessibility setting. Outgoing transition content ignores taps and accessibility focus.

Regression tests cover the above request/session behavior, long lists, rapid tab switching, reduced motion, and the sign-in/create-task workflow. Phone previews use sample tasks. Smoothness still needs checking on a physical Android device; these automated tests do not measure frame timing.

```powershell
# Repository root
php vendor/bin/pest tests/Feature/MobileApiTest.php

# mobile directory
flutter analyze
flutter test
```

The app uses live API data, with pull-to-refresh. Network failures show retry messages; edits are not saved offline. Reminders are displayed inside the app when opened, based on task end dates. Background push/local notifications, AI features, and profile editing are outside this initial Android version. UI text is English in this first version.
