import 'package:flutter/foundation.dart';

import '../models/app_user.dart';
import '../models/task.dart';
import '../services/api_client.dart';
import '../services/api_exception.dart';
import '../services/session_store.dart';
import 'focus_session.dart';
import 'streak_manager.dart';

class AppState extends ChangeNotifier {
  AppState(this.api, this.store)
    : focus = FocusSession(store),
      streak = StreakManager(store) {
    streak.addListener(_emit);
  }
  final FocusSession focus;
  final StreakManager streak;
  final ApiClient api;
  final SessionStore store;
  AppUser? user;
  List<Task> _tasks = const [];
  List<Task> get tasks => _tasks;
  set tasks(List<Task> value) {
    _tasks = value;
    hasLoadedTasks = true;
    _emit();
  }

  bool starting = true, busy = false, hasLoadedTasks = false;
  bool readingSession = true;
  String? startupError;
  DateTime? lastSyncedAt;
  bool _updatingProfile = false;
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
    readingSession = true;
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
      readingSession = false;
      if (api.token != null) {
        _emit();
        final data = await api.request('GET', '/me');
        if (_active(version)) {
          user = AppUser.fromJson(data['user']);
          await focus.attachUser(user!.id);
          await streak.checkIn(userId: user!.id);
        }
      } else {
        await streak.checkIn();
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
        readingSession = false;
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
      await focus.attachUser(account.id);
      await streak.checkIn(userId: account.id);
      if (!_active(version)) return;
      _tasks = const [];
      hasLoadedTasks = false;
      _refreshFuture = null;
      busy = false;
      startupError = null;
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
      lastSyncedAt = DateTime.now();
      await streak.syncWithTasks(_tasks, userId: user?.id);
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
        if (updated.completed) {
          await streak.recordTaskCompletion(updated);
        } else {
          await streak.recordActivity(type: 'task_saved');
        }
      });

  Future<void> deleteTask(int id) => _mutate('$id', (version) async {
    await api.request('DELETE', '/tasks/$id');
    if (!_active(version)) return;
    _taskVersion++;
    _tasks = List.unmodifiable(tasks.where((task) => task.id != id));
  });

  Future<void> toggleTask(Task task) async {
    final current =
        tasks.where((item) => item.id == task.id).firstOrNull ?? task;
    final willComplete = !current.completed;
    await saveTask({
      ...current.toPayload(),
      'status': willComplete ? 'completed' : 'pending',
    }, id: current.id);
    if (willComplete) {
      await streak.recordTaskCompletion(current);
    }
  }

  Future<void> updateTaskStatus(Task task, String newStatus) async {
    final current =
        tasks.where((item) => item.id == task.id).firstOrNull ?? task;
    if (current.status == newStatus) return;
    final willComplete = newStatus == 'completed';
    await saveTask({
      ...current.toPayload(),
      'status': newStatus,
    }, id: current.id);
    if (willComplete) {
      await streak.recordTaskCompletion(current);
    }
  }

  Future<void> toggleSubtask(Task task, int subtaskIndex) async {
    final current =
        tasks.where((item) => item.id == task.id).firstOrNull ?? task;
    if (subtaskIndex < 0 || subtaskIndex >= current.subtasks.length) return;

    final updatedSubtasks = current.subtasks
        .map((s) => Map<String, dynamic>.from(s))
        .toList();
    final wasDone = updatedSubtasks[subtaskIndex]['completed'] == true;
    updatedSubtasks[subtaskIndex]['completed'] = !wasDone;

    final allDone =
        updatedSubtasks.isNotEmpty &&
        updatedSubtasks.every((s) => s['completed'] == true);
    final String nextStatus = (allDone && current.status != 'completed')
        ? 'completed'
        : current.status;

    await saveTask({
      ...current.toPayload(),
      'subtasks': updatedSubtasks,
      'status': nextStatus,
    }, id: current.id);
    if (allDone && current.status != 'completed') {
      await streak.recordTaskCompletion(current);
    }
  }

  Future<void> addSubtask(Task task, String title) async {
    final clean = title.trim();
    if (clean.isEmpty) return;
    final current =
        tasks.where((item) => item.id == task.id).firstOrNull ?? task;
    final updatedSubtasks = current.subtasks
        .map((s) => Map<String, dynamic>.from(s))
        .toList();
    updatedSubtasks.add({
      'id': 'mobile-${DateTime.now().microsecondsSinceEpoch}',
      'title': clean,
      'completed': false,
      'points': <Map<String, dynamic>>[],
    });
    await saveTask({
      ...current.toPayload(),
      'subtasks': updatedSubtasks,
    }, id: current.id);
  }

  Future<void> toggleSubPoint(
    Task task,
    int subtaskIndex,
    int pointIndex,
  ) async {
    final current =
        tasks.where((item) => item.id == task.id).firstOrNull ?? task;
    if (subtaskIndex < 0 || subtaskIndex >= current.subtasks.length) return;

    final updatedSubtasks = current.subtasks
        .map((s) => Map<String, dynamic>.from(s))
        .toList();
    final points = (updatedSubtasks[subtaskIndex]['points'] as List? ?? [])
        .map(
          (p) => p is Map
              ? Map<String, dynamic>.from(p)
              : {'title': '$p', 'completed': false},
        )
        .toList();
    if (pointIndex < 0 || pointIndex >= points.length) return;

    final wasDone = points[pointIndex]['completed'] == true;
    points[pointIndex]['completed'] = !wasDone;
    updatedSubtasks[subtaskIndex]['points'] = points;

    // If all sub-points are done, mark the parent step as done
    final allPointsDone = points.every((p) => p['completed'] == true);
    if (allPointsDone) {
      updatedSubtasks[subtaskIndex]['completed'] = true;
    }

    final allDone =
        updatedSubtasks.isNotEmpty &&
        updatedSubtasks.every((s) => s['completed'] == true);
    final String nextStatus = (allDone && current.status != 'completed')
        ? 'completed'
        : current.status;

    await saveTask({
      ...current.toPayload(),
      'subtasks': updatedSubtasks,
      'status': nextStatus,
    }, id: current.id);
    if (allDone && current.status != 'completed') {
      await streak.recordTaskCompletion(current);
    }
  }

  Future<void> addSubPoint(
    Task task,
    int subtaskIndex,
    String pointTitle,
  ) async {
    final clean = pointTitle.trim();
    if (clean.isEmpty) return;
    final current =
        tasks.where((item) => item.id == task.id).firstOrNull ?? task;
    if (subtaskIndex < 0 || subtaskIndex >= current.subtasks.length) return;

    final updatedSubtasks = current.subtasks
        .map((s) => Map<String, dynamic>.from(s))
        .toList();
    final points = (updatedSubtasks[subtaskIndex]['points'] as List? ?? [])
        .map(
          (p) => p is Map
              ? Map<String, dynamic>.from(p)
              : {'title': '$p', 'completed': false},
        )
        .toList();
    points.add({
      'id': 'p-${DateTime.now().microsecondsSinceEpoch}',
      'title': clean,
      'completed': false,
    });
    updatedSubtasks[subtaskIndex]['points'] = points;

    await saveTask({
      ...current.toPayload(),
      'subtasks': updatedSubtasks,
    }, id: current.id);
  }

  Future<void> updateProfileName(String name) async {
    final clean = name.trim();
    if (clean.isEmpty || clean.length > 100) {
      throw const ApiException('Enter a name between 1 and 100 characters.');
    }
    if (_disposed || user == null) {
      throw const ApiException('Please sign in to update your profile.');
    }
    if (_updatingProfile) {
      throw const ApiException('Your profile is already being saved.');
    }
    _updatingProfile = true;
    final version = _sessionVersion;
    try {
      final data = await _authorized(
        () => api.request('PATCH', '/profile', {'name': clean}),
      );
      if (!_active(version)) {
        throw const ApiException('Your session changed. Please sign in again.');
      }
      user = AppUser.fromJson(data['user']);
      _emit();
    } finally {
      _updatingProfile = false;
    }
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
    lastSyncedAt = null;
    focus.attachUser(null);
    _tasks = const [];
    _mutations.clear();
    _refreshFuture = null;
    starting = busy = hasLoadedTasks = false;
    readingSession = false;
    startupError = null;
    _emit();
    await _withStore(() => store.delete('access_token'));
  }

  @override
  void dispose() {
    _disposed = true;
    ++_sessionVersion;
    api.close();
    focus.dispose();
    streak.removeListener(_emit);
    streak.dispose();
    super.dispose();
  }
}
