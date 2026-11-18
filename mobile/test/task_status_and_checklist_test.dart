import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:task_manager_mobile/src/models.dart';
import 'package:task_manager_mobile/src/widgets/task_status_widgets.dart';

void main() {
  group('TaskStatusStyle & Helpers', () {
    test('resolves styles correctly for pending, in_progress, and completed', () {
      final pendingStyle = TaskStatusStyle.of('pending');
      expect(pendingStyle.label, 'To do');
      expect(pendingStyle.color, const Color(0xFF3864ED));

      final inProgStyle = TaskStatusStyle.of('in_progress');
      expect(inProgStyle.label, 'In Progress');
      expect(inProgStyle.color, const Color(0xFF7C3AED));

      final doneStyle = TaskStatusStyle.of('completed');
      expect(doneStyle.label, 'Done');
      expect(doneStyle.color, const Color(0xFF10B981));
    });
  });

  group('AnimatedTaskStatusSelector Widget', () {
    testWidgets('renders all 3 statuses and triggers callback when tapped', (tester) async {
      String currentStatus = 'pending';

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: StatefulBuilder(
              builder: (context, setState) {
                return AnimatedTaskStatusSelector(
                  selectedStatus: currentStatus,
                  onChanged: (newStatus) {
                    setState(() => currentStatus = newStatus);
                  },
                );
              },
            ),
          ),
        ),
      );
      await tester.pump();

      // Check that all 3 options are visible
      expect(find.text('To do'), findsOneWidget);
      expect(find.text('In Progress'), findsOneWidget);
      expect(find.text('Done'), findsOneWidget);

      // Tap 'In Progress'
      await tester.tap(find.text('In Progress'));
      await tester.pumpAndSettle();
      expect(currentStatus, 'in_progress');

      // Tap 'Done'
      await tester.tap(find.text('Done'));
      await tester.pumpAndSettle();
      expect(currentStatus, 'completed');
    });
  });

  group('TaskChecklistSection Widget', () {
    testWidgets('renders checklist steps, handles clicks and progress', (tester) async {
      int? toggledIndex;
      String? addedStep;

      final subtasks = [
        {'id': '1', 'title': 'Design high-fidelity prototype', 'completed': true},
        {'id': '2', 'title': 'Connect REST API endpoints', 'completed': false},
      ];

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: TaskChecklistSection(
              subtasks: subtasks,
              onToggleSubtask: (index) => toggledIndex = index,
              onAddSubtask: (title) => addedStep = title,
              initiallyExpanded: true,
            ),
          ),
        ),
      );
      await tester.pump();

      // Check summary
      expect(find.text('Checklist & Steps'), findsOneWidget);
      expect(find.text('1 of 2 (50%)'), findsOneWidget);
      expect(find.text('Design high-fidelity prototype'), findsOneWidget);
      expect(find.text('Connect REST API endpoints'), findsOneWidget);

      // Tap the second step
      await tester.tap(find.text('Connect REST API endpoints'));
      await tester.pump();
      expect(toggledIndex, 1);

      // Tap 'Add step'
      await tester.tap(find.text('Add step'));
      await tester.pump();

      // Enter a new step
      await tester.enterText(find.byType(TextField), 'Run automated tests');
      await tester.tap(find.byTooltip('Add Step'));
      await tester.pump();
      expect(addedStep, 'Run automated tests');
    });

    testWidgets('renders micro-steps (sub-points) and handles sub-point click', (tester) async {
      int? toggledStepIndex;
      int? toggledPointIndex;

      final subtasksWithPoints = [
        {
          'id': '1',
          'title': 'Design high-fidelity prototype',
          'completed': false,
          'points': [
            {'title': 'Draft color palette', 'completed': true},
            {'title': 'Export iOS app icons', 'completed': false},
          ],
        },
      ];

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: TaskChecklistSection(
              subtasks: subtasksWithPoints,
              onToggleSubtask: (_) {},
              onToggleSubPoint: (stepIdx, pointIdx) {
                toggledStepIndex = stepIdx;
                toggledPointIndex = pointIdx;
              },
              initiallyExpanded: true,
            ),
          ),
        ),
      );
      await tester.pump();

      expect(find.text('Design high-fidelity prototype'), findsOneWidget);
      expect(find.text('Draft color palette'), findsOneWidget);
      expect(find.text('Export iOS app icons'), findsOneWidget);

      // Tap the second sub-point
      await tester.tap(find.text('Export iOS app icons'));
      await tester.pump();
      expect(toggledStepIndex, 0);
      expect(toggledPointIndex, 1);
    });
  });

  group('Task copyWith & helpers', () {
    test('Task copyWith properly updates fields', () {
      final task = Task(
        id: 1,
        title: 'Initial',
        priority: 'medium',
        status: 'pending',
        subtasks: const [
          {'title': 'Step 1', 'completed': false},
        ],
      );

      final updated = task.copyWith(
        status: 'in_progress',
        subtasks: [
          {'title': 'Step 1', 'completed': true},
        ],
      );

      expect(updated.id, 1);
      expect(updated.title, 'Initial');
      expect(updated.status, 'in_progress');
      expect(updated.completedSubtasks, 1);
    });
  });
}
