import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../app.dart';
import '../app_state.dart';
import '../models.dart';
import '../focus_timer.dart';

class FocusView extends StatefulWidget {
  const FocusView({super.key, required this.state});
  final AppState state;

  @override
  State<FocusView> createState() => _FocusViewState();
}

class _FocusViewState extends State<FocusView> with WidgetsBindingObserver {
  final _session = FocusTimer();
  int? _selectedTaskId;
  int _handledCompletions = 0;

  int get _totalSeconds => _session.totalSeconds;
  int get _remainingSeconds => _session.remainingSeconds;
  bool get _isRunning => _session.isRunning;
  String get _mode => _session.mode;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _session.addListener(_updateTimer);
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) _session.sync();
  }

  void _updateTimer() {
    if (!mounted) return;
    setState(() {});
    if (_session.completionCount != _handledCompletions) {
      _handledCompletions = _session.completionCount;
      HapticFeedback.heavyImpact();
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) _showFinishedDialog();
      });
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _session.removeListener(_updateTimer);
    _session.dispose();
    super.dispose();
  }

  void _setMode(String mode) {
    HapticFeedback.selectionClick();
    _session.setMode(mode);
  }

  void _startTimer() {
    HapticFeedback.lightImpact();
    _session.start();
  }

  void _pauseTimer() {
    HapticFeedback.lightImpact();
    _session.pause();
  }

  void _resetTimer() {
    HapticFeedback.selectionClick();
    _session.reset();
  }

  void _showFinishedDialog() {
    showDialog<void>(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Text(
          _mode == 'work' ? '🎉 Focus Session Complete!' : '☕ Break Finished!',
          style: const TextStyle(fontWeight: FontWeight.w800),
        ),
        content: Text(
          _mode == 'work'
              ? 'Great work! Take a short 5-minute break to recharge.'
              : 'Break time is over. Ready to start another focus block?',
        ),
        actions: [
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: brandPurple),
            onPressed: () {
              Navigator.pop(context);
              if (_mode == 'work') {
                _setMode('short_break');
              } else {
                _setMode('work');
              }
            },
            child: Text(_mode == 'work' ? 'Take Break' : 'Start Focus'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final pendingTasks =
        widget.state.tasks.where((t) => !t.completed).toList();
    final selectedTask = pendingTasks.where((task) => task.id == _selectedTaskId).firstOrNull ?? pendingTasks.firstOrNull;
    final minutes = (_remainingSeconds ~/ 60).toString().padLeft(2, '0');
    final seconds = (_remainingSeconds % 60).toString().padLeft(2, '0');
    final progress = _totalSeconds > 0
        ? (_totalSeconds - _remainingSeconds) / _totalSeconds
        : 0.0;

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const SizedBox(height: 6),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
            decoration: BoxDecoration(
              color: brandPurple.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(6),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 4.5,
                  height: 4.5,
                  decoration: const BoxDecoration(
                    color: brandPurple,
                    shape: BoxShape.circle,
                  ),
                ),
                const SizedBox(width: 5),
                const Text(
                  'DEEP WORK & TIMER',
                  style: TextStyle(
                    fontSize: 9.5,
                    fontWeight: FontWeight.w700,
                    letterSpacing: 0.8,
                    color: brandPurple,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 6),
          const Text(
            'Focus Session',
            style: TextStyle(
              fontSize: 26,
              fontWeight: FontWeight.w800,
              color: Color(0xFF1B1D28),
              letterSpacing: -0.6,
              height: 1.15,
            ),
          ),
          const SizedBox(height: 3),
          const Text(
            'Stay in the zone with interval timers',
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w400,
              color: Color(0xFF8E95A5),
              height: 1.3,
            ),
          ),
          const SizedBox(height: 18),
          // Mode Switcher Pills
          Container(
            padding: const EdgeInsets.all(4),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: const Color(0xFFF0F2F8), width: 1.2),
            ),
            child: Row(
              children: [
                _buildModeTab('work', 'Focus (25m)'),
                _buildModeTab('short_break', 'Short (5m)'),
                _buildModeTab('long_break', 'Long (15m)'),
              ],
            ),
          ),
          const SizedBox(height: 32),

          // Circular Countdown Dial
          Center(
            child: SizedBox(
              width: 240,
              height: 240,
              child: Stack(
                alignment: Alignment.center,
                children: [
                  // Outer Glow & Track
                  Container(
                    width: 220,
                    height: 220,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: Colors.white,
                      boxShadow: [
                        BoxShadow(
                          color: brandPurple.withValues(alpha: 0.08),
                          blurRadius: 30,
                          offset: const Offset(0, 10),
                        ),
                      ],
                    ),
                  ),
                  SizedBox(
                    width: 210,
                    height: 210,
                    child: CircularProgressIndicator(
                      value: progress,
                      strokeWidth: 10,
                      strokeCap: StrokeCap.round,
                      backgroundColor: const Color(0xFFECE9FF),
                      valueColor: const AlwaysStoppedAnimation(brandPurple),
                    ),
                  ),
                  Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        '$minutes:$seconds',
                        style: const TextStyle(
                          fontSize: 48,
                          fontWeight: FontWeight.w800,
                          letterSpacing: -1,
                          color: Color(0xFF1B1D28),
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        _mode == 'work' ? 'Deep Work' : 'Break Time',
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: Color(0xFF8E95A5),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 28),

          // Active Task Selector Card
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(18),
              border: Border.all(color: const Color(0xFFF0F2F8), width: 1.2),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.03),
                  blurRadius: 10,
                  offset: const Offset(0, 3),
                ),
              ],
            ),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: const Color(0xFFECE9FF),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(
                    Icons.adjust_rounded,
                    color: brandPurple,
                    size: 20,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Target Task',
                        style: TextStyle(
                          fontSize: 11.5,
                          fontWeight: FontWeight.w500,
                          color: Color(0xFF8E95A5),
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        selectedTask?.title ??
                            (pendingTasks.isNotEmpty
                                ? pendingTasks.first.title
                                : 'Take a deep breath'),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 14.5,
                          fontWeight: FontWeight.w700,
                          color: Color(0xFF1B1D28),
                        ),
                      ),
                    ],
                  ),
                ),
                if (pendingTasks.isNotEmpty)
                  PopupMenuButton<Task>(
                    icon: const Icon(
                      Icons.arrow_drop_down_circle_outlined,
                      color: Color(0xFF8E95A5),
                    ),
                    onSelected: (task) => setState(() => _selectedTaskId = task.id),
                    itemBuilder: (context) => [
                      for (final task in pendingTasks)
                        PopupMenuItem(
                          value: task,
                          child: Text(
                            task.title,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                    ],
                  ),
              ],
            ),
          ),
          const SizedBox(height: 24),

          // Play/Pause & Reset Controls
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              // Reset Button
              IconButton.filledTonal(
                onPressed: _resetTimer,
                iconSize: 24,
                style: IconButton.styleFrom(
                  backgroundColor: Colors.white,
                  foregroundColor: const Color(0xFF64748B),
                  side: const BorderSide(color: Color(0xFFE2E8F0)),
                  minimumSize: const Size(54, 54),
                ),
                icon: const Icon(Icons.refresh_rounded),
                tooltip: 'Reset timer',
              ),
              const SizedBox(width: 18),

              // Main Toggle Button
              FilledButton.icon(
                onPressed: _isRunning ? _pauseTimer : _startTimer,
                icon: Icon(
                  _isRunning ? Icons.pause_rounded : Icons.play_arrow_rounded,
                  size: 28,
                ),
                label: Text(
                  _isRunning ? 'Pause' : (_mode == 'work' ? 'Start Focus' : 'Start Break'),
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                style: FilledButton.styleFrom(
                  backgroundColor: brandPurple,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(
                    horizontal: 32,
                    vertical: 16,
                  ),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(18),
                  ),
                  elevation: 4,
                  shadowColor: brandPurple.withValues(alpha: 0.4),
                ),
              ),
            ],
          ),
          const SizedBox(height: 32),

          // Session Stats Row
          Row(
            children: [
              Expanded(
                child: _buildStatCard(
                  'Sessions finished',
                  '${_session.completedSessions}',
                  Icons.check_circle_outline_rounded,
                  const Color(0xFF10B981),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildStatCard(
                  'Focus completed',
                  '${_session.completedMinutes}m',
                  Icons.timer_outlined,
                  brandPurple,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildModeTab(String modeKey, String label) {
    final active = _mode == modeKey;
    return Expanded(
      child: GestureDetector(
        onTap: () => _setMode(modeKey),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          padding: const EdgeInsets.symmetric(vertical: 10),
          decoration: BoxDecoration(
            color: active ? const Color(0xFFECE9FF) : Colors.transparent,
            borderRadius: BorderRadius.circular(12),
          ),
          child: Center(
            child: Text(
              label,
              style: TextStyle(
                fontSize: 12.5,
                fontWeight: active ? FontWeight.w700 : FontWeight.w500,
                color: active ? brandPurple : const Color(0xFF8E95A5),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildStatCard(
    String label,
    String value,
    IconData icon,
    Color color,
  ) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFF0F2F8), width: 1.2),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, color: color, size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  value,
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF1B1D28),
                  ),
                ),
                Text(
                  label,
                  style: const TextStyle(
                    fontSize: 11,
                    color: Color(0xFF8E95A5),
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
