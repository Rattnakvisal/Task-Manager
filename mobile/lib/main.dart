import 'package:flutter/material.dart';

import 'src/app.dart';
import 'src/services/api.dart';
import 'src/state/app_state.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(TaskManagerApp(state: AppState(ApiClient(), SecureSessionStore())));
}
