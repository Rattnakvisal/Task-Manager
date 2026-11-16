# Task Manager for Android

Flutter app with a new Material 3 interface, connected to the existing Laravel database through `/api/mobile`.

Signed-out users start with the onboarding walkthrough, including returning users. `Get Started` or `Skip` opens registration, `Sign In` opens sign-in, and Android Back returns to onboarding. A valid saved session opens the dashboard directly; signing out returns to onboarding.

The three onboarding pages introduce tasks, priorities and dates, and progress. They support swiping, page indicators, Android Back, reduced motion, and larger text on small screens. Previews: [Plan](previews/onboarding-1.png), [Priorities](previews/onboarding-2.png), [Progress](previews/onboarding-3.png). Regenerate with `flutter test tool/render_onboarding.dart --dart-define=FLUTTER_SDK=C:/flutter`.

Included: sign in/register/sign out, secure token storage and session restoration, dashboard progress, task create/edit/delete/complete, subtasks, tags, pinning, search, status/priority filters, a monthly calendar, account information, and in-app reminders for tasks ending today/tomorrow.

Phone-size previews with sample tasks: [Home](previews/home.png), [Tasks](previews/tasks.png), [Calendar](previews/calendar.png), [Profile](previews/profile.png). Regenerate with `flutter test tool/render_previews.dart --dart-define=FLUTTER_SDK=C:/flutter` (use your Flutter SDK path).

## Run Laravel

From the repository root:

```powershell
composer install
php artisan migrate
php artisan serve --host=0.0.0.0 --port=8000
```

Use the existing Laravel `.env` database configuration. Review the configured database before migrating. The new migration adds `personal_access_tokens`; it does not replace task data. Never expose the development server directly to the internet.

The configured PostgreSQL database has not been migrated as part of this implementation. Run the migration on the intended Laravel server before signing in from Android. To apply only the new token table migration, use `php artisan migrate --path=database/migrations/2026_10_14_000001_create_personal_access_tokens_table.php` after the existing application migrations have been applied.

## Run Android

Requires Flutter 3.47.1 / Dart 3.13.1 or compatible, Android Studio/SDK, and Java supported by Gradle 9.3.1.

```powershell
cd mobile
flutter pub get
flutter run -d <android-device-id>
```

An Android emulator connects to `http://10.0.2.2:8000/api/mobile` by default in debug mode. On the computer, the same Laravel server is available at `http://127.0.0.1:8000`. The emulator uses `10.0.2.2` to reach the computer; its own `localhost` refers to the emulator.

For a physical phone, put the computer and phone on the same Wi-Fi and use the computer's LAN IPv4 address:

```powershell
flutter run -d <android-device-id> --dart-define=API_BASE_URL=http://192.168.1.10:8000/api/mobile
```

Replace the example IP with your computer's address (`ipconfig`). Allow port 8000 through Windows Firewall on your private network if needed. The phone's `localhost` refers to the phone itself. Enable USB debugging and accept the computer authorization prompt on the phone. Use `flutter devices` to find the device ID.

You can also set the server URL from the settings button on the sign-in screen. The URL must end in `/api/mobile`. Do not enter database credentials in the app.

## Build an installable test APK

```powershell
flutter build apk --debug
```

Output: `build/app/outputs/flutter-apk/app-debug.apk`. Debug builds allow HTTP for local development. A physical phone needs the LAN address configured through sign-in settings, or via `--dart-define=API_BASE_URL=...` when building.

Release builds require an HTTPS API URL (passed with `--dart-define=API_BASE_URL=https://your-domain.com/api/mobile` or configured at sign-in). Configure your own release keystore before distribution: the generated Android template currently uses the debug signing key. Do not publish this development build to Google Play.

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
