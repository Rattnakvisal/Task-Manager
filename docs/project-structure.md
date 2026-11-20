# WorkMind — រចនាសម្ព័ន្ធ Laravel និង Flutter

Laravel ស្ថិតនៅ repository root ហើយ Flutter ស្ថិតនៅ `mobile/`។ Dependencies កំណត់ក្នុង `composer.json` និង `mobile/pubspec.yaml`។

## Laravel

```text
app/
  Http/
    Controllers/
      Controller.php
      Auth/
        AuthenticatedSessionController.php
        RegisteredUserController.php
      Web/
        AiCopilotController.php
        OnboardingController.php
        ProfileController.php
        TaskController.php
      Api/
        MobileAuthController.php
        MobileProfileController.php
        TaskAlertController.php
        TaskApiController.php
    Middleware/
  Models/
  Notifications/
  Providers/
  Services/
```

- `Web/`: controllers សម្រាប់ web និង endpoints ដែលប្រើ web session។
- `Auth/`: ការចូលគណនី និងចុះឈ្មោះលើ web។
- `Api/`: JSON responses សម្រាប់ web និង mobile។
- `routes/web.php`: web routes; `routes/api.php`: general API និង include `mobile.php`។
- `routes/mobile.php`: `/api/mobile` routes; សកម្មភាពរបស់គណនីប្រើ Sanctum។
- `resources/views/`: Blade templates តាមមុខងារ។
- `database/`: migrations, factories និង seeders។
- `tests/Feature/`, `tests/Unit/`: Laravel tests។
- `api/index.php`, `vercel.json`, `tools/check-deployment.mjs`: deployment។

ឈ្មោះ PHP file ប្រើ `PascalCase` ហើយត្រូវនឹង class។ Namespace ត្រូវនឹង folder តាម PSR-4: `app/Http/Controllers/Api/MobileProfileController.php` → `App\Http\Controllers\Api\MobileProfileController`។

## Flutter

```text
mobile/lib/
  main.dart
  src/
    app.dart
    models/
      app_user.dart
      task.dart
    services/
      api_client.dart
      api_exception.dart
      secure_session_store.dart
      session_store.dart
    state/
      app_state.dart
      focus_session.dart
      focus_timer.dart
      streak_manager.dart
    screens/
      analytics_view.dart
      auth_screen.dart
      focus_view.dart
      home_shell.dart
      onboarding_screen.dart
      profile_view.dart
      task_editor.dart
    utils/
      task_helpers.dart
    widgets/
```

- `main.dart`: បង្កើត API client, secure storage និង AppState។
- `app.dart`: MaterialApp, theme និង session gating។
- `models/`: data models, JSON parsing និង task payload។
- `services/`: HTTP client, API errors និង storage។
- `state/`: authentication, task cache, focus និង streak logic។
- `screens/`: screens, tab views និង navigation shell។
- `widgets/`: UI components ដែលប្រើឡើងវិញ។
- `utils/task_helpers.dart`: pure functions `dateOnly`, `apiDate`, `statusLabel`។
- `mobile/test/`: automated tests; `mobile/tool/`: preview rendering។

Dart file និង folder ប្រើ `snake_case`; class ប្រើ `PascalCase`: `ApiClient` → `api_client.dart`។ Import ត្រង់ទៅ file ដែលកំណត់ class។ File export ចាស់នៅ `src/api.dart`, `src/models.dart`, `src/app_state.dart`, `src/focus_session.dart` និង `src/focus_timer.dart` ត្រូវបានដកចេញ។

```dart
import 'package:task_manager_mobile/src/models/task.dart';
import 'package:task_manager_mobile/src/services/api_client.dart';
import 'package:task_manager_mobile/src/state/app_state.dart';
```

## Profile

`profile_view.dart` គ្រប់គ្រង Profile UI, account editor, sync panel និង sign-out confirmation។ `AppState.updateProfileName` ហៅ `PATCH /api/mobile/profile` ដែលគ្រប់គ្រងដោយ `MobileProfileController`។ អាចកែតែឈ្មោះរបស់គណនីដែលបានចូល; email បង្ហាញសម្រាប់អាន។ ការបរាជ័យអនុញ្ញាតឱ្យ retry ហើយ response ចាស់មិនអាចស្ដារគណនីដែលបាន sign out ឡើងវិញបានទេ។

ត្រូវ deploy Laravel backend ដែលមាន endpoint ថ្មីមុនចែកចាយ mobile build ដែលប្រើការកែឈ្មោះ។ មិនត្រូវការ database migration សម្រាប់ការកែនេះទេ។

## ការពិនិត្យ

ពី repository root:

```powershell
php artisan route:list --except-vendor --json
php artisan test
php vendor/bin/pint --test app routes tests
```

ពី `mobile/`:

```powershell
flutter analyze --no-pub
flutter test --no-pub
flutter test tool/render_previews.dart --dart-define=FLUTTER_SDK=C:/flutter --dart-define=PROFILE_ONLY=true
```

កុំកែ generated files ក្នុង `vendor/`, `node_modules/`, `mobile/build/` និង `.dart_tool/` ដោយដៃ។
