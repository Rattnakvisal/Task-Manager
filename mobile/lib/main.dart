import 'package:flutter/material.dart';

import 'src/app.dart';
import 'src/services/api_client.dart';
import 'src/services/secure_session_store.dart';
import 'src/state/app_state.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(TaskManagerApp(state: AppState(ApiClient(), SecureSessionStore())));
}
