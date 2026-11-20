import '../services/session_store.dart';
import 'dart:async';
import 'dart:convert';
import 'focus_timer.dart';
import 'package:flutter/foundation.dart';

class FocusSession extends ChangeNotifier {
  FocusSession(this.store, {FocusTimer? timer}) : timer = timer ?? FocusTimer() {
    this.timer.addListener(_changed);
  }
  final SessionStore store;
  final FocusTimer timer;
  final List<String> notes = [];
  int? userId, selectedTaskId;
  bool loading = false;
  String? storageError;
  int _epoch = 0, _revision = -1;
  bool _disposed = false;
  Future<void> _writes = Future.value();
  Future<void> get flushed => _writes;
  void _changed() {
    if (_disposed) return;
    if (!loading && timer.revision != _revision) { _revision = timer.revision; save(); }
    notifyListeners();
  }
  Future<void> attachUser(int? id) async {
    if (id == userId) return;
    if (userId != null) { timer.pause(); save(); }
    final epoch = ++_epoch;
    loading = true; userId = id; selectedTaskId = null; notes.clear();
    timer.restore({}); storageError = null;
    if (id != null) {
      try {
        await _writes;
        final raw = await store.read('focus_user_$id');
        if (_disposed || epoch != _epoch) return;
        final data = raw == null ? <String, dynamic>{} : jsonDecode(raw) as Map<String, dynamic>;
        timer.restore(Map<String, dynamic>.from(data['timer'] as Map? ?? {}));
        selectedTaskId = data['taskId'] as int?;
        notes.addAll((data['notes'] as List? ?? []).whereType<String>().toSet());
      } catch (_) { storageError = 'Could not restore your focus session.'; }
    }
    if (_disposed || epoch != _epoch) return;
    loading = false; _revision = timer.revision; notifyListeners(); save();
  }
  void selectTask(int? id) { selectedTaskId = id; save(); notifyListeners(); }
  void addNote(String text) {
    final clean = text.trim();
    if (clean.isEmpty || notes.contains(clean)) return;
    notes.insert(0, clean); save(); notifyListeners();
  }
  void removeNote(String text) { notes.remove(text); save(); notifyListeners(); }
  void save() {
    final id = userId;
    if (id == null || loading || _disposed) return;
    final encoded = jsonEncode({'timer': timer.toJson(), 'taskId': selectedTaskId, 'notes': notes});
    _writes = _writes.then((_) => store.write('focus_user_$id', encoded)).catchError((Object _) {
      storageError = 'Could not save focus changes on this device.';
    });
  }
  @override
  void dispose() {
    timer.pause(); save(); _disposed = true;
    timer.removeListener(_changed); timer.dispose(); super.dispose();
  }
}
