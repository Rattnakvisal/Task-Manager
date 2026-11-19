import 'dart:math' as math;
import 'dart:ui';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../app.dart';
import '../app_state.dart';
import '../focus_timer.dart';
import '../models.dart';
import '../widgets/sweet_alert.dart';

class FocusView extends StatefulWidget {
  const FocusView({super.key, required this.state});
  final AppState state;

  @override
  State<FocusView> createState() => _FocusViewState();
}

class _FocusViewState extends State<FocusView>
    with WidgetsBindingObserver, SingleTickerProviderStateMixin {
  FocusTimer get _session => widget.state.focus.timer;
  int? get _selectedTaskId => widget.state.focus.selectedTaskId;
  final _convertingNotes = <String>{};
  int? _ownerId;
  bool _reduceMotion = false;
  int _handledCompletions = 0;
  bool _completingTask = false;
  bool _isZenMode = false;

  // Distraction Scratchpad
  List<String> get _distractionNotes => widget.state.focus.notes;
  final TextEditingController _scratchpadController = TextEditingController();
  final FocusNode _scratchpadFocusNode = FocusNode();

  // Inspirational Quotes
  int _quoteIndex = 0;

  // Animation controllers
  late final AnimationController _pulseController;

  int get _totalSeconds => _session.totalSeconds;
  int get _remainingSeconds => _session.remainingSeconds;
  int get _flowSeconds => _session.flowSeconds;
  bool get _isRunning => _session.isRunning;
  bool get _isFlowMode => _session.isFlowMode;
  String get _mode => _session.mode;

  static const List<Map<String, String>> _quotes = [
    {
      'quote':
          'Focus is a muscle. The more you practice, the stronger it gets.',
      'author': 'Cal Newport',
    },
    {
      'quote':
          'You do not rise to the level of your goals. You fall to the level of your systems.',
      'author': 'James Clear',
    },
    {
      'quote':
          'Do less. But do what you do with complete, uninterrupted presence.',
      'author': 'Marcus Aurelius',
    },
    {
      'quote': 'The secret of getting ahead is simply getting started.',
      'author': 'Mark Twain',
    },
    {
      'quote': 'Deep work is the superpower of the twenty-first century.',
      'author': 'Deep Work Philosophy',
    },
  ];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _ownerId = widget.state.focus.userId;
    _handledCompletions = _session.completionCount;
    widget.state.focus.addListener(_updateTimer);
    widget.state.addListener(_updateTimer);

    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1800),
    );
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _reduceMotion =
        MediaQuery.disableAnimationsOf(context) ||
        !TickerMode.valuesOf(context).enabled;
    if (widget.state.user != null &&
        widget.state.focus.userId != widget.state.user!.id &&
        !widget.state.focus.loading) {
      widget.state.focus.attachUser(widget.state.user!.id);
    }
    _updateAnimations();
  }

  void _updateAnimations() {
    if (_isRunning && !_reduceMotion) {
      if (!_pulseController.isAnimating) _pulseController.repeat(reverse: true);
    } else {
      _pulseController.stop();
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    _session.sync();
    widget.state.focus.save();
    if (mounted) setState(() {});
  }

  void _updateTimer() {
    if (!mounted) return;
    if (_ownerId != widget.state.focus.userId) {
      _ownerId = widget.state.focus.userId;
      _handledCompletions = _session.completionCount;
      _scratchpadController.clear();
    }
    if (widget.state.user != null &&
        widget.state.focus.userId != widget.state.user!.id &&
        !widget.state.focus.loading) {
      widget.state.focus.attachUser(widget.state.user!.id);
    }
    _updateAnimations();
    setState(() {});
    if (!widget.state.focus.loading &&
        _session.completionCount > _handledCompletions) {
      _handledCompletions = _session.completionCount;
      final completedMode = _session.lastCompletedMode;
      final owner = _ownerId;
      HapticFeedback.heavyImpact();
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted && owner == widget.state.focus.userId) {
          _showFinishedDialog(completedMode);
        }
      });
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    widget.state.focus.removeListener(_updateTimer);
    widget.state.removeListener(_updateTimer);
    _pulseController.dispose();
    _scratchpadController.dispose();
    _scratchpadFocusNode.dispose();
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

  void _adjustMinutes(int delta) {
    HapticFeedback.selectionClick();
    _session.addMinutes(delta);
  }

  void _setCustomMinutes(int minutes) {
    HapticFeedback.selectionClick();
    _session.setWorkMinutes(minutes);
  }

  Color get _currentModeColor {
    switch (_mode) {
      case 'short_break':
        return const Color(0xFF10B981);
      case 'long_break':
        return const Color(0xFF0284C7);
      case 'flow':
        return const Color(0xFFF59E0B);
      case 'work':
      default:
        return brandPurple;
    }
  }

  Color get _currentModeLightBg {
    switch (_mode) {
      case 'short_break':
        return const Color(0xFFD1FAE5);
      case 'long_break':
        return const Color(0xFFE0F2FE);
      case 'flow':
        return const Color(0xFFFEF3C7);
      case 'work':
      default:
        return const Color(0xFFECE9FF);
    }
  }

  String get _modeTitle {
    switch (_mode) {
      case 'short_break':
        return 'Short Break';
      case 'long_break':
        return 'Long Break';
      case 'flow':
        return 'Flow State';
      case 'work':
      default:
        return 'Deep Work';
    }
  }

  void _addDistractionNote() {
    final text = _scratchpadController.text.trim();
    if (text.isEmpty) return;
    HapticFeedback.lightImpact();
    widget.state.focus.addNote(text);
    _scratchpadController.clear();
  }

  Future<void> _convertNoteToTask(String note) async {
    if (!_convertingNotes.add(note)) return;
    final owner = widget.state.focus.userId;
    setState(() {});
    try {
      await widget.state.saveTask({
        'title': note,
        'priority': 'medium',
        'status': 'pending',
        'category': 'Focus Note',
      });
      if (!mounted || owner != widget.state.focus.userId) return;
      widget.state.focus.removeNote(note);
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Created task: "$note"')));
    } catch (_) {
      if (mounted && owner == widget.state.focus.userId) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Could not create task. Your note was kept.'),
          ),
        );
      }
    } finally {
      _convertingNotes.remove(note);
      if (mounted) setState(() {});
    }
  }

  Future<void> _completeActiveTask(Task task) async {
    if (_completingTask || widget.state.isTaskBusy(task.id)) return;
    final current = widget.state.tasks
        .where((item) => item.id == task.id)
        .firstOrNull;
    if (current == null || current.completed) return;
    final owner = widget.state.focus.userId;
    setState(() => _completingTask = true);
    HapticFeedback.heavyImpact();
    try {
      await widget.state.saveTask({
        ...current.toPayload(),
        'status': 'completed',
      }, id: current.id);
      widget.state.focus.selectTask(null);
      if (mounted && owner == widget.state.focus.userId) {
        SweetAlert.success(
          context,
          title: '🎉 Great Job!',
          message: 'Completed "${task.title}" successfully!',
          confirmText: 'Awesome!',
          autoCloseDuration: const Duration(milliseconds: 2400),
        );
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(const SnackBar(content: Text('Could not update task.')));
      }
    } finally {
      if (mounted) setState(() => _completingTask = false);
    }
  }

  String _getSessionTypeLabel(int minutes) {
    if (minutes <= 15) return 'Quick Sprint';
    if (minutes <= 25) return 'Classic Pomodoro';
    if (minutes <= 45) return 'Deep Work Block';
    if (minutes <= 60) return 'Power Focus Block';
    return 'Endurance Session';
  }

  IconData _getSessionTypeIcon(int minutes) {
    if (minutes <= 15) return Icons.bolt_rounded;
    if (minutes <= 25) return Icons.timer_rounded;
    if (minutes <= 45) return Icons.psychology_rounded;
    if (minutes <= 60) return Icons.local_fire_department_rounded;
    return Icons.rocket_launch_rounded;
  }

  Color _getSessionTypeColor(int minutes) {
    if (minutes <= 15) return const Color(0xFF0284C7);
    if (minutes <= 25) return const Color(0xFF10B981);
    if (minutes <= 45) return const Color(0xFF8B5CF6);
    if (minutes <= 60) return const Color(0xFFF59E0B);
    return const Color(0xFFEF4444);
  }

  void _showCustomDurationDialog() {
    int selected = _session.workMinutes;
    showGeneralDialog<void>(
      context: context,
      barrierDismissible: true,
      barrierLabel: 'Dismiss',
      barrierColor: Colors.black.withValues(alpha: 0.52),
      transitionDuration: const Duration(milliseconds: 320),
      transitionBuilder: (ctx, anim, _, child) {
        final curved = CurvedAnimation(parent: anim, curve: Curves.easeOutBack);
        return BackdropFilter(
          filter: ImageFilter.blur(
            sigmaX: 10 * anim.value,
            sigmaY: 10 * anim.value,
          ),
          child: FadeTransition(
            opacity: anim,
            child: ScaleTransition(
              scale: Tween<double>(begin: 0.86, end: 1.0).animate(curved),
              child: child,
            ),
          ),
        );
      },
      pageBuilder: (ctx, _, anim) => StatefulBuilder(
        builder: (ctx, setDialogState) => Center(
          child: Material(
            color: Colors.transparent,
            child: Container(
              width: double.infinity,
              constraints: const BoxConstraints(maxWidth: 380),
              margin: const EdgeInsets.symmetric(horizontal: 24),
              padding: const EdgeInsets.fromLTRB(24, 28, 24, 24),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(28),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.24),
                    blurRadius: 40,
                    offset: const Offset(0, 16),
                  ),
                  BoxShadow(
                    color: brandPurple.withValues(alpha: 0.14),
                    blurRadius: 20,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  // 1. Floating Animated Glowing Top Icon Badge
                  Container(
                    width: 58,
                    height: 58,
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFF6366F1), Color(0xFF4F46E5)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      shape: BoxShape.circle,
                      boxShadow: [
                        BoxShadow(
                          color: const Color(
                            0xFF4F46E5,
                          ).withValues(alpha: 0.38),
                          blurRadius: 18,
                          offset: const Offset(0, 6),
                        ),
                      ],
                    ),
                    child: const Icon(
                      Icons.timer_outlined,
                      color: Colors.white,
                      size: 28,
                    ),
                  ),
                  const SizedBox(height: 16),

                  // 2. Title & Subtitle
                  const Text(
                    'Custom Focus Duration',
                    style: TextStyle(
                      fontSize: 18.5,
                      fontWeight: FontWeight.w800,
                      color: Color(0xFF1E293B),
                      letterSpacing: -0.3,
                    ),
                  ),
                  const SizedBox(height: 4),
                  const Text(
                    'Choose your ideal deep work interval',
                    style: TextStyle(
                      fontSize: 12.5,
                      color: Color(0xFF64748B),
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  const SizedBox(height: 18),

                  // 3. Hero Animated Value Card
                  Container(
                    padding: const EdgeInsets.symmetric(
                      vertical: 14,
                      horizontal: 20,
                    ),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF8FAFC),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(
                        color: const Color(0xFFF1F5F9),
                        width: 1.5,
                      ),
                    ),
                    child: Column(
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          crossAxisAlignment: CrossAxisAlignment.baseline,
                          textBaseline: TextBaseline.alphabetic,
                          children: [
                            AnimatedSwitcher(
                              duration: const Duration(milliseconds: 220),
                              transitionBuilder: (child, anim) =>
                                  FadeTransition(
                                    opacity: anim,
                                    child: ScaleTransition(
                                      scale: Tween<double>(
                                        begin: 0.82,
                                        end: 1.0,
                                      ).animate(anim),
                                      child: child,
                                    ),
                                  ),
                              child: Text(
                                '$selected',
                                key: ValueKey<int>(selected),
                                style: const TextStyle(
                                  fontSize: 46,
                                  fontWeight: FontWeight.w900,
                                  color: brandPurple,
                                  letterSpacing: -1.0,
                                  height: 1.0,
                                ),
                              ),
                            ),
                            const SizedBox(width: 8),
                            const Text(
                              'MINUTES',
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w800,
                                letterSpacing: 0.8,
                                color: Color(0xFF64748B),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        // Session Intensity Badge
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 4,
                          ),
                          decoration: BoxDecoration(
                            color: _getSessionTypeColor(
                              selected,
                            ).withValues(alpha: 0.12),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(
                                _getSessionTypeIcon(selected),
                                size: 13,
                                color: _getSessionTypeColor(selected),
                              ),
                              const SizedBox(width: 5),
                              Text(
                                _getSessionTypeLabel(selected),
                                style: TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w700,
                                  color: _getSessionTypeColor(selected),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),

                  // 4. Glowing Interactive Slider with Min/Max
                  SliderTheme(
                    data: SliderTheme.of(ctx).copyWith(
                      trackHeight: 6,
                      activeTrackColor: brandPurple,
                      inactiveTrackColor: const Color(0xFFE2E8F0),
                      thumbColor: brandPurple,
                      thumbShape: const RoundSliderThumbShape(
                        enabledThumbRadius: 10,
                        elevation: 3,
                      ),
                      overlayColor: brandPurple.withValues(alpha: 0.18),
                      overlayShape: const RoundSliderOverlayShape(
                        overlayRadius: 20,
                      ),
                    ),
                    child: Slider(
                      value: selected.toDouble(),
                      min: 5,
                      max: 120,
                      divisions: 23,
                      onChanged: (v) {
                        final rounded = v.round();
                        if (rounded != selected) {
                          HapticFeedback.selectionClick();
                          setDialogState(() => selected = rounded);
                        }
                      },
                    ),
                  ),
                  const Padding(
                    padding: EdgeInsets.symmetric(horizontal: 8),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          '5m sprint',
                          style: TextStyle(
                            fontSize: 11,
                            color: Color(0xFF94A3B8),
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        Text(
                          '120m deep',
                          style: TextStyle(
                            fontSize: 11,
                            color: Color(0xFF94A3B8),
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),

                  // 5. Preset Chips with Smooth Selection Animation
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    alignment: WrapAlignment.center,
                    children: [15, 25, 45, 60, 90].map((m) {
                      final active = selected == m;
                      return GestureDetector(
                        onTap: () {
                          HapticFeedback.selectionClick();
                          setDialogState(() => selected = m);
                        },
                        child: AnimatedContainer(
                          duration: const Duration(milliseconds: 180),
                          padding: const EdgeInsets.symmetric(
                            horizontal: 14,
                            vertical: 8,
                          ),
                          decoration: BoxDecoration(
                            color: active
                                ? brandPurple
                                : const Color(0xFFF1F5F9),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: active
                                  ? brandPurple
                                  : const Color(0xFFE2E8F0),
                              width: 1.2,
                            ),
                            boxShadow: active
                                ? [
                                    BoxShadow(
                                      color: brandPurple.withValues(
                                        alpha: 0.35,
                                      ),
                                      blurRadius: 8,
                                      offset: const Offset(0, 3),
                                    ),
                                  ]
                                : null,
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              if (active) ...[
                                const Icon(
                                  Icons.check_rounded,
                                  color: Colors.white,
                                  size: 14,
                                ),
                                const SizedBox(width: 4),
                              ],
                              Text(
                                '${m}m',
                                style: TextStyle(
                                  fontSize: 13,
                                  fontWeight: active
                                      ? FontWeight.w800
                                      : FontWeight.w600,
                                  color: active
                                      ? Colors.white
                                      : const Color(0xFF475569),
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    }).toList(),
                  ),
                  const SizedBox(height: 24),

                  // 6. Action Buttons Row (iPhone style)
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton(
                          style: OutlinedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(vertical: 14),
                            side: const BorderSide(color: Color(0xFFE2E8F0)),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(16),
                            ),
                            backgroundColor: const Color(0xFFF8FAFC),
                          ),
                          onPressed: () => Navigator.pop(ctx),
                          child: const Text(
                            'Cancel',
                            style: TextStyle(
                              fontSize: 14.5,
                              fontWeight: FontWeight.w600,
                              color: Color(0xFF64748B),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Container(
                          height: 48,
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(
                              colors: [Color(0xFF6366F1), Color(0xFF4F46E5)],
                            ),
                            borderRadius: BorderRadius.circular(16),
                            boxShadow: [
                              BoxShadow(
                                color: const Color(
                                  0xFF4F46E5,
                                ).withValues(alpha: 0.35),
                                blurRadius: 10,
                                offset: const Offset(0, 4),
                              ),
                            ],
                          ),
                          child: ElevatedButton(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.transparent,
                              shadowColor: Colors.transparent,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(16),
                              ),
                            ),
                            onPressed: () {
                              HapticFeedback.lightImpact();
                              _setCustomMinutes(selected);
                              Navigator.pop(ctx);
                            },
                            child: const Text(
                              'Set Duration',
                              style: TextStyle(
                                fontSize: 14.5,
                                fontWeight: FontWeight.w800,
                                color: Colors.white,
                              ),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  void _showTaskPicker() {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => ListenableBuilder(
        listenable: widget.state,
        builder: (ctx, _) {
          final pendingTasks = widget.state.tasks
              .where((t) => !t.completed)
              .toList();
          return Container(
            height: MediaQuery.of(ctx).size.height * 0.65,
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
            ),
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: const Color(0xFFE2E8F0),
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                const Text(
                  'Select Focus Target',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF1B1D28),
                  ),
                ),
                const SizedBox(height: 4),
                const Text(
                  'Pick a task to focus on during this block',
                  style: TextStyle(fontSize: 13, color: Color(0xFF8E95A5)),
                ),
                const SizedBox(height: 16),
                Expanded(
                  child: pendingTasks.isEmpty
                      ? const Center(
                          child: Text(
                            'No pending tasks found.\nTake a mindful break!',
                            textAlign: TextAlign.center,
                            style: TextStyle(color: Color(0xFF8E95A5)),
                          ),
                        )
                      : ListView.separated(
                          itemCount: pendingTasks.length + 1,
                          separatorBuilder: (_, index) => const Divider(
                            height: 1,
                            color: Color(0xFFF1F5F9),
                          ),
                          itemBuilder: (ctx, index) {
                            if (index == 0) {
                              final isNone = _selectedTaskId == -1;
                              return ListTile(
                                contentPadding: const EdgeInsets.symmetric(
                                  horizontal: 4,
                                  vertical: 4,
                                ),
                                leading: Container(
                                  width: 36,
                                  height: 36,
                                  decoration: BoxDecoration(
                                    color: isNone
                                        ? brandPurple
                                        : const Color(0xFFF1F5F9),
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                  child: Icon(
                                    Icons.self_improvement_rounded,
                                    size: 18,
                                    color: isNone
                                        ? Colors.white
                                        : const Color(0xFF64748B),
                                  ),
                                ),
                                title: Text(
                                  'Mindful Flow / No Task',
                                  style: TextStyle(
                                    fontWeight: isNone
                                        ? FontWeight.w700
                                        : FontWeight.w500,
                                    color: isNone
                                        ? brandPurple
                                        : const Color(0xFF1B1D28),
                                  ),
                                ),
                                subtitle: const Text(
                                  'Open-ended focus without targeting a specific task',
                                  style: TextStyle(
                                    fontSize: 11,
                                    color: Color(0xFF8E95A5),
                                  ),
                                ),
                                trailing: isNone
                                    ? const Icon(
                                        Icons.check_circle_rounded,
                                        color: brandPurple,
                                      )
                                    : null,
                                onTap: () {
                                  widget.state.focus.selectTask(-1);
                                  Navigator.pop(ctx);
                                },
                              );
                            }
                            final task = pendingTasks[index - 1];
                            final isSelected = task.id == _selectedTaskId;
                            return ListTile(
                              contentPadding: const EdgeInsets.symmetric(
                                horizontal: 4,
                                vertical: 4,
                              ),
                              leading: Container(
                                width: 36,
                                height: 36,
                                decoration: BoxDecoration(
                                  color: isSelected
                                      ? brandPurple
                                      : const Color(0xFFF1F5F9),
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                child: Icon(
                                  Icons.task_alt_rounded,
                                  size: 18,
                                  color: isSelected
                                      ? Colors.white
                                      : const Color(0xFF64748B),
                                ),
                              ),
                              title: Text(
                                task.title,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  fontWeight: isSelected
                                      ? FontWeight.w700
                                      : FontWeight.w500,
                                  color: isSelected
                                      ? brandPurple
                                      : const Color(0xFF1B1D28),
                                ),
                              ),
                              subtitle: Text(
                                '${task.category.isNotEmpty ? task.category : "General"} • ${task.priority.toUpperCase()} priority',
                                style: const TextStyle(
                                  fontSize: 11,
                                  color: Color(0xFF8E95A5),
                                ),
                              ),
                              trailing: isSelected
                                  ? const Icon(
                                      Icons.check_circle_rounded,
                                      color: brandPurple,
                                    )
                                  : null,
                              onTap: () {
                                widget.state.focus.selectTask(task.id);
                                Navigator.pop(ctx);
                              },
                            );
                          },
                        ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  void _showFinishedDialog(String? completedMode) {
    final wasFocus = completedMode == 'work' || completedMode == 'flow';
    showGeneralDialog<void>(
      context: context,
      barrierDismissible: true,
      barrierLabel: 'Dismiss',
      barrierColor: Colors.black.withValues(alpha: 0.52),
      transitionDuration: const Duration(milliseconds: 320),
      transitionBuilder: (ctx, anim, _, child) {
        final curved = CurvedAnimation(parent: anim, curve: Curves.easeOutBack);
        return BackdropFilter(
          filter: ImageFilter.blur(
            sigmaX: 10 * anim.value,
            sigmaY: 10 * anim.value,
          ),
          child: FadeTransition(
            opacity: anim,
            child: ScaleTransition(
              scale: Tween<double>(begin: 0.86, end: 1.0).animate(curved),
              child: child,
            ),
          ),
        );
      },
      pageBuilder: (ctx, _, anim) => Center(
        child: Material(
          color: Colors.transparent,
          child: Container(
            width: double.infinity,
            constraints: const BoxConstraints(maxWidth: 380),
            margin: const EdgeInsets.symmetric(horizontal: 24),
            padding: const EdgeInsets.fromLTRB(24, 28, 24, 24),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(28),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.24),
                  blurRadius: 40,
                  offset: const Offset(0, 16),
                ),
              ],
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 64,
                  height: 64,
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: wasFocus
                          ? [const Color(0xFFF59E0B), const Color(0xFFD97706)]
                          : [const Color(0xFF10B981), const Color(0xFF059669)],
                    ),
                    shape: BoxShape.circle,
                    boxShadow: [
                      BoxShadow(
                        color:
                            (wasFocus
                                    ? const Color(0xFFD97706)
                                    : const Color(0xFF059669))
                                .withValues(alpha: 0.35),
                        blurRadius: 18,
                        offset: const Offset(0, 6),
                      ),
                    ],
                  ),
                  child: Icon(
                    wasFocus
                        ? Icons.emoji_events_rounded
                        : Icons.coffee_rounded,
                    color: Colors.white,
                    size: 32,
                  ),
                ),
                const SizedBox(height: 18),
                Text(
                  wasFocus ? '🎉 Focus Session Complete!' : '☕ Break Finished!',
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontSize: 19,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF1E293B),
                    letterSpacing: -0.3,
                  ),
                ),
                const SizedBox(height: 10),
                Text(
                  wasFocus
                      ? 'Incredible focus! Take a short 5-minute break to hydrate, stretch, and let your mind recharge.'
                      : 'Break time is over. Ready to dive into another high-value deep work block?',
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontSize: 13.5,
                    color: Color(0xFF64748B),
                    height: 1.45,
                  ),
                ),
                const SizedBox(height: 24),
                Container(
                  width: double.infinity,
                  height: 48,
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      colors: [Color(0xFF6366F1), Color(0xFF4F46E5)],
                    ),
                    borderRadius: BorderRadius.circular(16),
                    boxShadow: [
                      BoxShadow(
                        color: const Color(
                          0xFF4F46E5,
                        ).withValues(alpha: 0.35),
                        blurRadius: 10,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.transparent,
                      shadowColor: Colors.transparent,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                    ),
                    onPressed: () {
                      HapticFeedback.lightImpact();
                      Navigator.pop(ctx);
                      if (wasFocus) {
                        _setMode('short_break');
                      } else {
                        _setMode('work');
                      }
                    },
                    child: Text(
                      wasFocus ? 'Take Break (5m)' : 'Start Next Focus',
                      style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w800,
                        color: Colors.white,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (widget.state.focus.loading) {
      return const Padding(
        padding: EdgeInsets.all(32),
        child: Center(child: CircularProgressIndicator()),
      );
    }
    if (_isZenMode) {
      return _buildZenMode();
    }

    final pendingTasks = widget.state.tasks.where((t) => !t.completed).toList();
    final selectedTask = _selectedTaskId == -1
        ? null
        : (_selectedTaskId != null
              ? pendingTasks
                    .where((task) => task.id == _selectedTaskId)
                    .firstOrNull
              : pendingTasks.firstOrNull);

    final displaySeconds = _isFlowMode ? _flowSeconds : _remainingSeconds;
    final minutes = (displaySeconds ~/ 60).toString().padLeft(2, '0');
    final seconds = (displaySeconds % 60).toString().padLeft(2, '0');

    final progress = _isFlowMode
        ? 1.0
        : (_totalSeconds > 0
              ? (_totalSeconds - _remainingSeconds) / _totalSeconds
              : 0.0);

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const SizedBox(height: 6),
          // 1. Top Bar: Badge & Actions
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
                decoration: BoxDecoration(
                  color: brandPurple.withValues(alpha: 0.08),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    AnimatedBuilder(
                      animation: _pulseController,
                      builder: (context, _) => Container(
                        width: 6,
                        height: 6,
                        decoration: BoxDecoration(
                          color: _isRunning ? _currentModeColor : brandPurple,
                          shape: BoxShape.circle,
                          boxShadow: _isRunning
                              ? [
                                  BoxShadow(
                                    color: _currentModeColor.withValues(
                                      alpha: _pulseController.value * 0.8,
                                    ),
                                    blurRadius: 6,
                                    spreadRadius: 1,
                                  ),
                                ]
                              : null,
                        ),
                      ),
                    ),
                    const SizedBox(width: 6),
                    const Text(
                      'DEEP WORK & TIMER',
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w700,
                        letterSpacing: 0.8,
                        color: brandPurple,
                      ),
                    ),
                  ],
                ),
              ),
              Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  IconButton(
                    icon: const Icon(Icons.tune_rounded, size: 20),
                    tooltip: 'Adjust Duration',
                    style: IconButton.styleFrom(
                      foregroundColor: const Color(0xFF64748B),
                      backgroundColor: Colors.white,
                      padding: const EdgeInsets.all(7),
                      minimumSize: const Size(38, 38),
                      side: const BorderSide(color: Color(0xFFE2E8F0)),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    onPressed: _showCustomDurationDialog,
                  ),
                  const SizedBox(width: 8),
                  IconButton(
                    icon: const Icon(Icons.fullscreen_rounded, size: 22),
                    tooltip: 'Zen Mode',
                    style: IconButton.styleFrom(
                      foregroundColor: brandPurple,
                      backgroundColor: brandPurple.withValues(alpha: 0.08),
                      padding: const EdgeInsets.all(7),
                      minimumSize: const Size(38, 38),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    onPressed: () {
                      HapticFeedback.lightImpact();
                      setState(() => _isZenMode = true);
                    },
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 8),
          const Text(
            'Focus Session',
            style: TextStyle(
              fontSize: 27,
              fontWeight: FontWeight.w800,
              color: Color(0xFF1B1D28),
              letterSpacing: -0.6,
              height: 1.15,
            ),
          ),
          const SizedBox(height: 4),
          const Text(
            'Stay in the zone with interval timers & deep flow tools',
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w400,
              color: Color(0xFF8E95A5),
              height: 1.3,
            ),
          ),
          const SizedBox(height: 18),

          // 2. iOS-style Sliding Capsule Segmented Control
          Container(
            padding: const EdgeInsets.all(4),
            decoration: BoxDecoration(
              color: const Color(0xFFF1F5F9),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: const Color(0xFFE2E8F0), width: 1.0),
            ),
            child: Row(
              children: [
                _buildModeTab('work', 'Focus (${_session.workMinutes}m)'),
                _buildModeTab('short_break', 'Short (5m)'),
                _buildModeTab('long_break', 'Long (15m)'),
                _buildModeTab('flow', 'Flow ⏱'),
              ],
            ),
          ),
          const SizedBox(height: 14),

          // 3. Quick Preset Pills (for Work Mode)
          if (_mode == 'work') ...[
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              physics: const BouncingScrollPhysics(),
              child: Row(
                children: [
                  const Text(
                    'Presets: ',
                    style: TextStyle(
                      fontSize: 11.5,
                      fontWeight: FontWeight.w600,
                      color: Color(0xFF8E95A5),
                    ),
                  ),
                  const SizedBox(width: 4),
                  _buildPresetChip(15, 'Sprint', Icons.bolt_rounded),
                  _buildPresetChip(25, 'Pomodoro', Icons.timer_rounded),
                  _buildPresetChip(45, 'Deep Work', Icons.psychology_rounded),
                  _buildPresetChip(
                    60,
                    'Ultradian',
                    Icons.local_fire_department_rounded,
                  ),
                  ActionChip(
                    avatar: const Icon(
                      Icons.tune_rounded,
                      size: 14,
                      color: brandPurple,
                    ),
                    label: const Text('Custom'),
                    labelStyle: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: brandPurple,
                    ),
                    backgroundColor: Colors.white,
                    side: const BorderSide(color: Color(0xFFE2E8F0)),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                    onPressed: _showCustomDurationDialog,
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
          ] else
            const SizedBox(height: 10),

          // 4. Hero Apple-style Circular Countdown Dial
          Center(
            child: SizedBox(
              width: 260,
              height: 260,
              child: Stack(
                alignment: Alignment.center,
                children: [
                  // Outer Glow & Shadow
                  AnimatedBuilder(
                    animation: _pulseController,
                    builder: (context, child) {
                      final glowVal = _isRunning ? _pulseController.value : 0.0;
                      return Container(
                        width: 240,
                        height: 240,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: Colors.white,
                          boxShadow: [
                            BoxShadow(
                              color: _currentModeColor.withValues(
                                alpha: 0.08 + (glowVal * 0.12),
                              ),
                              blurRadius: 36 + (glowVal * 12),
                              spreadRadius: glowVal * 4,
                              offset: const Offset(0, 8),
                            ),
                            BoxShadow(
                              color: Colors.black.withValues(alpha: 0.03),
                              blurRadius: 12,
                              offset: const Offset(0, 2),
                            ),
                          ],
                        ),
                      );
                    },
                  ),

                  // Circular Progress Indicator Ring
                  SizedBox(
                    width: 226,
                    height: 226,
                    child: _isFlowMode
                        ? AnimatedBuilder(
                            animation: _pulseController,
                            builder: (context, _) => CircularProgressIndicator(
                              value: null,
                              strokeWidth: 10,
                              strokeCap: StrokeCap.round,
                              backgroundColor: _currentModeLightBg,
                              valueColor: AlwaysStoppedAnimation(
                                _currentModeColor,
                              ),
                            ),
                          )
                        : CircularProgressIndicator(
                            value: progress,
                            strokeWidth: 10,
                            strokeCap: StrokeCap.round,
                            backgroundColor: _currentModeLightBg,
                            valueColor: AlwaysStoppedAnimation(
                              _currentModeColor,
                            ),
                          ),
                  ),

                  // Center Time & Mode Information
                  Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      // Status Pill Badge
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 9,
                          vertical: 3,
                        ),
                        decoration: BoxDecoration(
                          color: _currentModeLightBg,
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Container(
                              width: 6,
                              height: 6,
                              decoration: BoxDecoration(
                                color: _isRunning
                                    ? const Color(0xFF10B981)
                                    : const Color(0xFF94A3B8),
                                shape: BoxShape.circle,
                              ),
                            ),
                            const SizedBox(width: 5),
                            Text(
                              _isRunning
                                  ? (_mode == 'work' ? 'FOCUSING' : 'RESTING')
                                  : 'PAUSED',
                              style: TextStyle(
                                fontSize: 9.5,
                                fontWeight: FontWeight.w800,
                                letterSpacing: 0.6,
                                color: _currentModeColor,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        '$minutes:$seconds',
                        style: const TextStyle(
                          fontSize: 50,
                          fontWeight: FontWeight.w900,
                          letterSpacing: -1.2,
                          color: Color(0xFF1B1D28),
                          fontFeatures: [FontFeature.tabularFigures()],
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        _modeTitle,
                        style: const TextStyle(
                          fontSize: 13.5,
                          fontWeight: FontWeight.w600,
                          color: Color(0xFF8E95A5),
                        ),
                      ),
                      if (!_isFlowMode) ...[
                        const SizedBox(height: 8),
                        Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            _buildMicroAdjustButton(
                              '-5m',
                              () => _adjustMinutes(-5),
                            ),
                            const SizedBox(width: 8),
                            _buildMicroAdjustButton(
                              '+5m',
                              () => _adjustMinutes(5),
                            ),
                          ],
                        ),
                      ],
                    ],
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 22),

          // 5. Tactile iPhone Action Buttons (Play/Pause/Reset) - Perfectly Centered
          Center(
            child: FittedBox(
              fit: BoxFit.scaleDown,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                mainAxisSize: MainAxisSize.min,
                children: [
                  // Reset Button (iOS circular frosted glass)
                  Container(
                    width: 54,
                    height: 54,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      shape: BoxShape.circle,
                      border: Border.all(color: const Color(0xFFE2E8F0), width: 1.2),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.04),
                          blurRadius: 10,
                          offset: const Offset(0, 3),
                        ),
                      ],
                    ),
                    child: IconButton(
                      onPressed: _resetTimer,
                      iconSize: 22,
                      color: const Color(0xFF64748B),
                      icon: const Icon(Icons.refresh_rounded),
                      tooltip: 'Reset timer',
                    ),
                  ),
                  const SizedBox(width: 16),

                  // Hero Capsule Action Button (Apple iOS Style)
                  Container(
                    height: 56,
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: _isRunning
                            ? [const Color(0xFFF59E0B), const Color(0xFFD97706)]
                            : (_mode == 'work'
                                  ? [
                                      const Color(0xFF6366F1),
                                      const Color(0xFF4F46E5),
                                    ]
                                  : (_mode == 'flow'
                                        ? [
                                            const Color(0xFFF59E0B),
                                            const Color(0xFFD97706),
                                          ]
                                        : [
                                            const Color(0xFF10B981),
                                            const Color(0xFF059669),
                                          ])),
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(100),
                      boxShadow: [
                        BoxShadow(
                          color: (_isRunning
                                  ? const Color(0xFFF59E0B)
                                  : _currentModeColor)
                              .withValues(alpha: 0.38),
                          blurRadius: 16,
                          offset: const Offset(0, 6),
                        ),
                      ],
                    ),
                    child: ElevatedButton.icon(
                      onPressed: _isRunning ? _pauseTimer : _startTimer,
                      icon: Icon(
                        _isRunning
                            ? Icons.pause_rounded
                            : Icons.play_arrow_rounded,
                        size: 26,
                        color: Colors.white,
                      ),
                      label: Text(
                        _isRunning
                            ? 'Pause'
                            : (_mode == 'work'
                                  ? 'Start Focus'
                                  : (_mode == 'flow'
                                        ? 'Start Flow'
                                        : 'Start Break')),
                        style: const TextStyle(
                          fontSize: 16.5,
                          fontWeight: FontWeight.w800,
                          color: Colors.white,
                          letterSpacing: 0.3,
                        ),
                      ),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.transparent,
                        shadowColor: Colors.transparent,
                        padding: const EdgeInsets.symmetric(
                          horizontal: 36,
                          vertical: 15,
                        ),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(100),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 16),

                  // Skip / Finish Early Button (iOS circular frosted glass)
                  Container(
                    width: 54,
                    height: 54,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      shape: BoxShape.circle,
                      border: Border.all(color: const Color(0xFFE2E8F0), width: 1.2),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.04),
                          blurRadius: 10,
                          offset: const Offset(0, 3),
                        ),
                      ],
                    ),
                    child: IconButton(
                      onPressed: () {
                        HapticFeedback.mediumImpact();
                        _session.finishSessionEarly();
                      },
                      iconSize: 22,
                      color: const Color(0xFF64748B),
                      icon: const Icon(Icons.skip_next_rounded),
                      tooltip: 'Finish / Skip interval',
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),

          // 6. Target Focus Task Card (Integrated iOS Reminders Anchor)
          _buildTargetTaskCard(selectedTask, pendingTasks),
          const SizedBox(height: 20),

          // 7. Brain Dump & Distraction Pad (iOS Quick Notes Tool)
          _buildBrainDumpCard(),
          const SizedBox(height: 20),

          // 8. Daily Focus Target & Activity Stats (Apple Fitness Style)
          _buildDailyGoalsCard(),
          const SizedBox(height: 20),

          // 9. Motivational Quote Card
          _buildMotivationalQuoteCard(),
          const SizedBox(height: 32),
        ],
      ),
    );
  }

  Widget _buildMicroAdjustButton(String label, VoidCallback onTap) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(10),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        decoration: BoxDecoration(
          color: const Color(0xFFF1F5F9),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: const Color(0xFFE2E8F0), width: 1.0),
        ),
        child: Text(
          label,
          style: const TextStyle(
            fontSize: 11.5,
            fontWeight: FontWeight.w700,
            color: Color(0xFF475569),
          ),
        ),
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
          curve: Curves.easeInOut,
          padding: const EdgeInsets.symmetric(vertical: 9, horizontal: 2),
          decoration: BoxDecoration(
            color: active ? Colors.white : Colors.transparent,
            borderRadius: BorderRadius.circular(12),
            boxShadow: active
                ? [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.08),
                      blurRadius: 4,
                      offset: const Offset(0, 2),
                    ),
                  ]
                : null,
          ),
          child: Center(
            child: FittedBox(
              fit: BoxFit.scaleDown,
              child: Text(
                label,
                maxLines: 1,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: active ? FontWeight.w800 : FontWeight.w600,
                  color: active ? _currentModeColor : const Color(0xFF64748B),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildPresetChip(int minutes, String label, IconData icon) {
    final active = _session.workMinutes == minutes;
    return Padding(
      padding: const EdgeInsets.only(right: 6),
      child: ChoiceChip(
        avatar: Icon(
          icon,
          size: 14,
          color: active ? Colors.white : const Color(0xFF64748B),
        ),
        label: Text('$minutes min ($label)'),
        selected: active,
        selectedColor: brandPurple,
        labelStyle: TextStyle(
          fontSize: 11,
          fontWeight: active ? FontWeight.w700 : FontWeight.w500,
          color: active ? Colors.white : const Color(0xFF64748B),
        ),
        backgroundColor: Colors.white,
        side: BorderSide(color: active ? brandPurple : const Color(0xFFE2E8F0)),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        onSelected: (_) => _setCustomMinutes(minutes),
      ),
    );
  }

  // Target Focus Task Card
  Widget _buildTargetTaskCard(Task? selectedTask, List<Task> pendingTasks) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFFF0F2F8), width: 1.2),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(9),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFFECE9FF), Color(0xFFE0E7FF)],
                  ),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.adjust_rounded,
                  color: brandPurple,
                  size: 22,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'TARGET FOCUS TASK',
                      style: TextStyle(
                        fontSize: 10.5,
                        fontWeight: FontWeight.w700,
                        letterSpacing: 0.6,
                        color: Color(0xFF8E95A5),
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      selectedTask?.title ??
                          (pendingTasks.isNotEmpty
                              ? pendingTasks.first.title
                              : 'Mindful breathing & deep flow'),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 15.5,
                        fontWeight: FontWeight.w800,
                        color: Color(0xFF1B1D28),
                        height: 1.25,
                      ),
                    ),
                  ],
                ),
              ),
              if (pendingTasks.isNotEmpty)
                IconButton(
                  icon: const Icon(
                    Icons.swap_horiz_rounded,
                    color: Color(0xFF64748B),
                  ),
                  tooltip: 'Switch Task',
                  onPressed: () => _showTaskPicker(),
                ),
            ],
          ),
          if (selectedTask != null) ...[
            const SizedBox(height: 12),
            Wrap(
              spacing: 6,
              runSpacing: 4,
              children: [
                if (selectedTask.category.isNotEmpty)
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 3,
                    ),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF1F5F9),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      selectedTask.category,
                      style: const TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF475569),
                      ),
                    ),
                  ),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 3,
                  ),
                  decoration: BoxDecoration(
                    color: selectedTask.priority == 'high'
                        ? const Color(0xFFFFE4E6)
                        : (selectedTask.priority == 'medium'
                              ? const Color(0xFFFEF3C7)
                              : const Color(0xFFE2E8F0)),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    '${selectedTask.priority.toUpperCase()} priority',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: selectedTask.priority == 'high'
                          ? const Color(0xFFE11D48)
                          : (selectedTask.priority == 'medium'
                                ? const Color(0xFFD97706)
                                : const Color(0xFF475569)),
                    ),
                  ),
                ),
                if (selectedTask.subtasks.isNotEmpty)
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 3,
                    ),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF0FDF4),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      '✓ ${selectedTask.completedSubtasks}/${selectedTask.subtasks.length} Subtasks',
                      style: const TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF16A34A),
                      ),
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 14),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _completingTask
                        ? null
                        : () => _completeActiveTask(selectedTask),
                    icon: _completingTask
                        ? const SizedBox(
                            width: 16,
                            height: 16,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.check_circle_rounded, size: 18),
                    label: const Text('Mark Done'),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: const Color(0xFF10B981),
                      side: const BorderSide(
                        color: Color(0xFF10B981),
                        width: 1.2,
                      ),
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () => _showTaskPicker(),
                    icon: const Icon(Icons.list_alt_rounded, size: 18),
                    label: const Text('Change Task'),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: const Color(0xFF64748B),
                      side: const BorderSide(
                        color: Color(0xFFE2E8F0),
                        width: 1.2,
                      ),
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ] else ...[
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Row(
                children: [
                  const Icon(
                    Icons.self_improvement_rounded,
                    size: 20,
                    color: brandPurple,
                  ),
                  const SizedBox(width: 10),
                  const Expanded(
                    child: Text(
                      'No specific task selected. Focusing freely on deep flow.',
                      style: TextStyle(
                        fontSize: 12.5,
                        color: Color(0xFF64748B),
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                  if (pendingTasks.isNotEmpty)
                    TextButton(
                      onPressed: () => _showTaskPicker(),
                      child: const Text('Pick Task'),
                    ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  // Distraction Pad / Brain Dump Tool
  Widget _buildBrainDumpCard() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFFF0F2F8), width: 1.2),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(9),
                decoration: BoxDecoration(
                  color: const Color(0xFFFEF3C7),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.lightbulb_outline_rounded,
                  color: Color(0xFFD97706),
                  size: 20,
                ),
              ),
              const SizedBox(width: 12),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'BRAIN DUMP & DISTRACTION PAD',
                      style: TextStyle(
                        fontSize: 10.5,
                        fontWeight: FontWeight.w700,
                        letterSpacing: 0.6,
                        color: Color(0xFF8E95A5),
                      ),
                    ),
                    Text(
                      'Capture stray thoughts without breaking flow',
                      style: TextStyle(
                        fontSize: 12.5,
                        color: Color(0xFF64748B),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _scratchpadController,
                  focusNode: _scratchpadFocusNode,
                  onSubmitted: (_) => _addDistractionNote(),
                  style: const TextStyle(fontSize: 13.5),
                  decoration: InputDecoration(
                    hintText: 'Quick idea, to-do, or stray thought…',
                    hintStyle: const TextStyle(
                      fontSize: 13,
                      color: Color(0xFFA0AEC0),
                    ),
                    contentPadding: const EdgeInsets.symmetric(
                      horizontal: 14,
                      vertical: 11,
                    ),
                    filled: true,
                    fillColor: const Color(0xFFF8FAFC),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(14),
                      borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(14),
                      borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(14),
                      borderSide: const BorderSide(
                        color: brandPurple,
                        width: 1.5,
                      ),
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              FilledButton(
                style: FilledButton.styleFrom(
                  backgroundColor: const Color(0xFF1B1D28),
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  minimumSize: const Size(46, 46),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14),
                  ),
                ),
                onPressed: _addDistractionNote,
                child: const Icon(Icons.add_rounded, size: 20),
              ),
            ],
          ),
          if (_distractionNotes.isNotEmpty) ...[
            const SizedBox(height: 12),
            ..._distractionNotes.map(
              (note) => Container(
                margin: const EdgeInsets.only(bottom: 6),
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 9,
                ),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.circle, size: 6, color: Color(0xFF94A3B8)),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        note,
                        style: const TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w500,
                          color: Color(0xFF334155),
                        ),
                      ),
                    ),
                    IconButton(
                      icon: const Icon(
                        Icons.add_task_rounded,
                        size: 18,
                        color: brandPurple,
                      ),
                      tooltip: 'Convert to Task',
                      onPressed: _convertingNotes.contains(note)
                          ? null
                          : () => _convertNoteToTask(note),
                      padding: EdgeInsets.zero,
                      constraints: const BoxConstraints(),
                    ),
                    const SizedBox(width: 10),
                    IconButton(
                      icon: const Icon(
                        Icons.close_rounded,
                        size: 18,
                        color: Color(0xFF94A3B8),
                      ),
                      tooltip: 'Dismiss',
                      onPressed: _convertingNotes.contains(note)
                          ? null
                          : () => widget.state.focus.removeNote(note),
                      padding: EdgeInsets.zero,
                      constraints: const BoxConstraints(),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }

  void _showDailyGoalDialog() {
    var goal = _session.dailyGoalSessions;
    showGeneralDialog<void>(
      context: context,
      barrierDismissible: true,
      barrierLabel: 'Dismiss',
      barrierColor: Colors.black.withValues(alpha: 0.52),
      transitionDuration: const Duration(milliseconds: 320),
      transitionBuilder: (ctx, anim, _, child) {
        final curved = CurvedAnimation(parent: anim, curve: Curves.easeOutBack);
        return BackdropFilter(
          filter: ImageFilter.blur(
            sigmaX: 10 * anim.value,
            sigmaY: 10 * anim.value,
          ),
          child: FadeTransition(
            opacity: anim,
            child: ScaleTransition(
              scale: Tween<double>(begin: 0.86, end: 1.0).animate(curved),
              child: child,
            ),
          ),
        );
      },
      pageBuilder: (ctx, _, anim) => StatefulBuilder(
        builder: (ctx, setDialogState) => Center(
          child: Material(
            color: Colors.transparent,
            child: Container(
              width: double.infinity,
              constraints: const BoxConstraints(maxWidth: 380),
              margin: const EdgeInsets.symmetric(horizontal: 24),
              padding: const EdgeInsets.fromLTRB(24, 28, 24, 24),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(28),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.24),
                    blurRadius: 40,
                    offset: const Offset(0, 16),
                  ),
                ],
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 58,
                    height: 58,
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFF10B981), Color(0xFF059669)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      shape: BoxShape.circle,
                      boxShadow: [
                        BoxShadow(
                          color: const Color(
                            0xFF059669,
                          ).withValues(alpha: 0.35),
                          blurRadius: 18,
                          offset: const Offset(0, 6),
                        ),
                      ],
                    ),
                    child: const Icon(
                      Icons.track_changes_rounded,
                      color: Colors.white,
                      size: 28,
                    ),
                  ),
                  const SizedBox(height: 16),
                  const Text(
                    'Daily Focus Target',
                    style: TextStyle(
                      fontSize: 18.5,
                      fontWeight: FontWeight.w800,
                      color: Color(0xFF1E293B),
                      letterSpacing: -0.3,
                    ),
                  ),
                  const SizedBox(height: 4),
                  const Text(
                    'Set how many Pomodoro sessions you want each day',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 12.5, color: Color(0xFF64748B)),
                  ),
                  const SizedBox(height: 18),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      vertical: 14,
                      horizontal: 20,
                    ),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF8FAFC),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(
                        color: const Color(0xFFF1F5F9),
                        width: 1.5,
                      ),
                    ),
                    child: Column(
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          crossAxisAlignment: CrossAxisAlignment.baseline,
                          textBaseline: TextBaseline.alphabetic,
                          children: [
                            AnimatedSwitcher(
                              duration: const Duration(milliseconds: 220),
                              transitionBuilder: (child, anim) =>
                                  FadeTransition(
                                    opacity: anim,
                                    child: ScaleTransition(
                                      scale: Tween<double>(
                                        begin: 0.82,
                                        end: 1.0,
                                      ).animate(anim),
                                      child: child,
                                    ),
                                  ),
                              child: Text(
                                '$goal',
                                key: ValueKey<int>(goal),
                                style: const TextStyle(
                                  fontSize: 46,
                                  fontWeight: FontWeight.w900,
                                  color: Color(0xFF10B981),
                                  letterSpacing: -1.0,
                                  height: 1.0,
                                ),
                              ),
                            ),
                            const SizedBox(width: 8),
                            const Text(
                              'BLOCKS / DAY',
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w800,
                                letterSpacing: 0.8,
                                color: Color(0xFF64748B),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Text(
                          '≈ ${(goal * _session.workMinutes / 60).toStringAsFixed(1)} hours of deep focus',
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w700,
                            color: Color(0xFF10B981),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  SliderTheme(
                    data: SliderTheme.of(ctx).copyWith(
                      trackHeight: 6,
                      activeTrackColor: const Color(0xFF10B981),
                      inactiveTrackColor: const Color(0xFFE2E8F0),
                      thumbColor: const Color(0xFF10B981),
                      thumbShape: const RoundSliderThumbShape(
                        enabledThumbRadius: 10,
                      ),
                      overlayColor: const Color(
                        0xFF10B981,
                      ).withValues(alpha: 0.18),
                    ),
                    child: Slider(
                      value: goal.toDouble(),
                      min: 1,
                      max: 12,
                      divisions: 11,
                      onChanged: (v) {
                        final rounded = v.round();
                        if (rounded != goal) {
                          HapticFeedback.selectionClick();
                          setDialogState(() => goal = rounded);
                        }
                      },
                    ),
                  ),
                  const SizedBox(height: 22),
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton(
                          style: OutlinedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(vertical: 14),
                            side: const BorderSide(color: Color(0xFFE2E8F0)),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(16),
                            ),
                            backgroundColor: const Color(0xFFF8FAFC),
                          ),
                          onPressed: () => Navigator.pop(ctx),
                          child: const Text(
                            'Cancel',
                            style: TextStyle(
                              fontSize: 14.5,
                              fontWeight: FontWeight.w600,
                              color: Color(0xFF64748B),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Container(
                          height: 48,
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(
                              colors: [Color(0xFF10B981), Color(0xFF059669)],
                            ),
                            borderRadius: BorderRadius.circular(16),
                            boxShadow: [
                              BoxShadow(
                                color: const Color(
                                  0xFF059669,
                                ).withValues(alpha: 0.35),
                                blurRadius: 10,
                                offset: const Offset(0, 4),
                              ),
                            ],
                          ),
                          child: ElevatedButton(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.transparent,
                              shadowColor: Colors.transparent,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(16),
                              ),
                            ),
                            onPressed: () {
                              HapticFeedback.lightImpact();
                              _session.setDailyGoal(goal);
                              Navigator.pop(ctx);
                            },
                            child: const Text(
                              'Save Target',
                              style: TextStyle(
                                fontSize: 14.5,
                                fontWeight: FontWeight.w800,
                                color: Colors.white,
                              ),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  // Daily Goals & Productivity Stats Card
  Widget _buildDailyGoalsCard() {
    final dailyGoal = _session.dailyGoalSessions;
    final completed = _session.completedSessions;
    final progress = _session.dailyProgress;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFFF0F2F8), width: 1.2),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(9),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF0FDF4),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Icon(
                        Icons.emoji_events_rounded,
                        color: Color(0xFF10B981),
                        size: 20,
                      ),
                    ),
                    const SizedBox(width: 10),
                    const Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'DAILY FOCUS TARGET',
                            style: TextStyle(
                              fontSize: 10.5,
                              fontWeight: FontWeight.w700,
                              letterSpacing: 0.6,
                              color: Color(0xFF8E95A5),
                            ),
                          ),
                          Text(
                            'Consistency builds mastery',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              fontSize: 12.5,
                              color: Color(0xFF64748B),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              InkWell(
                onTap: _showDailyGoalDialog,
                borderRadius: BorderRadius.circular(8),
                child: Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: brandPurple.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    '$completed / $dailyGoal Blocks',
                    style: const TextStyle(
                      fontSize: 12.5,
                      fontWeight: FontWeight.w800,
                      color: brandPurple,
                    ),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),

          // Visual Pomodoro Tomato / Dot Indicators
          Wrap(
            spacing: 6,
            runSpacing: 6,
            children: List.generate(dailyGoal, (index) {
              final done = index < completed;
              final boxWidth = dailyGoal <= 4
                  ? 64.0
                  : (dailyGoal <= 6 ? 44.0 : 38.0);
              return Container(
                width: boxWidth,
                height: 38,
                decoration: BoxDecoration(
                  color: done ? brandPurple : const Color(0xFFF1F5F9),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(
                    color: done ? brandPurple : const Color(0xFFE2E8F0),
                  ),
                ),
                child: Center(
                  child: Icon(
                    done ? Icons.check_rounded : Icons.timer_outlined,
                    size: 18,
                    color: done ? Colors.white : const Color(0xFF94A3B8),
                  ),
                ),
              );
            }),
          ),
          const SizedBox(height: 14),

          // Linear Progress Bar
          ClipRRect(
            borderRadius: BorderRadius.circular(6),
            child: LinearProgressIndicator(
              value: progress,
              minHeight: 6,
              backgroundColor: const Color(0xFFF1F5F9),
              valueColor: const AlwaysStoppedAnimation(brandPurple),
            ),
          ),
          const SizedBox(height: 16),

          // Stats Metric Row
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
                  'Focus time today',
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

  // Motivational Quote Card
  Widget _buildMotivationalQuoteCard() {
    final current = _quotes[_quoteIndex % _quotes.length];
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(Icons.format_quote_rounded, color: brandPurple, size: 24),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '"${current['quote']}"',
                  style: const TextStyle(
                    fontSize: 13,
                    fontStyle: FontStyle.italic,
                    fontWeight: FontWeight.w500,
                    color: Color(0xFF334155),
                    height: 1.35,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  '— ${current['author']}',
                  style: const TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    color: Color(0xFF8E95A5),
                  ),
                ),
              ],
            ),
          ),
          IconButton(
            icon: const Icon(Icons.refresh_rounded, size: 18),
            tooltip: 'Next Quote',
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(),
            style: IconButton.styleFrom(
              foregroundColor: const Color(0xFF64748B),
            ),
            onPressed: () {
              HapticFeedback.selectionClick();
              setState(() {
                _quoteIndex = (_quoteIndex + 1) % _quotes.length;
              });
            },
          ),
        ],
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
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFF1F5F9)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, color: color, size: 18),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  value,
                  style: const TextStyle(
                    fontSize: 17,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF1B1D28),
                  ),
                ),
                Text(
                  label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 10.5,
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

  // Zen Mode (Minimalist Fullscreen Focus)
  Widget _buildZenMode() {
    final displaySeconds = _isFlowMode ? _flowSeconds : _remainingSeconds;
    final minutes = (displaySeconds ~/ 60).toString().padLeft(2, '0');
    final seconds = (displaySeconds % 60).toString().padLeft(2, '0');
    final screenHeight = MediaQuery.of(context).size.height;

    return Container(
      constraints: BoxConstraints(
        minHeight: math.max(480, screenHeight - 140),
      ),
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A),
        borderRadius: BorderRadius.circular(24),
      ),
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 4,
                ),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Row(
                  children: [
                    Icon(Icons.spa_rounded, color: Colors.white70, size: 14),
                    SizedBox(width: 6),
                    Text(
                      'ZEN FOCUS MODE',
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w700,
                        letterSpacing: 0.8,
                        color: Colors.white70,
                      ),
                    ),
                  ],
                ),
              ),
              IconButton(
                icon: const Icon(
                  Icons.fullscreen_exit_rounded,
                  color: Colors.white70,
                ),
                tooltip: 'Exit Zen Mode',
                onPressed: () => setState(() => _isZenMode = false),
              ),
            ],
          ),
          const SizedBox(height: 36),
          Column(
            children: [
              AnimatedBuilder(
                animation: _pulseController,
                builder: (context, _) => Text(
                  '$minutes:$seconds',
                  style: TextStyle(
                    fontSize: 72,
                    fontWeight: FontWeight.w900,
                    letterSpacing: -2,
                    color: Colors.white,
                    fontFeatures: const [FontFeature.tabularFigures()],
                    shadows: _isRunning
                        ? [
                            Shadow(
                              color: _currentModeColor.withValues(
                                alpha: _pulseController.value * 0.7,
                              ),
                              blurRadius: 30,
                            ),
                          ]
                        : null,
                  ),
                ),
              ),
              const SizedBox(height: 8),
              Text(
                _modeTitle.toUpperCase(),
                style: const TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w700,
                  letterSpacing: 2,
                  color: Colors.white60,
                ),
              ),
            ],
          ),
          const SizedBox(height: 48),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              IconButton(
                icon: const Icon(Icons.refresh_rounded, color: Colors.white60),
                onPressed: _resetTimer,
              ),
              const SizedBox(width: 20),
              FilledButton.icon(
                style: FilledButton.styleFrom(
                  backgroundColor: _currentModeColor,
                  padding: const EdgeInsets.symmetric(
                    horizontal: 36,
                    vertical: 18,
                  ),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(20),
                  ),
                ),
                onPressed: _isRunning ? _pauseTimer : _startTimer,
                icon: Icon(
                  _isRunning ? Icons.pause_rounded : Icons.play_arrow_rounded,
                ),
                label: Text(
                  _isRunning ? 'Pause' : 'Resume',
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
              const SizedBox(width: 20),
              IconButton(
                icon: const Icon(
                  Icons.skip_next_rounded,
                  color: Colors.white60,
                ),
                onPressed: () => _session.finishSessionEarly(),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
