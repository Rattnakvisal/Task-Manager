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
      final savedToken = await _withStore(() async {
        // Retire manually saved LAN/USB URLs from older app versions.
        await store.delete('server_url');
        return store.read('access_token');
      });
      if (!_active(version)) return;
      api.token = savedToken;
      if (api.token != null) {
        final data = await api.request('GET', '/me');
        if (_active(version)) user = AppUser.fromJson(data['user']);
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
