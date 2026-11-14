import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:task_manager_mobile/src/api.dart';
import 'package:task_manager_mobile/src/app.dart';
import 'package:task_manager_mobile/src/app_state.dart';
import 'package:task_manager_mobile/src/models.dart';

class MemoryStore implements SessionStore {
  final values = <String, String>{};
  @override
  Future<String?> read(String key) async => values[key];
  @override
  Future<void> write(String key, String value) async {
    values[key] = value;
  }

  @override
  Future<void> delete(String key) async {
    values.remove(key);
  }
}

http.Response jsonResponse(Map<String, dynamic> data, [int status = 200]) =>
    http.Response(jsonEncode(data), status);
const user = {'id': 1, 'name': 'Visal', 'email': 'visal@example.com'};

void main() {
  test('a late refresh cannot overwrite a newly saved task', () async {
    final pendingRefresh = Completer<http.Response>();
    final state = AppState(
      ApiClient(
        client: MockClient((request) async {
          if (request.method == 'GET') return pendingRefresh.future;
          return jsonResponse({
            'task': {
              'id': 7,
              'title': 'New task',
              'priority': 'medium',
              'status': 'pending',
            },
          }, 201);
        }),
      ),
      MemoryStore(),
    );
    final refresh = state.refresh();
    await state.saveTask({
      'title': 'New task',
      'priority': 'medium',
      'status': 'pending',
    });
    pendingRefresh.complete(jsonResponse({'tasks': []}));
    await refresh;
    expect(state.tasks.single.title, 'New task');
    state.dispose();
  });

  test('expired sessions remove saved credentials', () async {
    final store = MemoryStore()..values['access_token'] = 'expired';
    final state = AppState(
      ApiClient(
        client: MockClient(
          (request) async => jsonResponse({'message': 'Unauthenticated.'}, 401),
        ),
      ),
      store,
    );
    await state.initialize();
    expect(state.user, isNull);
    expect(store.values['access_token'], isNull);
    expect(state.startupError, isNull);
    state.dispose();
  });

  test('connection failures preserve saved session for retry', () async {
    final store = MemoryStore()..values['access_token'] = 'saved';
    final state = AppState(
      ApiClient(
        client: MockClient(
          (request) async => throw http.ClientException('offline'),
        ),
      ),
      store,
    );
    await state.initialize();
    expect(store.values['access_token'], 'saved');
    expect(state.startupError, contains('Cannot reach'));
    state.dispose();
  });

  test('API sends bearer tokens and surfaces validation errors', () async {
    final api = ApiClient(
      client: MockClient((request) async {
        expect(request.headers['Authorization'], 'Bearer secret');
        expect(request.headers['Accept'], 'application/json');
        return jsonResponse({
          'errors': {
            'title': ['Give your task a title.'],
          },
        }, 422);
      }),
    )..token = 'secret';
    await expectLater(
      api.request('POST', '/tasks', {}),
      throwsA(
        isA<ApiException>().having(
          (e) => e.message,
          'message',
          'Give your task a title.',
        ),
      ),
    );
    api.close();
  });

  test(
    'failed logout keeps credentials so revocation can be retried',
    () async {
      final store = MemoryStore()..values['access_token'] = 'saved';
      final state = AppState(
        ApiClient(
          client: MockClient(
            (r) async => throw http.ClientException('offline'),
          ),
        )..token = 'saved',
        store,
      )..user = AppUser.fromJson(user);
      await expectLater(state.logout(), throwsA(isA<ApiException>()));
      expect(state.user, isNotNull);
      expect(store.values['access_token'], 'saved');
      state.dispose();
    },
  );

  testWidgets(
    'sign in shows navigation and creating a task saves through API',
    (tester) async {
      tester.view.physicalSize = const Size(420, 900);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      Map<String, dynamic>? created;
      final state = AppState(
        ApiClient(
          client: MockClient((request) async {
            if (request.url.path.endsWith('/login')) {
              return jsonResponse({'token': 'secret', 'user': user});
            }
            expect(request.headers['Authorization'], 'Bearer secret');
            if (request.method == 'POST' &&
                request.url.path.endsWith('/tasks')) {
              created = jsonDecode(request.body) as Map<String, dynamic>;
              return jsonResponse({
                'task': {'id': 7, ...created!},
              }, 201);
            }
            return jsonResponse({'tasks': []});
          }),
        ),
        MemoryStore(),
      );
      await tester.pumpWidget(TaskManagerApp(state: state));
      await tester.pumpAndSettle();
      await tester.enterText(
        find.widgetWithText(TextFormField, 'Email address'),
        'visal@example.com',
      );
      await tester.enterText(
        find.widgetWithText(TextFormField, 'Password'),
        'SecurePass1!',
      );
      await tester.tap(find.widgetWithText(FilledButton, 'Sign in'));
      await tester.pumpAndSettle();
      expect(find.byType(NavigationBar), findsOneWidget);
      expect(find.text('Hello, Visal'), findsOneWidget);
      await tester.tap(find.text('New task'));
      await tester.pumpAndSettle();
      await tester.enterText(
        find.widgetWithText(TextFormField, 'What needs to get done?'),
        'Build Android app',
      );
      await tester.ensureVisible(find.text('Create task'));
      await tester.tap(find.text('Create task'));
      await tester.pumpAndSettle();
      expect(created?['title'], 'Build Android app');
      expect(state.tasks.single.title, 'Build Android app');
      expect(find.byType(NavigationBar), findsOneWidget);
      await tester.tap(find.text('Tasks'));
      await tester.pumpAndSettle();
      expect(find.text('Build Android app'), findsOneWidget);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox());
      state.dispose();
    },
  );
}
