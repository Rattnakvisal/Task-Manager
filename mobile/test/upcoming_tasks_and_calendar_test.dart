import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:task_manager_mobile/src/api.dart';
import 'package:task_manager_mobile/src/app_state.dart';
import 'package:task_manager_mobile/src/models.dart';
import 'package:task_manager_mobile/src/screens/home_shell.dart';

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
  testWidgets('Upcoming Tasks displays future deadlines and links to Calendar', (tester) async {
    final state = AppState(ApiClient(), _FakeStore());
    state.user = const AppUser(id: 1, name: 'Visal', email: 'visal@test.com');

    final now = DateTime.now();
    final today = dateOnly(now);
    final tomorrow = today.add(const Duration(days: 1));
    final futureDate = today.add(const Duration(days: 3));

    // Mock initial tasks
    state.hasLoadedTasks = true;
    state.tasks = [
      Task(
        id: 1,
        title: 'Complete UI Testing',
        priority: 'high',
        status: 'pending',
        category: 'Work',
        dueDate: tomorrow,
      ),
      Task(
        id: 2,
        title: 'Backend API Integration',
        priority: 'medium',
        status: 'in_progress',
        category: 'Project',
        dueDate: futureDate,
        subtasks: const [
          {'title': 'Auth endpoint', 'completed': true},
          {'title': 'Task endpoints', 'completed': false},
        ],
      ),
    ];

    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: HomeShell(state: state),
        ),
      ),
    );
    await tester.pump();

    // Verify Upcoming Tasks section header
    expect(find.text('Upcoming Tasks'), findsOneWidget);
    expect(find.text('Calendar'), findsWidgets);

    // Verify upcoming task titles are visible
    expect(find.text('Complete UI Testing'), findsOneWidget);
    expect(find.text('Backend API Integration'), findsOneWidget);

    // Verify relative date badge
    expect(find.text('TMRW'), findsOneWidget);

    // Verify subtask count badge
    expect(find.text('✓ 1/2 steps'), findsOneWidget);

    // Tap 'Calendar' button on the Upcoming Tasks card
    await tester.tap(find.widgetWithText(TextButton, 'Calendar'));
    await tester.pumpAndSettle();

    // Verify we switched to the Calendar tab
    expect(find.text('SCHEDULE & DEADLINES'), findsOneWidget);

    state.dispose();
  });
}
