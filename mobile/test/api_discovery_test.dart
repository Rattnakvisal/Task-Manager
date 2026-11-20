import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:task_manager_mobile/src/services/api_client.dart';
import 'package:task_manager_mobile/src/services/api_exception.dart';

void main() {
  test('the default API follows the configured Vercel production domain', () {
    final vercel = jsonDecode(File('../vercel.json').readAsStringSync());
    expect(
      ApiClient.productionBaseUrl,
      '${vercel['env']['APP_URL']}/api/mobile',
    );
    expect(ApiClient.defaultBaseUrl, ApiClient.productionBaseUrl);
    expect(Uri.parse(ApiClient.defaultBaseUrl).scheme, 'https');
  });

  test('connection failure never switches to a LAN server', () async {
    final requests = <Uri>[];
    final api = ApiClient(
      client: MockClient((request) async {
        requests.add(request.url);
        throw http.ClientException('offline');
      }),
    );
    addTearDown(api.close);
    await expectLater(
      api.request('POST', '/login', {}),
      throwsA(isA<ApiException>()),
    );
    expect(requests.single.toString(), '${ApiClient.productionBaseUrl}/login');
    expect(api.baseUrl, ApiClient.productionBaseUrl);
  });

  test('TLS failure never downgrades production HTTPS', () async {
    final requests = <Uri>[];
    final api = ApiClient(
      client: MockClient((request) async {
        requests.add(request.url);
        throw const HandshakeException('Invalid TLS');
      }),
    );
    addTearDown(api.close);
    await expectLater(
      api.request('POST', '/login', {}),
      throwsA(isA<ApiException>()),
    );
    expect(requests.single.scheme, 'https');
  });

  test('a developer can explicitly configure a local debug backend', () async {
    Uri? url;
    final api = ApiClient(
      baseUrl: 'http://127.0.0.1:8000',
      client: MockClient((request) async {
        url = request.url;
        return http.Response('{"status":"ok"}', 200);
      }),
    );
    addTearDown(api.close);
    await api.request('GET', '/ping');
    expect(url.toString(), 'http://127.0.0.1:8000/api/mobile/ping');
  });

  test('Vercel resolves its working API path once without a form', () async {
    final requests = <http.Request>[];
    final api = ApiClient(
      client: MockClient((request) async {
        requests.add(request);
        if (!request.url.path.startsWith('/api/api/mobile')) {
          return http.Response('{"message":"Not Found"}', 404);
        }
        if (request.url.path.endsWith('/ping')) {
          expect(request.headers.containsKey('Authorization'), isFalse);
          return http.Response('{"status":"ok"}', 200);
        }
        return http.Response('{"token":"signed-in"}', 200);
      }),
    )..token = 'saved';
    addTearDown(api.close);

    final signedIn = await api.request('POST', '/login', {
      'email': 'user@example.com',
    });
    expect(signedIn['token'], 'signed-in');
    expect(requests.map((r) => r.url.path), [
      '/api/mobile/login',
      '/api/api/mobile/ping',
      '/api/api/mobile/login',
    ]);
    expect(requests.first.body, requests.last.body);
    expect(api.baseUrl, 'https://taskflow-ai-kh.vercel.app/api/api/mobile');
    await api.request('GET', '/tasks');
    expect(requests.length, 4);
    expect(requests.last.url.path, '/api/api/mobile/tasks');
    expect(
      requests.every(
        (r) =>
            r.url.scheme == 'https' &&
            r.url.host == 'taskflow-ai-kh.vercel.app',
      ),
      isTrue,
    );
  });

  test(
    'an updated Vercel deployment can return to the canonical path',
    () async {
      final requests = <Uri>[];
      final api = ApiClient(
        baseUrl: 'https://taskflow-ai-kh.vercel.app/api/api/mobile',
        client: MockClient((request) async {
          requests.add(request.url);
          if (request.url.path.startsWith('/api/api/')) {
            return http.Response('{"message":"Not Found"}', 404);
          }
          return http.Response('{"status":"ok"}', 200);
        }),
      );
      addTearDown(api.close);
      await api.request('GET', '/tasks');
      expect(api.baseUrl, ApiClient.productionBaseUrl);
      expect(requests.map((r) => r.path), [
        '/api/api/mobile/tasks',
        '/api/mobile/ping',
        '/api/mobile/tasks',
      ]);
    },
  );

  test('a missing task does not trigger route discovery', () async {
    var requests = 0;
    final api = ApiClient(
      client: MockClient((_) async {
        requests++;
        return http.Response('{"message":"Not Found"}', 404);
      }),
    );
    addTearDown(api.close);
    await expectLater(
      api.request('GET', '/tasks/404'),
      throwsA(isA<ApiException>()),
    );
    expect(requests, 1);
  });

  test(
    'profile updates recover the production route without changing the payload',
    () async {
      final requests = <http.Request>[];
      final api = ApiClient(
        client: MockClient((request) async {
          requests.add(request);
          if (!request.url.path.startsWith('/api/api/mobile')) {
            return http.Response('{"message":"Not Found"}', 404);
          }
          if (request.url.path.endsWith('/ping')) {
            expect(request.headers.containsKey('Authorization'), isFalse);
            return http.Response('{"status":"ok"}', 200);
          }
          return http.Response(
            '{"user":{"id":1,"name":"Updated","email":"user@example.com"}}',
            200,
          );
        }),
      )..token = 'saved';
      addTearDown(api.close);
      final data = await api.request('PATCH', '/profile', {'name': 'Updated'});
      expect(data['user']['name'], 'Updated');
      expect(requests.map((request) => request.url.path), [
        '/api/mobile/profile',
        '/api/api/mobile/ping',
        '/api/api/mobile/profile',
      ]);
      expect(requests.last.method, 'PATCH');
      expect(requests.last.body, requests.first.body);
      expect(requests.last.headers['Authorization'], 'Bearer saved');
    },
  );

  test('a late 404 reuses the route resolved by another request', () async {
    final lateResponse = Completer<http.Response>();
    final requests = <Uri>[];
    final api = ApiClient(
      client: MockClient((request) async {
        requests.add(request.url);
        if (request.url.path == '/api/mobile/tasks') return lateResponse.future;
        if (request.url.path == '/api/mobile/dashboard') {
          return http.Response('{"message":"Not Found"}', 404);
        }
        return http.Response('{"status":"ok","tasks":[]}', 200);
      }),
    );
    addTearDown(api.close);
    final tasks = api.request('GET', '/tasks');
    await api.request('GET', '/dashboard');
    lateResponse.complete(http.Response('{"message":"Not Found"}', 404));
    expect((await tasks)['tasks'], isEmpty);
    expect(requests.where((url) => url.path.endsWith('/ping')).length, 1);
  });
}
