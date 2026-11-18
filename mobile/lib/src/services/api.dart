import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;

class ApiException implements Exception {
  const ApiException(this.message, [this.statusCode = 0]);
  final String message;
  final int statusCode;
  @override
  String toString() => message;
}

abstract class SessionStore {
  Future<String?> read(String key);
  Future<void> write(String key, String value);
  Future<void> delete(String key);
}

class SecureSessionStore implements SessionStore {
  final _storage = const FlutterSecureStorage();
  @override
  Future<String?> read(String key) => _storage.read(key: key);
  @override
  Future<void> write(String key, String value) =>
      _storage.write(key: key, value: value);
  @override
  Future<void> delete(String key) => _storage.delete(key: key);
}

class ApiClient {
  ApiClient({
    http.Client? client,
    String? baseUrl,
    this.timeout = const Duration(seconds: 20),
  }) : _client = client ?? http.Client(),
       _baseUrl = validateBaseUrl(baseUrl ?? defaultBaseUrl);
  final http.Client _client;
  String _baseUrl;
  String get baseUrl => _baseUrl;
  final Duration timeout;
  final _pending = <Completer<void>>{};

  // The stable production domain follows Vercel production deployments.
  // Developers can override it at build time; app users never enter an address.
  static const productionBaseUrl =
      'https://taskflow-ai-kh.vercel.app/api/mobile';
  static const defaultBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: productionBaseUrl,
  );

  bool _closed = false;
  Future<String?>? _routeResolution;
  String? token;

  static String validateBaseUrl(String input) {
    var value = input.trim().replaceFirst(RegExp(r'/+$'), '');
    if (value.isEmpty) {
      throw const ApiException('Please enter a server URL.');
    }
    if (!value.startsWith('http://') && !value.startsWith('https://')) {
      value = 'http://$value';
    }
    if (value.endsWith('/api')) {
      value = '$value/mobile';
    } else if (!value.endsWith('/api/mobile')) {
      value = '$value/api/mobile';
    }
    final uri = Uri.tryParse(value);
    if (uri == null ||
        !uri.hasAuthority ||
        uri.host.isEmpty ||
        !['http', 'https'].contains(uri.scheme) ||
        uri.hasQuery ||
        uri.hasFragment ||
        uri.userInfo.isNotEmpty) {
      throw const ApiException(
        'Enter a valid server URL (e.g. http://10.0.2.2:8000 or http://127.0.0.1:8000).',
      );
    }
    if (!kDebugMode && uri.scheme != 'https') {
      throw const ApiException('A secure HTTPS server is required.');
    }
    return value;
  }

  Future<Map<String, dynamic>> request(
    String method,
    String path, [
    Map<String, dynamic>? body,
  ]) async {
    try {
      return await _requestWithRouteRecovery(method, path, body);
    } on HandshakeException {
      throw const ApiException(
        'Could not establish a secure connection. Please try again.',
      );
    } on SocketException {
      throw const ApiException(
        'Cannot connect. Check your internet connection and try again.',
      );
    } on http.ClientException {
      throw const ApiException(
        'Cannot reach the server. Check your internet connection and try again.',
      );
    }
  }

  Future<Map<String, dynamic>> _requestWithRouteRecovery(
    String method,
    String path,
    Map<String, dynamic>? body,
  ) async {
    final attemptedBaseUrl = baseUrl;
    try {
      return await _execute(method, path, body);
    } on ApiException catch (error) {
      // Older Vercel PHP deployments infer /api as the script's base path.
      // Resolve that compatibility path only on our known production origin.
      if (error.statusCode != 404 ||
          !const {
            '/login',
            '/register',
            '/me',
            '/tasks',
            '/dashboard',
            '/calendar',
            '/notifications',
            '/logout',
            '/ping',
          }.contains(path)) {
        rethrow;
      }
      if (baseUrl != attemptedBaseUrl) {
        return _execute(method, path, body);
      }
      final resolved = await (_routeResolution ??= _resolveProductionRoute()
          .whenComplete(() => _routeResolution = null));
      if (resolved == null || _closed) rethrow;
      _baseUrl = resolved;
      return _execute(method, path, body);
    }
  }

  Future<String?> _resolveProductionRoute() async {
    final legacyBaseUrl = productionBaseUrl.replaceFirst(
      '/api/mobile',
      '/api/api/mobile',
    );
    if (!{productionBaseUrl, legacyBaseUrl}.contains(baseUrl)) return null;
    for (final candidate in {productionBaseUrl, legacyBaseUrl}) {
      if (candidate == baseUrl) continue;
      try {
        // Health checks carry no credentials. The origin always remains HTTPS
        // on the same Vercel project; a LAN address is never a candidate.
        final health = await _execute('GET', '/ping', null, candidate);
        if (health['status'] == 'ok') return candidate;
      } on ApiException {
        // Preserve the original response when the alternative is unavailable.
      } on IOException {
        continue;
      } on http.ClientException {
        continue;
      }
    }
    return null;
  }

  Future<Map<String, dynamic>> _execute(
    String method,
    String path, [
    Map<String, dynamic>? body,
    String? server,
  ]) async {
    if (_closed) throw const ApiException('This connection has been closed.');
    final base = validateBaseUrl(server ?? baseUrl);
    final abort = Completer<void>();
    final request =
        http.AbortableRequest(
            method,
            Uri.parse('$base$path'),
            abortTrigger: abort.future,
          )
          ..followRedirects = false
          ..headers.addAll({
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            if (token != null && server == null)
              'Authorization': 'Bearer $token',
          });
    if (body != null) request.body = jsonEncode(body);
    _pending.add(abort);
    try {
      final response =
          await (() async => http.Response.fromStream(
            await _client.send(request),
          ))().timeout(
            timeout,
            onTimeout: () {
              if (!abort.isCompleted) abort.complete();
              throw const ApiException(
                'The server is taking too long. Please try again.',
              );
            },
          );
      if (response.statusCode == 401) {
        throw const ApiException(
          'Your session expired. Please sign in again.',
          401,
        );
      }
      if (response.statusCode == 204) return {};
      Map<String, dynamic> data;
      try {
        data =
            jsonDecode(utf8.decode(response.bodyBytes)) as Map<String, dynamic>;
      } catch (_) {
        throw ApiException(
          'The service is temporarily unavailable. Please try again later.',
          response.statusCode,
        );
      }
      if (response.statusCode < 200 || response.statusCode >= 300) {
        final errors = data['errors'];
        final first = errors is Map && errors.isNotEmpty
            ? errors.values.first
            : null;
        final message =
            first is List && first.isNotEmpty && first.first is String
            ? first.first as String
            : data['message'] is String
            ? data['message'] as String
            : 'Something went wrong. Please try again.';
        throw ApiException(message, response.statusCode);
      }
      return data;
    } on TimeoutException {
      throw const ApiException(
        'The server is taking too long. Please try again.',
      );
    } finally {
      _pending.remove(abort);
    }
  }

  void close() {
    if (_closed) return;
    _closed = true;
    for (final abort in _pending) {
      if (!abort.isCompleted) abort.complete();
    }
    _pending.clear();
    _client.close();
  }
}
