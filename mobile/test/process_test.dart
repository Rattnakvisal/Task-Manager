import 'dart:async';
import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:task_manager_mobile/src/api.dart';
import 'package:task_manager_mobile/src/app_state.dart';
import 'package:task_manager_mobile/src/models.dart';

import 'app_test.dart' show MemoryStore, jsonResponse, user;

class AbortAwareClient extends http.BaseClient {
  bool aborted = false;
  @override
  Future<http.StreamedResponse> send(http.BaseRequest request) async {
    await (request as http.Abortable).abortTrigger;
    aborted = true;
    throw http.RequestAbortedException(request.url);
  }
}

class SlowWriteStore extends MemoryStore {
  final started = Completer<void>();
  final release = Completer<void>();
  @override
  Future<void> write(String key, String value) async {
    if (key == 'access_token') {
      started.complete();
      await release.future;
    }
    await super.write(key, value);
  }
}

class SlowReadStore extends MemoryStore {
  final started = Completer<void>();
  final release = Completer<String?>();
  @override
  Future<String?> read(String key) async {
    if (key == 'access_token') {
      started.complete();
      return release.future;
    }
    return super.read(key);
  }
}

void main() {
  test(
    'sign out during credential persistence cannot restore the token',
    () async {
      final store = SlowWriteStore();
      final state = AppState(
        ApiClient(
          client: MockClient(
            (r) async => jsonResponse({'token': 'saved', 'user': user}),
          ),
        ),
        store,
      );
      final signingIn = state.authenticate(
        email: 'visal@example.com',
        password: 'SecurePass1!',
      );
      await store.started.future;
      final signingOut = state.clearSession();
      store.release.complete();
      await Future.wait([signingIn, signingOut]);
      expect(store.values['access_token'], isNull);
      expect(state.api.token, isNull);
      expect(state.user, isNull);
      state.dispose();
    },
  );

  test('sign out during startup ignores delayed stored credentials', () async {
    final store = SlowReadStore();
    var requests = 0;
    final state = AppState(
      ApiClient(
        client: MockClient((r) async {
          requests++;
          return jsonResponse({'user': user});
        }),
      ),
      store,
    );
    final startup = state.initialize();
    await store.started.future;
    final signingOut = state.clearSession();
    store.release.complete('old-token');
    await Future.wait([startup, signingOut]);
    expect(requests, 0);
    expect(state.user, isNull);
    expect(state.starting, isFalse);
    state.dispose();
  });

  test(
    'concurrent refreshes share one request and reset loading on failure',
    () async {
      var requests = 0;
      final pending = Completer<http.Response>();
      final state = AppState(
        ApiClient(
          client: MockClient((r) async {
            requests++;
            if (requests == 1) return pending.future;
            return jsonResponse({'tasks': []});
          }),
        ),
        MemoryStore(),
      );
      final first = state.refresh();
      final second = state.refresh();
      expect(identical(first, second), isTrue);
      expect(state.busy, isTrue);
      final failed = expectLater(first, throwsA(isA<ApiException>()));
      pending.complete(jsonResponse({'message': 'Please retry.'}, 503));
      await failed;
      expect(requests, 1);
      expect(state.busy, isFalse);
      expect(state.hasLoadedTasks, isFalse);
      await state.refresh();
      expect(requests, 2);
      expect(state.hasLoadedTasks, isTrue);
      expect(
        () => state.tasks.add(
          const Task(id: 1, title: 'x', priority: 'low', status: 'pending'),
        ),
        throwsUnsupportedError,
      );
      state.dispose();
    },
  );

  test('sign out invalidates pending data immediately', () async {
    final pending = Completer<http.Response>();
    final state = AppState(
      ApiClient(client: MockClient((r) => pending.future)),
      MemoryStore(),
    );
    final loading = state.refresh();
    await state.clearSession();
    pending.complete(
      jsonResponse({
        'tasks': [
          {
            'id': 1,
            'title': 'Old account',
            'priority': 'low',
            'status': 'pending',
          },
        ],
      }),
    );
    await loading;
    expect(state.tasks, isEmpty);
    expect(state.hasLoadedTasks, isFalse);
    expect(state.busy, isFalse);
    state.dispose();
  });

  test(
    'a late unauthorized response cannot sign out a newer account',
    () async {
      final oldResponse = Completer<http.Response>();
      final store = MemoryStore();
      final state = AppState(
        ApiClient(
          client: MockClient((r) async {
            if (r.url.path.endsWith('/login')) {
              return jsonResponse({'token': 'new-token', 'user': user});
            }
            return oldResponse.future;
          }),
        ),
        store,
      );
      final oldRefresh = state.refresh();
      await state.clearSession();
      await state.authenticate(
        email: 'visal@example.com',
        password: 'SecurePass1!',
      );
      final failed = expectLater(oldRefresh, throwsA(isA<ApiException>()));
      oldResponse.complete(http.Response('<html>Unauthorized</html>', 401));
      await failed;
      expect(state.user?.name, 'Visal');
      expect(store.values['access_token'], 'new-token');
      state.dispose();
    },
  );

  test('disposing during a request never publishes late data', () async {
    final pending = Completer<http.Response>();
    final state = AppState(
      ApiClient(client: MockClient((r) => pending.future)),
      MemoryStore(),
    );
    final loading = state.refresh();
    state.dispose();
    pending.complete(jsonResponse({'tasks': []}));
    await loading;
    expect(state.hasLoadedTasks, isFalse);
  });

  test(
    'duplicate writes for the same task are blocked until completion',
    () async {
      final pending = Completer<http.Response>();
      var requests = 0;
      final state = AppState(
        ApiClient(
          client: MockClient((r) async {
            requests++;
            return pending.future;
          }),
        ),
        MemoryStore(),
      );
      final save = state.saveTask({'title': 'Save'}, id: 7);
      expect(state.isTaskBusy(7), isTrue);
      await expectLater(state.deleteTask(7), throwsA(isA<ApiException>()));
      await Future<void>.delayed(Duration.zero);
      expect(requests, 1);
      pending.complete(
        jsonResponse({
          'task': {
            'id': 7,
            'title': 'Saved',
            'priority': 'low',
            'status': 'pending',
          },
        }),
      );
      await save;
      expect(state.isTaskBusy(7), isFalse);
      state.dispose();
    },
  );

  test('toggles use the latest task snapshot', () async {
    Map<String, dynamic>? update;
    final state = AppState(
      ApiClient(
        client: MockClient((r) async {
          if (r.method == 'GET') {
            return jsonResponse({
              'tasks': [
                {
                  'id': 1,
                  'title': 'Updated title',
                  'priority': 'high',
                  'status': 'completed',
                },
              ],
            });
          }
          update = jsonDecode(r.body);
          return jsonResponse({
            'task': {'id': 1, ...update!},
          });
        }),
      ),
      MemoryStore(),
    );
    await state.refresh();
    await state.toggleTask(
      const Task(id: 1, title: 'Old title', priority: 'low', status: 'pending'),
    );
    expect(update?['status'], 'pending');
    expect(update?['title'], 'Updated title');
    state.dispose();
  });

  test('timeout cancels the underlying request', () async {
    final client = AbortAwareClient();
    final api = ApiClient(
      client: client,
      timeout: const Duration(milliseconds: 10),
    );
    await expectLater(
      api.request('GET', '/tasks'),
      throwsA(
        isA<ApiException>().having(
          (e) => e.message,
          'message',
          contains('too long'),
        ),
      ),
    );
    await Future<void>.delayed(Duration.zero);
    expect(client.aborted, isTrue);
    api.close();
  });

  test('closing API client cancels pending requests', () async {
    final client = AbortAwareClient();
    final api = ApiClient(client: client);
    final request = api.request('GET', '/tasks');
    final failed = expectLater(request, throwsA(isA<ApiException>()));
    api.close();
    await failed;
    expect(client.aborted, isTrue);
  });

  test(
    'API handles UTF-8, empty responses and unusual error payloads',
    () async {
      final api = ApiClient(
        client: MockClient(
          (r) async => switch (r.url.path.split('/').last) {
            'unicode' => http.Response.bytes(
              utf8.encode(jsonEncode({'title': 'ភាសាខ្មែរ'})),
              200,
            ),
            'empty' => http.Response('', 204),
            _ => jsonResponse({
              'message': 'Try again.',
              'errors': {'title': []},
            }, 422),
          },
        ),
      );
      expect((await api.request('GET', '/unicode'))['title'], 'ភាសាខ្មែរ');
      expect(await api.request('POST', '/empty'), isEmpty);
      await expectLater(
        api.request('POST', '/error'),
        throwsA(
          isA<ApiException>().having((e) => e.message, 'message', 'Try again.'),
        ),
      );
      api.close();
    },
  );
}
