import 'dart:async';

import 'package:flutter/foundation.dart';

class FocusTimer extends ChangeNotifier {
  FocusTimer({DateTime Function()? now}) : _now = now ?? DateTime.now;

  static const durations = {
    'work': 25 * 60,
    'short_break': 5 * 60,
    'long_break': 15 * 60,
  };

  final DateTime Function() _now;
  Timer? _ticker;
  DateTime? _deadline;
  String _mode = 'work';
  int _remainingSeconds = 25 * 60;
  int completedSessions = 0, completionCount = 0;

  String get mode => _mode;
  int get totalSeconds => durations[_mode]!;
  int get remainingSeconds => _remainingSeconds;
  bool get isRunning => _deadline != null;
  int get completedMinutes => completedSessions * 25;

  void setMode(String mode) {
    if (!durations.containsKey(mode)) {
      throw ArgumentError.value(mode, 'mode', 'Unknown focus interval');
    }
    if (mode == _mode) return;
    _mode = mode;
    reset();
  }

  void start() {
    if (isRunning) return;
    if (_remainingSeconds == 0) _remainingSeconds = totalSeconds;
    _deadline = _now().add(Duration(seconds: _remainingSeconds));
    _ticker = Timer.periodic(const Duration(seconds: 1), (_) => sync());
    notifyListeners();
  }

  // Compare against a deadline so backgrounding the phone cannot slow the timer.
  void sync() {
    final deadline = _deadline;
    if (deadline == null) return;
    final next = (deadline.difference(_now()).inMilliseconds / 1000)
        .ceil()
        .clamp(0, totalSeconds);
    if (next == _remainingSeconds) return;
    _remainingSeconds = next;
    if (next == 0) {
      _ticker?.cancel();
      _deadline = null;
      completionCount++;
      if (_mode == 'work') completedSessions++;
    }
    notifyListeners();
  }

  void pause() {
    if (!isRunning) return;
    sync();
    _ticker?.cancel();
    _deadline = null;
    notifyListeners();
  }

  void reset() {
    _ticker?.cancel();
    _deadline = null;
    _remainingSeconds = totalSeconds;
    notifyListeners();
  }

  @override
  void dispose() {
    _ticker?.cancel();
    super.dispose();
  }
}
