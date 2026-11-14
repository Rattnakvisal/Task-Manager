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
  ApiClient({http.Client? client, this.timeout = const Duration(seconds: 20)})
    : _client = client ?? http.Client(),
      isCustomClient = client != null;
  final http.Client _client;
  final bool isCustomClient;
  final Duration timeout;
  final _pending = <Completer<void>>{};

  static String get defaultBaseUrl {
    const envUrl = String.fromEnvironment('API_BASE_URL');
    if (envUrl.isNotEmpty) return envUrl;
    if (!kDebugMode) return '';
    if (kIsWeb) return 'http://localhost:8000/api/mobile';
    try {
      if (Platform.isAndroid) return 'http://127.0.0.1:8000/api/mobile';
    } catch (_) {}
    return 'http://127.0.0.1:8000/api/mobile';
  }

  bool _closed = false;
  String baseUrl = defaultBaseUrl;
  String? token;
  void Function(String newUrl)? onBaseUrlChanged;

  /// Gathers all dynamic server candidate URLs for auto-discovery
  static Future<List<String>> getCandidates({String? current}) async {
    final list = <String>{};
    if (current != null && current.isNotEmpty) {
      try {
        list.add(validateBaseUrl(current));
      } catch (_) {}
    }
    const envUrl = String.fromEnvironment('API_BASE_URL');
    if (envUrl.isNotEmpty) {
      try {
        list.add(validateBaseUrl(envUrl));
      } catch (_) {}
    }

    // 1. USB cable / localhost
    list.add('http://127.0.0.1:8000/api/mobile');
    list.add('http://localhost:8000/api/mobile');

    // 2. Android emulator
    list.add('http://10.0.2.2:8000/api/mobile');

    // 3. Known host machine Wi-Fi / LAN IPs
    list.add('http://192.168.132.238:8000/api/mobile');
    list.add('http://172.16.129.104:8000/api/mobile');

    // 4. Dynamic subnet candidates from local network interfaces
    if (!kIsWeb) {
      try {
        final interfaces = await NetworkInterface.list(
          type: InternetAddressType.IPv4,
          includeLinkLocal: false,
        );
        for (final iface in interfaces) {
          for (final addr in iface.addresses) {
            if (!addr.isLoopback) {
              final parts = addr.address.split('.');
              if (parts.length == 4) {
                final subnet = '${parts[0]}.${parts[1]}.${parts[2]}';
                list.add('http://$subnet.238:8000/api/mobile');
                list.add('http://$subnet.104:8000/api/mobile');
                list.add('http://$subnet.1:8000/api/mobile');
                list.add('http://$subnet.222:8000/api/mobile');
              }
            }
          }
        }
      } catch (_) {}
    }

    return list.toList();
  }

  /// Tests if a candidate URL responds with a healthy ping
  static Future<String?> probeUrl(
    String url, {
    Duration timeout = const Duration(milliseconds: 1400),
    http.Client? client,
  }) async {
    final httpClient = client ?? http.Client();
    final shouldClose = client == null;
    try {
      final clean = validateBaseUrl(url);
      final response = await httpClient
          .get(Uri.parse('$clean/ping'))
          .timeout(timeout);
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data is Map && data['status'] == 'ok') {
          return clean;
        }
      }
    } catch (_) {
    } finally {
      if (shouldClose) {
        httpClient.close();
      }
    }
    return null;
  }

  /// Probes candidate URLs in parallel and returns the fastest responding server
  static Future<String?> autoDiscoverServer({
    String? preferred,
    http.Client? client,
  }) async {
    final candidates = await getCandidates(current: preferred);
    if (candidates.isEmpty) return null;

    final completer = Completer<String?>();
    int pending = candidates.length;

    for (final url in candidates) {
      probeUrl(url, client: client)
          .then((working) {
            if (working != null && !completer.isCompleted) {
              completer.complete(working);
            } else {
              pending--;
              if (pending == 0 && !completer.isCompleted) {
                completer.complete(null);
              }
            }
          })
          .catchError((_) {
            pending--;
            if (pending == 0 && !completer.isCompleted) {
              completer.complete(null);
            }
          });
    }

    return completer.future;
  }

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
      return await _execute(method, path, body);
    } on SocketException {
      // Auto-discover if network address changed (e.g. Wi-Fi switched or USB reconnected)
      final discovered = await autoDiscoverServer();
      if (discovered != null && discovered != baseUrl) {
        baseUrl = discovered;
        onBaseUrlChanged?.call(discovered);
        return await _execute(method, path, body);
      }
      throw const ApiException(
        'Cannot connect. Check your internet connection and server address.',
      );
    } on http.ClientException {
      final discovered = await autoDiscoverServer();
      if (discovered != null && discovered != baseUrl) {
        baseUrl = discovered;
        onBaseUrlChanged?.call(discovered);
        return await _execute(method, path, body);
      }
      throw const ApiException(
        'Cannot reach the server. Check your connection and try again.',
      );
    }
  }

  Future<Map<String, dynamic>> _execute(
    String method,
    String path, [
    Map<String, dynamic>? body,
  ]) async {
    if (_closed) throw const ApiException('This connection has been closed.');
    final base = validateBaseUrl(baseUrl);
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
            if (token != null) 'Authorization': 'Bearer $token',
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
          'The server returned an unexpected response. Check the server address.',
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
