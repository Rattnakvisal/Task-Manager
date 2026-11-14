import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:task_manager_mobile/src/api.dart';
import 'package:task_manager_mobile/src/app.dart';
import 'package:task_manager_mobile/src/app_state.dart';
import 'package:task_manager_mobile/src/widgets/motion.dart';
import 'package:task_manager_mobile/src/widgets/task_card.dart';

import 'app_test.dart' show MemoryStore, user;

void main() {
  testWidgets(
    'long lists build visible cards and retain search across tab changes',
    (tester) async {
      tester.view.physicalSize = const Size(390, 844);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final state = AppState(
        ApiClient(
          client: MockClient(
            (r) async => http.Response(
              jsonEncode(
                r.url.path.endsWith('/me')
                    ? {'user': user}
                    : {
                        'tasks': List.generate(
                          500,
                          (i) => {
                            'id': i + 1,
                            'title': 'Task $i',
                            'priority': 'medium',
                            'status': 'pending',
                          },
                        ),
                      },
              ),
              200,
            ),
          ),
        ),
        MemoryStore()..values['access_token'] = 'saved',
      );
      await tester.pumpWidget(TaskManagerApp(state: state));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Tasks'));
      await tester.pumpAndSettle();
      expect(find.byType(TaskCard).evaluate().length, lessThan(20));
      expect(find.text('Task 499'), findsNothing);
      await tester.enterText(find.byType(TextField), 'Task 499');
      await tester.pumpAndSettle(const Duration(milliseconds: 200));
      expect(
        find.descendant(
          of: find.byType(TaskCard),
          matching: find.text('Task 499'),
        ),
        findsOneWidget,
      );
      await tester.tap(find.text('Profile'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Tasks'));
      await tester.pumpAndSettle();
      expect(
        tester.widget<TextField>(find.byType(TextField)).controller?.text,
        'Task 499',
      );
      expect(
        find.descendant(
          of: find.byType(TaskCard),
          matching: find.text('Task 499'),
        ),
        findsOneWidget,
      );
      // Repeated tab changes while transitions are in flight must stay safe.
      for (final label in ['Calendar', 'Home', 'Tasks', 'Profile']) {
        await tester.tap(find.widgetWithText(NavigationDestination, label));
        await tester.pump(const Duration(milliseconds: 30));
      }
      await tester.pumpAndSettle();
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox());
      state.dispose();
    },
  );

  testWidgets(
    'custom animations honor reduced motion and block outgoing taps',
    (tester) async {
      final selected = ValueNotifier(false);
      var oldTaps = 0;
      var reduceMotion = false;
      Widget app() => MaterialApp(
        home: MediaQuery(
          data: MediaQueryData(disableAnimations: reduceMotion),
          child: Scaffold(
            body: ValueListenableBuilder<bool>(
              valueListenable: selected,
              builder: (context, value, _) => MotionSwitcher(
                child: TextButton(
                  key: ValueKey(value),
                  onPressed: value ? () {} : () => oldTaps++,
                  child: Text(value ? 'New' : 'Old'),
                ),
              ),
            ),
          ),
        ),
      );
      await tester.pumpWidget(app());
      selected.value = true;
      await tester.pump();
      final oldButton = find.byKey(const ValueKey(false));
      expect(
        find.ancestor(of: oldButton, matching: find.byType(IgnorePointer)),
        findsWidgets,
      );
      reduceMotion = true;
      await tester.pumpWidget(app());
      await tester.pumpAndSettle();
      expect(
        tester.widget<AnimatedSwitcher>(find.byType(AnimatedSwitcher)).duration,
        Duration.zero,
      );
      expect(find.text('Old'), findsNothing);
      expect(oldTaps, 0);
      await tester.pumpWidget(const SizedBox());
      selected.dispose();
    },
  );
}
