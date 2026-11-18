import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:task_manager_mobile/src/api.dart';
import 'package:task_manager_mobile/src/app_state.dart';
import 'package:task_manager_mobile/src/models.dart';
import 'package:task_manager_mobile/src/screens/focus_view.dart';

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
  testWidgets('FocusView renders header, timer, mode switcher, and tools', (
    tester,
  ) async {
    final state = AppState(ApiClient(), _FakeStore());
    state.user = const AppUser(id: 1, name: 'Visal', email: 'visal@test.com');
    state.tasks = [
      const Task(
        id: 101,
        title: 'Design high-priority feature',
        priority: 'high',
        status: 'pending',
        category: 'Development',
      ),
    ];

    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: SingleChildScrollView(
            child: FocusView(state: state),
          ),
        ),
      ),
    );
    await tester.pump();

    // Verify key test anchors
    expect(find.text('Focus Session'), findsOneWidget);
    expect(find.text('DEEP WORK & TIMER'), findsOneWidget);
    expect(find.text('Design high-priority feature'), findsOneWidget);
    expect(find.text('BRAIN DUMP & DISTRACTION PAD'), findsOneWidget);
    expect(find.text('DAILY FOCUS TARGET'), findsOneWidget);

    // Mode Switching
    await tester.tap(find.text('Short (5m)'));
    await tester.pump();
    expect(find.text('05:00'), findsOneWidget);
    expect(find.text('Short Break'), findsOneWidget);

    await tester.tap(find.text('Long (15m)'));
    await tester.pump();
    expect(find.text('15:00'), findsOneWidget);
    expect(find.text('Long Break'), findsOneWidget);

    // Switch back to Focus
    await tester.tap(find.textContaining('Focus ('));
    await tester.pump();
    expect(find.text('25:00'), findsOneWidget);

    // Test Brain Dump note addition
    final inputFinder = find.byType(TextField);
    expect(inputFinder, findsOneWidget);
    await tester.enterText(inputFinder, 'Check server deployment log');
    await tester.tap(find.byIcon(Icons.add_rounded).first);
    await tester.pump();

    expect(find.text('Check server deployment log'), findsOneWidget);

    state.dispose();
  });
}
