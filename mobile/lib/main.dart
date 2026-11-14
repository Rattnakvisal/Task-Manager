import 'package:flutter/material.dart';

import 'src/api.dart';
import 'src/app.dart';
import 'src/app_state.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(TaskManagerApp(state: AppState(ApiClient(), SecureSessionStore())));
}
