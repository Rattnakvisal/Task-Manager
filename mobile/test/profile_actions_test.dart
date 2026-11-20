import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:task_manager_mobile/src/models/app_user.dart';
import 'package:task_manager_mobile/src/screens/home_shell.dart';
import 'package:task_manager_mobile/src/screens/profile_view.dart';
import 'package:task_manager_mobile/src/services/api_client.dart';
import 'package:task_manager_mobile/src/state/app_state.dart';

import 'app_test.dart' show MemoryStore, jsonResponse, user;

Future<void> openProfile(WidgetTester tester, AppState state) async {
  tester.view.physicalSize = const Size(390, 844);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.resetPhysicalSize);
  addTearDown(tester.view.resetDevicePixelRatio);
  await tester.pumpWidget(MaterialApp(home: HomeShell(state: state)));
  await tester.pumpAndSettle();
  await tester.tap(find.widgetWithText(NavigationDestination, 'Profile'));
  await tester.pumpAndSettle();
}

AppState signedInState(MockClient client) =>
    AppState(ApiClient(client: client), MemoryStore())
      ..user = AppUser.fromJson(user)
      ..starting = false
      ..readingSession = false
      ..hasLoadedTasks = true;

void main() {
  testWidgets(
    'profile validates, retains failed edits, and saves the server name',
    (tester) async {
      var writes = 0;
      var fail = true;
      final state = signedInState(
        MockClient((request) async {
          if (request.method == 'PATCH' &&
              request.url.path.endsWith('/profile')) {
            writes++;
            if (fail) return jsonResponse({'message': 'Please retry'}, 500);
            return jsonResponse({
              'user': {...user, 'name': 'Updated Name'},
            });
          }
          return jsonResponse({'tasks': []});
        }),
      );
      await openProfile(tester, state);
      await tester.tap(find.text('Edit profile'));
      await tester.pumpAndSettle();
      await tester.enterText(find.byType(TextFormField), '   ');
      await tester.tap(find.text('Save changes'));
      await tester.pumpAndSettle();
      expect(writes, 0);
      expect(find.text('Please enter your name.'), findsOneWidget);
      await tester.enterText(find.byType(TextFormField), 'Updated Name');
      await tester.tap(find.text('Save changes'));
      await tester.pumpAndSettle();
      expect(find.text('Please retry'), findsOneWidget);
      expect(state.user!.name, user['name']);
      expect(
        tester
            .widget<TextFormField>(find.byType(TextFormField))
            .controller!
            .text,
        'Updated Name',
      );
      fail = false;
      await tester.tap(find.text('Save changes'));
      await tester.pumpAndSettle();
      expect(writes, 2);
      expect(state.user!.name, 'Updated Name');
      expect(find.byType(TextFormField), findsNothing);
      expect(find.text('Updated Name'), findsOneWidget);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox());
      state.dispose();
    },
  );

  test('a late profile response cannot restore a signed-out account', () async {
    final response = Completer<http.Response>();
    final state = signedInState(MockClient((_) => response.future));
    final update = state.updateProfileName('New Name');
    final expectation = expectLater(update, throwsA(isA<Exception>()));
    await state.clearSession();
    response.complete(
      jsonResponse({
        'user': {...user, 'name': 'New Name'},
      }),
    );
    await expectation;
    expect(state.user, isNull);
    state.dispose();
  });

  testWidgets('profile supports a narrow screen and enlarged text', (
    tester,
  ) async {
    final state = signedInState(
      MockClient((_) async => jsonResponse({'tasks': []})),
    );
    state.user = const AppUser(
      id: 1,
      name: 'A long account name for accessibility',
      email: 'long.account.address@example.com',
    );
    tester.view.physicalSize = const Size(320, 640);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(
      MaterialApp(
        builder: (context, child) => MediaQuery(
          data: MediaQuery.of(context)
              .copyWith(textScaler: const TextScaler.linear(1.6)),
          child: child!,
        ),
        home: Scaffold(
          body: SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: ProfileView(state: state, onOpenFocus: () {}),
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.text('A long account name for accessibility'), findsOneWidget);
    await tester.drag(
      find.byType(SingleChildScrollView).first,
      const Offset(0, -1000),
    );
    await tester.pumpAndSettle();
    expect(tester.takeException(), isNull);
    await tester.pumpWidget(const SizedBox());
    state.dispose();
  });

  testWidgets('failed cloud sync never displays a success alert', (
    tester,
  ) async {
    var failSync = false;
    var failedRequests = 0;
    final state = signedInState(
      MockClient((_) async {
        if (failSync) {
          failedRequests++;
          return jsonResponse({'message': 'Cloud unavailable'}, 500);
        }
        return jsonResponse({'tasks': []});
      }),
    );
    await openProfile(tester, state);
    failSync = true;
    await tester.drag(
      find.byType(CustomScrollView).hitTestable().first,
      const Offset(0, -550),
    );
    await tester.pumpAndSettle();
    await tester.tap(find.text('Cloud Sync & Linked Storage'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Sync Workspace Now'));
    await tester.pumpAndSettle();
    expect(failedRequests, 1);
    expect(find.text('Synchronized!'), findsNothing);
    expect(find.textContaining('Cloud unavailable'), findsOneWidget);
    expect(tester.takeException(), isNull);
    await tester.pumpWidget(const SizedBox());
    state.dispose();
  });

  testWidgets('pending sign-out blocks duplicate requests and allows retry', (
    tester,
  ) async {
    final response = Completer<http.Response>();
    var requests = 0;
    final state = signedInState(
      MockClient((request) {
        if (request.url.path.endsWith('/logout')) {
          requests++;
          return response.future;
        }
        return Future.value(jsonResponse({'tasks': []}));
      }),
    );
    await openProfile(tester, state);
    await tester.drag(
      find.byType(CustomScrollView).hitTestable().first,
      const Offset(0, -1300),
    );
    await tester.pumpAndSettle();
    await tester.tap(find.text('Sign Out'));
    await tester.pumpAndSettle();
    await tester.tap(find.widgetWithText(FilledButton, 'Sign Out'));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 300));
    expect(
      find.byWidgetPredicate(
        (widget) => widget is ButtonStyleButton && widget.onPressed == null,
      ),
      findsOneWidget,
    );
    await tester.pump();
    expect(requests, 1);
    expect(find.byType(AlertDialog), findsNothing);
    response.complete(jsonResponse({'message': 'Try again'}, 500));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Sign Out'));
    await tester.pumpAndSettle();
    expect(find.byType(AlertDialog), findsOneWidget);
    expect(tester.takeException(), isNull);
    await tester.pumpWidget(const SizedBox());
    state.dispose();
  });
}
