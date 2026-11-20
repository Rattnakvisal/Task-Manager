import '../models/task.dart';
import '../services/session_store.dart';
import 'dart:convert';
import 'dart:math' as math;
import 'package:flutter/foundation.dart';

class DayStreakInfo {
  const DayStreakInfo({
    required this.dayLabel,
    required this.dayNumber,
    required this.date,
    required this.isCompleted,
    required this.isToday,
    required this.isFuture,
  });

  final String dayLabel; // e.g. 'M', 'T', 'W'
  final int dayNumber; // e.g. 18
  final DateTime date;
  final bool isCompleted;
  final bool isToday;
  final bool isFuture;
}

class StreakMilestone {
  const StreakMilestone({
    required this.targetDays,
    required this.title,
    required this.icon,
    required this.description,
  });

  final int targetDays;
  final String title;
  final String icon;
  final String description;
}

class StreakManager extends ChangeNotifier {
  StreakManager(this.store);

  final SessionStore store;

  int _streakCount = 1;
  int get streakCount => _streakCount;
  int get currentStreak => _streakCount;

  int _longestStreak = 1;
  int get longestStreak => _longestStreak;

  int _totalActiveDays = 1;
  int get totalActiveDays => _totalActiveDays;

  String? _lastActiveDateStr;
  String? get lastActiveDateStr => _lastActiveDateStr;

  final Set<String> _activeDates = {};
  Set<String> get activeDates => Set.unmodifiable(_activeDates);

  bool _isLoaded = false;
  bool get isLoaded => _isLoaded;

  bool _streakIncreasedToday = false;
  bool get streakIncreasedToday => _streakIncreasedToday;

  int? _userId;

  static const List<StreakMilestone> milestones = [
    StreakMilestone(
      targetDays: 3,
      title: 'Bronze Spark',
      icon: '⚡',
      description: '3 days of continuous task focus',
    ),
    StreakMilestone(
      targetDays: 7,
      title: 'Silver Flame',
      icon: '🔥',
      description: 'One full week of unbroken productivity',
    ),
    StreakMilestone(
      targetDays: 14,
      title: 'Gold Inferno',
      icon: '🏆',
      description: 'Two weeks of consistent task mastery',
    ),
    StreakMilestone(
      targetDays: 30,
      title: 'Diamond Master',
      icon: '💎',
      description: 'A full month of unstoppable achievement',
    ),
  ];

  StreakMilestone get currentMilestone {
    for (final m in milestones.reversed) {
      if (_streakCount >= m.targetDays) return m;
    }
    return milestones.first;
  }

  StreakMilestone get nextMilestone {
    for (final m in milestones) {
      if (_streakCount < m.targetDays) return m;
    }
    return milestones.last;
  }

  double get milestoneProgress {
    final next = nextMilestone;
    if (_streakCount >= next.targetDays) return 1.0;
    return (_streakCount / next.targetDays).clamp(0.0, 1.0);
  }

  String _dateKey(DateTime dt) {
    final y = dt.year.toString().padLeft(4, '0');
    final m = dt.month.toString().padLeft(2, '0');
    final d = dt.day.toString().padLeft(2, '0');
    return '$y-$m-$d';
  }

  String _storageKey(int? uid) =>
      uid != null ? 'daily_streak_v2_user_$uid' : 'daily_streak_v2_device';

  /// Dynamically computes the consecutive streak count from actual [activeDates]
  void _recalculateStreak() {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final todayKey = _dateKey(today);
    final yesterdayKey = _dateKey(today.subtract(const Duration(days: 1)));

    final hasActivityToday = _activeDates.contains(todayKey);
    final hasActivityYesterday = _activeDates.contains(yesterdayKey);

    if (!hasActivityToday && !hasActivityYesterday) {
      // More than 1 day skipped: streak broken
      _streakCount = 0;
    } else {
      // Trace backwards consecutively
      int count = 0;
      var cursor = hasActivityToday ? today : today.subtract(const Duration(days: 1));

      while (_activeDates.contains(_dateKey(cursor))) {
        count++;
        cursor = cursor.subtract(const Duration(days: 1));
      }

      _streakCount = count;
    }

    _totalActiveDays = _activeDates.length;
    _longestStreak = math.max(_longestStreak, _streakCount);
  }

