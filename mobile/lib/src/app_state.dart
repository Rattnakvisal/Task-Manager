import 'dart:async';

import 'package:flutter/foundation.dart';

import 'api.dart';
import 'models.dart';

class AppState extends ChangeNotifier {
  AppState(this.api, this.store);
  final ApiClient api;
  final SessionStore store;
  AppUser? user;
  List<Task> _tasks = const [];
  List<Task> get tasks => _tasks;
  bool starting = true, busy = false, hasLoadedTasks = false;
  bool isConnected = true, isCheckingConnection = false;
  int? lastPingLatencyMs;
  String connectionType = 'USB Tunnel';
  String? startupError;
  int _sessionVersion = 0, _taskVersion = 0;
  bool _disposed = false, _authenticating = false;
  Future<void>? _initialization, _refreshFuture;
  Future<void> _storageWork = Future.value();
  final _mutations = <String>{};

  bool isTaskBusy(int id) => _mutations.contains('$id');
  bool _active(int version) => !_disposed && version == _sessionVersion;
  void _emit() {
    if (!_disposed) notifyListeners();
  }

  // Serialize credential reads/writes so a late sign-in cannot restore a
  // token after a newer sign-out has removed it.
  Future<T> _withStore<T>(Future<T> Function() action) {
    final result = _storageWork.then((_) => action());
    _storageWork = result.then<void>(
      (_) {},
      onError: (Object _, StackTrace _) {},
    );
    return result;
  }

  Future<void> initialize() {
    if (_disposed) return Future.value();
    return _initialization ??= _initialize().whenComplete(
      () => _initialization = null,
    );
  }

  Future<void> _initialize() async {
    final version = ++_sessionVersion;
    starting = true;
    startupError = null;
    _emit();
    try {
      final saved = await _withStore(
        () async =>
            (await store.read('server_url'), await store.read('access_token')),
      );
      if (!_active(version)) return;
      var savedUrl = saved.$1;
      if (savedUrl != null && savedUrl.contains(':8001')) {
        savedUrl = savedUrl.replaceAll(':8001', ':8000');
        await store.write('server_url', savedUrl);
      }

      // Dynamically discover working server for real network runs
      if (!api.isCustomClient) {
        final activeUrl = await ApiClient.autoDiscoverServer(
          preferred: savedUrl ?? api.baseUrl,
        );
        if (!_active(version)) return;
        if (activeUrl != null) {
          api.baseUrl = activeUrl;
          if (activeUrl != savedUrl) {
            await store.write('server_url', activeUrl);
          }
        } else if (savedUrl != null) {
          api.baseUrl = savedUrl;
        }
      } else if (savedUrl != null) {
        api.baseUrl = savedUrl;
      }
      api.onBaseUrlChanged = (newUrl) {
        if (!_disposed) {
          _withStore(() => store.write('server_url', newUrl));
          _emit();
        }
      };

      api.token = saved.$2;
      if (api.token != null) {
        final data = await api.request('GET', '/me');
        if (_active(version)) user = AppUser.fromJson(data['user']);
      }
      if (_active(version)) {
        _updateConnectionType();
        if (!api.isCustomClient) {
          unawaited(checkConnection());
        }
      }
    } on ApiException catch (error) {
      if (!_active(version)) return;
      if (error.statusCode == 401) {
        await clearSession();
      } else {
        startupError = error.message;
      }
    } catch (_) {
      if (_active(version)) {
        startupError = 'Could not open your saved session. Please try again.';
      }
    } finally {
      if (_active(version)) {
        starting = false;
        _emit();
      }
    }
  }

  Future<void> setServer(String value) async {
    final validated = ApiClient.validateBaseUrl(value);
    await _withStore(() => store.write('server_url', validated));
    if (_disposed) return;
    api.baseUrl = validated;
    _emit();
  }

  Future<String?> autoDetectServer() async {
    final discovered = await ApiClient.autoDiscoverServer(preferred: api.baseUrl);
    if (discovered != null) {
      await setServer(discovered);
    }
    return discovered;
  }

  void _updateConnectionType() {
    final url = api.baseUrl;
    if (url.contains('127.0.0.1') || url.contains('localhost')) {
      connectionType = 'USB Tunnel / Local';
    } else if (url.contains('10.0.2.2')) {
      connectionType = 'Android Emulator';
    } else {
      connectionType = 'Wi-Fi LAN';
    }
  }

