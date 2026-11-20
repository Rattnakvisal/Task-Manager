import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:task_manager_mobile/src/app.dart';
import 'package:task_manager_mobile/src/models/app_user.dart';
import 'package:task_manager_mobile/src/screens/home_shell.dart';
import 'package:task_manager_mobile/src/services/api_client.dart';
import 'package:task_manager_mobile/src/state/app_state.dart';
import 'package:task_manager_mobile/src/widgets/motion.dart';

import 'app_test.dart' show MemoryStore, jsonResponse, user;

void main() {
  testWidgets(
    'saved-session startup loads inside Home without a separate splash page',
    (tester) async {
      final session = Completer<http.Response>();
      final state = AppState(
        ApiClient(
          client: MockClient((request) async {
            if (request.url.path.endsWith('/me')) return session.future;
            return jsonResponse({'tasks': []});
          }),
        ),
        MemoryStore()..values['access_token'] = 'saved',
      );
      await tester.pumpWidget(TaskManagerApp(state: state));
      await tester.pump();
      expect(find.byType(HomeShell), findsOneWidget);
      expect(find.text('Loading your tasks and schedule…'), findsOneWidget);
      expect(find.text('Opening Task Manager'), findsNothing);
      expect(find.text('Hello, Visal'), findsNothing);
      session.complete(jsonResponse({'user': user}));
      await tester.pump();
      await tester.pumpAndSettle();
      expect(find.text('Loading your tasks and schedule…'), findsNothing);
      expect(find.textContaining('Hello,'), findsOneWidget);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox());
      state.dispose();
    },
  );

  testWidgets('all workspace headers stay near the safe area during refresh', (
    tester,
  ) async {
    tester.view.physicalSize = const Size(390, 844);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    final pending = Completer<http.Response>();
    final state =
        AppState(
            ApiClient(client: MockClient((_) => pending.future)),
            MemoryStore(),
          )
          ..user = AppUser.fromJson(user)
          ..starting = false
          ..hasLoadedTasks = true;
    await tester.pumpWidget(
      MaterialApp(
        home: MediaQuery(
          data: const MediaQueryData(padding: EdgeInsets.only(top: 24)),
          child: HomeShell(state: state),
        ),
      ),
    );
    await tester.pump(const Duration(milliseconds: 500));
    final headers = {
      'Home': find.textContaining('Hello,'),
      'Tasks': find.text('WORKSPACE · ALL TASKS'),
      'Calendar': find.text('SCHEDULE & DEADLINES'),
      'Focus': find.text('Focus Session'),
      'Profile': find.text('SETTINGS & ACCOUNT'),
    };
    for (final entry in headers.entries) {
      await tester.tap(find.text(entry.key).last);
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 600));
      expect(entry.value, findsOneWidget);
      expect(
        tester.getTopLeft(entry.value).dy,
        lessThan(92),
        reason: entry.key,
      );
      expect(tester.takeException(), isNull);
    }
    pending.complete(jsonResponse({'tasks': []}));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 600));
    expect(find.text('Syncing workspace…'), findsNothing);
    await tester.pumpWidget(const SizedBox());
    state.dispose();
  });

  testWidgets(
    'skeleton handoff reveals content and removes the outgoing skeleton',
    (tester) async {
      final loaded = ValueNotifier(false);
      await tester.pumpWidget(
        MaterialApp(
          home: ValueListenableBuilder<bool>(
            valueListenable: loaded,
            builder: (_, ready, _) => MotionSwitcher(
              child: SizedBox(
                key: ValueKey(ready),
                height: 200,
                width: 200,
                child: Text(ready ? 'Content' : 'Skeleton'),
              ),
            ),
          ),
        ),
      );
      loaded.value = true;
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 120));
      final fade = tester.widget<FadeTransition>(
        find
            .ancestor(
              of: find.text('Content'),
              matching: find.byType(FadeTransition),
            )
            .first,
      );
      expect(fade.opacity.value, greaterThan(0));
      expect(fade.opacity.value, lessThan(1));
      await tester.pump(const Duration(milliseconds: 400));
      expect(find.text('Skeleton'), findsNothing);
      expect(find.text('Content'), findsOneWidget);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox());
      loaded.dispose();
    },
  );
}