  /// Initial check-in on app startup or user session restore
  Future<void> checkIn({int? userId}) async {
    _userId = userId;
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final todayKey = _dateKey(today);

    try {
      final raw = await store.read(_storageKey(userId));
      if (raw != null && raw.isNotEmpty) {
        final data = jsonDecode(raw) as Map<String, dynamic>;
        _longestStreak = (data['longestStreak'] as num?)?.toInt() ?? 1;
        _lastActiveDateStr = data['lastActiveDate'] as String?;

        final datesList = (data['activeDates'] as List<dynamic>?)?.cast<String>() ?? [];
        _activeDates.clear();
        _activeDates.addAll(datesList);
      } else {
        // Fresh initial install/user: start streak at 1 for today's active session
        _activeDates.clear();
        _activeDates.add(todayKey);
        _lastActiveDateStr = todayKey;
      }

      // Record today's app usage
      final prevCount = _streakCount;
      final wasActiveToday = _activeDates.contains(todayKey);
      _activeDates.add(todayKey);
      _lastActiveDateStr = todayKey;

      _recalculateStreak();

      _streakIncreasedToday = _streakCount > prevCount || (!wasActiveToday && _streakCount > 0);

      await _persist();
    } catch (_) {
      _streakCount = math.max(_streakCount, 1);
      _activeDates.add(todayKey);
    } finally {
      _isLoaded = true;
      notifyListeners();
    }
  }

  /// Dynamically synchronizes active dates with completed tasks from the server
  Future<void> syncWithTasks(List<Task> tasks, {int? userId}) async {
    if (userId != null) _userId = userId;
    bool hasNewDates = false;

    for (final task in tasks) {
      if (task.completed) {
        final d = task.endDate ?? task.dueDate;
        if (d != null) {
          final key = _dateKey(d);
          if (!_activeDates.contains(key)) {
            _activeDates.add(key);
            hasNewDates = true;
          }
        }
      }
    }

    _recalculateStreak();

    if (hasNewDates) {
      await _persist();
      notifyListeners();
    }
  }

  /// Called whenever a task is marked as completed in the app
  Future<bool> recordTaskCompletion(Task task) async {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final todayKey = _dateKey(today);

    final prevCount = _streakCount;
    final wasActiveToday = _activeDates.contains(todayKey);

    _activeDates.add(todayKey);
    _lastActiveDateStr = todayKey;

    _recalculateStreak();

    final increased = _streakCount > prevCount || (!wasActiveToday && _streakCount > 0);
    _streakIncreasedToday = increased;

    await _persist();
    notifyListeners();

    return increased;
  }

  /// Called on general productive actions (focus session complete, task creation)
  Future<bool> recordActivity({String type = 'activity'}) async {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final todayKey = _dateKey(today);

    final prevCount = _streakCount;
    final wasActiveToday = _activeDates.contains(todayKey);

    _activeDates.add(todayKey);
    _lastActiveDateStr = todayKey;

    _recalculateStreak();

    final increased = _streakCount > prevCount || (!wasActiveToday && _streakCount > 0);
    _streakIncreasedToday = increased;

    await _persist();
    notifyListeners();

    return increased;
  }

  Future<void> _persist() async {
    try {
      final payload = jsonEncode({
        'streakCount': _streakCount,
        'longestStreak': _longestStreak,
        'totalActiveDays': _totalActiveDays,
        'lastActiveDate': _lastActiveDateStr,
        'activeDates': _activeDates.toList(),
      });
      await store.write(_storageKey(_userId), payload);
    } catch (_) {}
  }

  /// Returns dynamic 7-day status for the current calendar week (Monday to Sunday)
  List<DayStreakInfo> getWeeklyStatus() {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    // Find Monday of the current week (weekday 1 is Monday in Dart)
    final monday = today.subtract(Duration(days: today.weekday - 1));

    const labels = ['M', 'T', 'W', 'T', 'F', 'S', 'S'];

    return List.generate(7, (i) {
      final dayDate = monday.add(Duration(days: i));
      final dayKey = _dateKey(dayDate);
      final isToday = dayDate.year == today.year &&
          dayDate.month == today.month &&
          dayDate.day == today.day;
      final isFuture = dayDate.isAfter(today);

      final isCompleted = _activeDates.contains(dayKey);

      return DayStreakInfo(
        dayLabel: labels[i],
        dayNumber: dayDate.day,
        date: dayDate,
        isCompleted: isCompleted,
        isToday: isToday,
        isFuture: isFuture,
      );
    });
  }
}
