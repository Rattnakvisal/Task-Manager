import 'dart:async';
import 'dart:math' as math;
import 'package:flutter/foundation.dart';

class FocusTimer extends ChangeNotifier {
  FocusTimer({DateTime Function()? now}) : _now = now ?? DateTime.now;
  static const durations = {'work': 1500, 'short_break': 300, 'long_break': 900, 'flow': 0};
  final DateTime Function() _now;
  Timer? _ticker;
  DateTime? _lastTick;
  String _mode = 'work';
  int _workMinutes = 25, _budgetMs = 1500000, _remainingMs = 1500000;
  int _flowMs = 0, _spentMs = 0;
  bool _recorded = false;
  int completionCount = 0, revision = 0;
  String? lastCompletedMode;
  int dailyGoalSessions = 4;
  final Map<String, List<int>> _days = {};
  String _day(DateTime t) => '${t.year}-${t.month}-${t.day}';
  List<int> _stats(DateTime t) => _days.putIfAbsent(_day(t), () => [0, 0]);
  String get mode => _mode;
  bool get isFlowMode => mode == 'flow';
  int get workMinutes => _workMinutes;
  int get totalSeconds => (_budgetMs / 1000).ceil();
  int get remainingSeconds => (_remainingMs / 1000).ceil();
  int get flowSeconds => _flowMs ~/ 1000;
  bool get isRunning => _lastTick != null;
  int get completedSessions => _stats(_now())[1];
  int get totalFocusSeconds => _stats(_now())[0] ~/ 1000;
  int get completedMinutes => totalFocusSeconds ~/ 60;
  double get dailyProgress => (completedSessions / dailyGoalSessions.clamp(1, 12)).clamp(0.0, 1.0);
  void _changed() { revision++; notifyListeners(); }
  void setDailyGoal(int value) { dailyGoalSessions = value.clamp(1, 12); _changed(); }

  void setWorkMinutes(int minutes) {
    sync();
    _workMinutes = minutes.clamp(5, 120);
    if (mode == 'work') { _reset(); }
    _changed();
  }
  void addMinutes(int minutes) {
    if (isFlowMode) return;
    sync();
    if (_recorded) _reset();
    final updated = (_remainingMs + minutes * 60000).clamp(10000, 10800000);
    _budgetMs = math.max(10000, _budgetMs + updated - _remainingMs);
    _remainingMs = updated;
    _changed();
  }
  void setMode(String value) {
    if (!durations.containsKey(value)) throw ArgumentError.value(value, 'mode');
    if (value == mode) return;
    sync(); _mode = value; _reset(); _changed();
  }
  void start() {
    if (isRunning) return;
    if (!isFlowMode && _remainingMs <= 0) _reset();
    _lastTick = _now();
    _ticker?.cancel();
    _ticker = Timer.periodic(const Duration(seconds: 1), (_) => sync());
    _changed();
  }
  void _credit(DateTime start, int millis) {
    var cursor = start;
    var left = millis;
    while (left > 0) {
      final midnight = DateTime(cursor.year, cursor.month, cursor.day + 1);
      final chunk = math.min(left, midnight.difference(cursor).inMilliseconds);
      if (chunk <= 0) break;
      _stats(cursor)[0] += chunk;
      cursor = cursor.add(Duration(milliseconds: chunk)); left -= chunk;
    }
  }
  void _complete(DateTime at) {
    if (_recorded) return;
    _recorded = true;
    if (mode == 'work' || isFlowMode) _stats(at)[1]++;
    completionCount++; lastCompletedMode = mode; revision++;
  }
  void sync() {
    final last = _lastTick;
    if (last == null) return;
    final current = _now();
    final elapsed = current.difference(last).inMilliseconds;
    if (elapsed <= 0) return;
    final used = isFlowMode ? elapsed : math.min(elapsed, _remainingMs);
    if (mode == 'work' || isFlowMode) _credit(last, used);
    _spentMs += used;
    if (isFlowMode) { _flowMs += used; } else { _remainingMs -= used; }
    _lastTick = current;
    if (!isFlowMode && _remainingMs == 0) {
      _lastTick = null; _ticker?.cancel();
      _complete(last.add(Duration(milliseconds: used)));
    }
    notifyListeners();
  }
  void pause() {
    sync(); _ticker?.cancel(); _lastTick = null; _changed();
  }
  void _reset() {
    _ticker?.cancel(); _lastTick = null; _flowMs = 0; _spentMs = 0; _recorded = false;
    _budgetMs = (mode == 'work' ? _workMinutes * 60 : durations[mode]!) * 1000;
    _remainingMs = _budgetMs;
  }
  void reset() { sync(); _reset(); _changed(); }
  void finishSessionEarly() {
    sync();
    if (_spentMs > 0 && !_recorded) _complete(_now());
    _reset(); _changed();
  }
  Map<String, dynamic> toJson() => {
    'mode': mode, 'workMinutes': _workMinutes, 'budgetMs': _budgetMs,
    'remainingMs': _remainingMs, 'flowMs': _flowMs, 'spentMs': _spentMs,
    'recorded': _recorded, 'runningSince': _lastTick?.toIso8601String(),
    'goal': dailyGoalSessions, 'days': _days,
  };
  void restore(Map<String, dynamic> data) {
    _reset(); _days.clear(); completionCount = 0; lastCompletedMode = null;
    _mode = durations.containsKey(data['mode']) ? data['mode'] as String : 'work';
    _workMinutes = (data['workMinutes'] as int? ?? 25).clamp(5, 120);
    _reset();
    _budgetMs = (data['budgetMs'] as int? ?? _budgetMs).clamp(0, 10800000);
    _remainingMs = (data['remainingMs'] as int? ?? _budgetMs).clamp(0, 10800000);
    _flowMs = math.max(0, data['flowMs'] as int? ?? 0);
    _spentMs = math.max(0, data['spentMs'] as int? ?? 0);
    _recorded = data['recorded'] == true;
    dailyGoalSessions = (data['goal'] as int? ?? 4).clamp(1, 12);
    final days = data['days'];
    if (days is Map) {
      for (final entry in days.entries) {
        if (entry.value is List && entry.value.length == 2 && entry.value.every((v) => v is int && v >= 0)) {
          _days['${entry.key}'] = List<int>.from(entry.value);
        }
      }
    }
    _lastTick = DateTime.tryParse(data['runningSince'] as String? ?? '');
    if (_lastTick != null) {
      sync();
      if (isRunning) _ticker = Timer.periodic(const Duration(seconds: 1), (_) => sync());
    }
    _changed();
  }
  @override
  void dispose() { _ticker?.cancel(); super.dispose(); }
}
