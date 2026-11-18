import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:task_manager_mobile/src/api.dart';
import 'package:task_manager_mobile/src/app_state.dart';
import 'package:task_manager_mobile/src/models.dart';
import 'package:task_manager_mobile/src/screens/task_editor.dart';

class _FakeStore implements SessionStore {
  final Map<String, String> values = {};
  @override
  Future<String?> read(String key) async => values[key];
  @override
  Future<void> write(String key, String value) async => values[key] = value;
  @override
  Future<void> delete(String key) async => values.remove(key);
}

void main() {
  testWidgets('TaskEditor renders modern fields, selectors, and accepts input', (
    tester,
  ) async {
    final state = AppState(ApiClient(), _FakeStore());
    state.user = const AppUser(id: 1, name: 'Visal', email: 'visal@test.com');

    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: TaskEditor(state: state),
        ),
      ),
    );
    await tester.pump();

    // Verify key headers and sections
    expect(find.text('New Task'), findsOneWidget);
    expect(find.text('What needs to get done?'), findsOneWidget);
    expect(find.text('PRIORITY LEVEL'), findsOneWidget);
    expect(find.text('TASK STATUS'), findsOneWidget);
    expect(find.text('SCHEDULE & TIMELINE'), findsOneWidget);
    expect(find.text('CATEGORY'), findsOneWidget);
    expect(find.text('TAGS'), findsOneWidget);
    expect(find.text('CHECKLIST & STEPS'), findsOneWidget);
    expect(find.text('PREFERENCES'), findsOneWidget);
    expect(find.text('Create Task'), findsOneWidget);

    // Test Priority 1-tap Selection
    await tester.tap(find.text('High'));
    await tester.pump();

    // Test Status 1-tap Selection
    await tester.tap(find.text('In Progress'));
    await tester.pump();

    // Test Quick Date Tap
    await tester.tap(find.text('Today'));
    await tester.pump();
    expect(find.text('Scheduled for Today'), findsOneWidget);

    // Test Category Preset Tap
    await tester.tap(find.text('Work'));
    await tester.pump();

    // Test Enter Title
    await tester.enterText(
      find.widgetWithText(TextFormField, 'What needs to get done?'),
      'Launch redesigned task UI',
    );
    await tester.pump();
    expect(find.text('Launch redesigned task UI'), findsOneWidget);

    state.dispose();
  });
}