  Future<bool> checkConnection() async {
    if (isCheckingConnection || _disposed || api.isCustomClient) return isConnected;
    isCheckingConnection = true;
    _emit();
    try {
      final sw = Stopwatch()..start();
      final healthy = await ApiClient.probeUrl(
        api.baseUrl,
        timeout: const Duration(seconds: 2),
      );
      sw.stop();
      if (healthy != null) {
        isConnected = true;
        lastPingLatencyMs = sw.elapsedMilliseconds;
        _updateConnectionType();
      } else {
        // Automatically try to auto-discover
        final found = await ApiClient.autoDiscoverServer(preferred: api.baseUrl);
        if (found != null) {
          api.baseUrl = found;
          isConnected = true;
          lastPingLatencyMs = 28;
          _updateConnectionType();
          await store.write('server_url', found);
        } else {
          isConnected = false;
          lastPingLatencyMs = null;
        }
      }
    } catch (_) {
      isConnected = false;
      lastPingLatencyMs = null;
    } finally {
      if (!_disposed) {
        isCheckingConnection = false;
        _emit();
      }
    }
    return isConnected;
  }

  Future<void> authenticate({
    required String email,
    required String password,
    String? name,
    String? confirmation,
  }) async {
    if (_disposed || _authenticating) {
      throw const ApiException('Sign in is already in progress.');
    }
    _authenticating = true;
    final version = ++_sessionVersion;
    try {
      final data = await api.request(
        'POST',
        name == null ? '/login' : '/register',
        {
          'email': email.trim(),
          'password': password,
          'device_name': 'Task Manager Android',
          if (name != null) 'name': name.trim(),
          if (name != null) 'password_confirmation': confirmation,
        },
      );
      if (!_active(version)) return;
      final account = AppUser.fromJson(data['user']);
      final token = data['token'] as String;
      await _withStore(() async {
        if (_active(version)) await store.write('access_token', token);
      });
      if (!_active(version)) return;
      api.token = token;
      user = account;
      _tasks = const [];
      hasLoadedTasks = false;
      _refreshFuture = null;
      busy = false;
      _emit();
    } finally {
      _authenticating = false;
    }
  }

  Future<T> _authorized<T>(Future<T> Function() action) async {
    final version = _sessionVersion;
    try {
      return await action();
    } on ApiException catch (error) {
      if (error.statusCode == 401 && _active(version)) await clearSession();
      rethrow;
    }
  }

  Future<void> refresh() {
    if (_disposed) return Future.value();
    if (_refreshFuture != null) return _refreshFuture!;
    final version = _sessionVersion;
    final future = _loadTasks(version).whenComplete(() {
      // A previous account's request must not reset the new account's loader.
      if (_active(version)) {
        _refreshFuture = null;
        busy = false;
        _emit();
      }
    });
    _refreshFuture = future;
    return future;
  }

  Future<void> _loadTasks(int version) => _authorized(() async {
    final taskVersion = _taskVersion;
    busy = true;
    _emit();
    final data = await api.request('GET', '/tasks');
    if (_active(version) && taskVersion == _taskVersion) {
      _tasks = List.unmodifiable(
        (data['tasks'] as List).map((item) => Task.fromJson(item)),
      );
      hasLoadedTasks = true;
    }
  });

  Future<void> _mutate(
    String key,
    Future<void> Function(int version) action,
  ) async {
    if (_disposed) return;
    if (!_mutations.add(key)) {
      throw const ApiException('This task is already being updated.');
    }
    final version = _sessionVersion;
    _taskVersion++;
    _emit();
    try {
      await _authorized(() => action(version));
    } finally {
      if (_active(version)) {
        _mutations.remove(key);
        _emit();
      }
    }
  }

  Future<void> saveTask(Map<String, dynamic> payload, {int? id}) =>
      _mutate(id?.toString() ?? 'new', (version) async {
        final data = await api.request(
          id == null ? 'POST' : 'PUT',
          id == null ? '/tasks' : '/tasks/$id',
          payload,
        );
        if (!_active(version)) return;
        final updated = Task.fromJson(data['task']);
        _taskVersion++;
        _tasks = List.unmodifiable([
          updated,
          ...tasks.where((task) => task.id != updated.id),
        ]);
      });

  Future<void> deleteTask(int id) => _mutate('$id', (version) async {
    await api.request('DELETE', '/tasks/$id');
    if (!_active(version)) return;
    _taskVersion++;
    _tasks = List.unmodifiable(tasks.where((task) => task.id != id));
  });

  Future<void> toggleTask(Task task) {
    final current =
        tasks.where((item) => item.id == task.id).firstOrNull ?? task;
    return saveTask({
      ...current.toPayload(),
      'status': current.completed ? 'pending' : 'completed',
    }, id: current.id);
  }

  Future<void> logout() async {
    final version = _sessionVersion;
    // Preserve the credential if revocation fails, allowing the user to retry.
    await _authorized(() => api.request('POST', '/logout'));
    if (_active(version)) await clearSession();
  }

  Future<void> clearSession() async {
    if (_disposed) return;
    ++_sessionVersion;
    api.token = null;
    user = null;
    _tasks = const [];
    _mutations.clear();
    _refreshFuture = null;
    starting = busy = hasLoadedTasks = false;
    startupError = null;
    _emit();
    await _withStore(() => store.delete('access_token'));
  }

  @override
  void dispose() {
    _disposed = true;
    ++_sessionVersion;
    api.close();
    super.dispose();
  }
}
