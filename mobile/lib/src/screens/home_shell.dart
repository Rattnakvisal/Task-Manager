import 'dart:async';
import 'dart:math' as math;

import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:intl/intl.dart';

import '../api.dart';
import '../app.dart';
import '../app_state.dart';
import '../models.dart';
import '../widgets/task_card.dart';
import '../widgets/motion.dart';
import '../widgets/animated_taskbar.dart';
import '../widgets/task_clipboard_graphic.dart';
import '../widgets/sweet_alert.dart';
import '../widgets/streak_widgets.dart';
import '../widgets/hero_slideshow.dart';
import '../widgets/task_status_widgets.dart';
import '../state/streak_manager.dart';

import 'focus_view.dart';
import 'task_editor.dart';
import 'onboarding_screen.dart';

class HomeShell extends StatefulWidget {
  const HomeShell({super.key, required this.state});
  final AppState state;
  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> with WidgetsBindingObserver {
  int _tab = 0;
  String _query = '', _status = 'all', _priority = 'all';
  String? _error;
  bool _signingOut = false;
  bool _requestedInitialTasks = false;
  final _search = TextEditingController();
  final FocusNode _searchFocusNode = FocusNode();
  final Set<String> _collapsedSections = {};
  Timer? _searchDebounce;
  DateTime? _lastRefresh;
  DateTime _selectedDay = dateOnly(DateTime.now());
  DateTime _month = DateTime(DateTime.now().year, DateTime.now().month);
  AppState get state => widget.state;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _searchDebounce?.cancel();
    _search.dispose();
    _searchFocusNode.dispose();
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState lifecycle) {
    if (lifecycle == AppLifecycleState.resumed &&
        state.user != null &&
        (_lastRefresh == null ||
            DateTime.now().difference(_lastRefresh!) >
                const Duration(seconds: 30))) {
      _refresh();
    }
  }

  void _selectTab(int value) {
    if (_tab == value) return;
    FocusManager.instance.primaryFocus?.unfocus();
    setState(() => _tab = value);
  }

  void _openCalendarForDate(DateTime? date) {
    FocusManager.instance.primaryFocus?.unfocus();
    HapticFeedback.selectionClick();
    setState(() {
      final targetDate =
          date != null ? dateOnly(date) : dateOnly(DateTime.now());
      _selectedDay = targetDate;
      _month = DateTime(targetDate.year, targetDate.month, 1);
      _tab = 2; // Jump directly to Calendar tab (index 2)
    });
  }

  void _searchTasks(String value) {
    _searchDebounce?.cancel();
    _searchDebounce = Timer(const Duration(milliseconds: 180), () {
      if (mounted) setState(() => _query = value.trim().toLowerCase());
    });
  }

  Future<void> _refresh() async {
    if (!mounted) return;
    setState(() => _error = null);
    try {
      if (state.user == null) {
        await state.initialize();
      } else {
        await Future.wait([
          state.refresh(),
          state.streak.checkIn(userId: state.user?.id),
        ]);
      }
      _lastRefresh = DateTime.now();
    } catch (e) {
      if (mounted) {
        setState(
          () => _error = e is ApiException
              ? e.message
              : 'Could not load tasks. Please try again.',
        );
      }
    }
  }

  Future<void> _toggle(Task task) async {
    if (state.isTaskBusy(task.id)) return;

    final willComplete = !task.completed;
    final confirmed = await SweetAlert.confirm(
      context,
      title: willComplete ? 'Mark as Done?' : 'Reopen Task?',
      message: willComplete
          ? 'Do you want to mark "${task.title}" as completed?'
          : 'Do you want to reopen "${task.title}" and set it to In Progress?',
      confirmText: willComplete ? 'Yes, Done!' : 'Yes, Reopen',
      cancelText: 'Cancel',
      confirmColor: willComplete ? const Color(0xFF10B981) : const Color(0xFF6366F1),
      icon: willComplete
          ? Icons.check_circle_outline_rounded
          : Icons.restart_alt_rounded,
    );

    if (!confirmed || !mounted) return;

    try {
      await state.toggleTask(task);
      if (mounted) {
        if (!MediaQuery.disableAnimationsOf(context)) {
          HapticFeedback.selectionClick();
        }
        if (willComplete) {
          final count = state.streak.streakCount;
          SweetAlert.success(
            context,
            title: '🎉 Great Job!',
            message: state.streak.streakIncreasedToday
                ? 'Task completed! Daily streak extended to $count ${count == 1 ? "Day" : "Days"}! 🔥'
                : 'Task "${task.title}" marked as completed successfully!',
            confirmText: 'Awesome!',
            autoCloseDuration: const Duration(milliseconds: 2400),
          );
        } else {
          SweetAlert.success(
            context,
            title: 'Task Reopened',
            message: 'Task "${task.title}" is now back in progress.',
            confirmText: 'OK',
            autoCloseDuration: const Duration(milliseconds: 1800),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        _message(e is ApiException ? e.message : 'Could not update task.');
      }
    }
  }

  Future<void> _deleteTask(Task task) async {
    if (state.isTaskBusy(task.id)) return;
    final confirmed = await SweetAlert.danger(
      context,
      title: 'Delete Task?',
      message: 'Are you sure you want to delete "${task.title}"?',
      confirmText: 'Yes, Delete',
      cancelText: 'Cancel',
    );
    if (!confirmed || !mounted) return;

    try {
      await state.deleteTask(task.id);
      if (mounted) {
        SweetAlert.success(
          context,
          title: 'Task Deleted',
          message: 'The task has been permanently deleted.',
          confirmText: 'OK',
          autoCloseDuration: const Duration(milliseconds: 1800),
        );
      }
    } catch (e) {
      if (mounted) {
        _message(e is ApiException ? e.message : 'Could not delete task.');
      }
    }
  }

  void _message(String value) =>
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(value)));
  void _edit([Task? task]) {
    if (task != null && state.isTaskBusy(task.id)) return;
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => TaskEditor(state: state, task: task),
      ),
    );
  }

  List<Task> _sorted(Iterable<Task> tasks) => tasks.toList()
    ..sort((a, b) {
      if (a.isPinned != b.isPinned) return a.isPinned ? -1 : 1;
      if (a.completed != b.completed) return a.completed ? 1 : -1;
      return (a.dueDate ?? DateTime(9999)).compareTo(
        b.dueDate ?? DateTime(9999),
      );
    });

  Widget _cards(
    List<Task> tasks, {
    String emptyTitle = 'Nothing here yet',
    String emptyMessage = 'Add a task and take the next small step.',
  }) {
    if (tasks.isEmpty) {
      return SliverToBoxAdapter(
        child: EmptyTasks(
          title: emptyTitle,
          message: emptyMessage,
          onAdd: () => _edit(),
        ),
      );
    }
    final indices = {for (var i = 0; i < tasks.length; i++) tasks[i].id: i};
    return SliverList.builder(
      itemCount: tasks.length,
      findChildIndexCallback: (key) {
        if (key is! ValueKey<int>) return null;
        return indices[key.value];
      },
      itemBuilder: (context, index) {
        final task = tasks[index];
        return TaskEntrance(
          key: ValueKey(task.id),
          index: index,
          child: Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: TaskCard(
              task: task,
              busy: state.isTaskBusy(task.id),
              onTap: () => _edit(task),
              onToggle: () => _toggle(task),
              onDelete: () => _deleteTask(task),
              onToggleSubtask: (index) => state.toggleSubtask(task, index),
              onStatusChange: (newStatus) =>
                  state.updateTaskStatus(task, newStatus),
              onAddSubtask: (title) => state.addSubtask(task, title),
              onToggleSubPoint: (stepIdx, ptIdx) =>
                  state.toggleSubPoint(task, stepIdx, ptIdx),
            ),
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) => ListenableBuilder(
    listenable: state,
    builder: (context, _) {
      if (state.user == null && !state.starting && state.startupError == null) {
        return const SizedBox.shrink();
      }
      if (state.starting) _requestedInitialTasks = false;
      if (state.user != null && !state.starting &&
          state.startupError == null && !_requestedInitialTasks) {
        _requestedInitialTasks = true;
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (mounted) _refresh();
        });
      }
      final sessionReady = state.user != null && !state.starting && state.startupError == null;
      final visibleError = state.startupError ?? _error;
      return Scaffold(
        body: Stack(
          children: [
            // Soft atmospheric ambient glow at the top right
            Positioned(
              top: -60,
              right: -50,
              child: IgnorePointer(
                child: Container(
                  width: 300,
                  height: 300,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: RadialGradient(
                      colors: [
                        const Color(0xFF7047F5).withValues(alpha: 0.14),
                        const Color(0xFF5B4DFF).withValues(alpha: 0.06),
                        Colors.transparent,
                      ],
                      stops: const [0.0, 0.55, 1.0],
                    ),
                  ),
                ),
              ),
            ),
            SafeArea(
              child: Column(
                children: [
                  MotionSwitcher(
                    child: visibleError == null || _tab == 3
                        ? const SizedBox.shrink(key: ValueKey('no-error'))
                        : MaterialBanner(
                            key: ValueKey(visibleError),
                            content: Text(visibleError),
                            leading: const Icon(Icons.cloud_off_outlined),
                            actions: [
                              TextButton(
                                onPressed: state.startupError != null ? state.initialize : _refresh,
                                child: const Text('Retry'),
                              ),
                              if (state.startupError != null)
                                TextButton(onPressed: state.clearSession, child: const Text('Sign in')),
                            ],
                          ),
                  ),
                  Expanded(
                    child: IndexedStack(
                      index: _tab == 3 ? 1 : 0,
                      children: [
                        RefreshIndicator(
                          color: brandPurple,
                          backgroundColor: Colors.white,
                          strokeWidth: 2.5,
                          onRefresh: sessionReady ? _refresh : state.initialize,
                          child: MotionSwitcher(
                            child: CustomScrollView(
                              key: PageStorageKey(
                                'tab-$_tab-${state.hasLoadedTasks || state.tasks.isNotEmpty || _tab == 4}',
                              ),
                              physics: const AlwaysScrollableScrollPhysics(),
                              slivers: [
                                SliverPadding(
                                  padding: EdgeInsets.fromLTRB(
                                    (MediaQuery.sizeOf(context).width - 640).clamp(
                                          32,
                                          double.infinity,
                                        ) /
                                        2,
                                    12,
                                    (MediaQuery.sizeOf(context).width - 640).clamp(
                                          32,
                                          double.infinity,
                                        ) /
                                        2,
                                    100,
                                  ),
                                  sliver: SliverMainAxisGroup(
                                    slivers:
                                        !sessionReady && _tab != 0
                                            ? _homeLoading()
                                            : !state.hasLoadedTasks &&
                                                    state.tasks.isEmpty &&
                                                    _tab != 4 &&
                                                    _tab != 3 &&
                                                    _tab != 0
                                                ? [
                                                    SliverToBoxAdapter(
                                                      child: state.busy
                                                          ? const TaskLoadingPlaceholder()
                                                          : const EmptyTasks(
                                                              title: 'Let’s reconnect',
                                                              message:
                                                                  'Pull down or tap Retry to load your tasks.',
                                                            ),
                                                    ),
                                                  ]
                                                : switch (_tab) {
                                                    0 => _home(),
                                                    1 => _tasks(),
                                                    2 => _calendar(),
                                                    3 => const <Widget>[],
                                                    _ => [
                                                        SliverToBoxAdapter(
                                                          child: _profile(),
                                                        ),
                                                      ],
                                                  },
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      SingleChildScrollView(
                        key: const PageStorageKey('focus-scroll'),
                        padding: const EdgeInsets.only(top: 12, bottom: 24),
                        child: Center(
                          child: ConstrainedBox(
                            constraints: const BoxConstraints(maxWidth: 640),
                            child: FocusView(state: state),
                          ),
                        ),
                      ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            // Refresh status floats above content; it never reserves header space.
            if (state.busy && state.hasLoadedTasks && _tab != 3)
              Positioned(
                bottom: 16,
                left: 16,
                right: 16,
                child: IgnorePointer(
                  child: TaskEntrance(
                    child: Center(
                      child: Material(
                        elevation: 4,
                        shadowColor: const Color(0x185B4DFF),
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(20),
                        child: const Padding(
                          padding: EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              CupertinoActivityIndicator(radius: 7, color: brandPurple),
                              SizedBox(width: 8),
                              Text('Syncing workspace…', style: TextStyle(
                                fontSize: 12, fontWeight: FontWeight.w600, color: brandPurple,
                              )),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
              ),
          ],
        ),
        floatingActionButton: ExcludeSemantics(
          excluding: !sessionReady || _tab == 4 || _tab == 3,
          child: IgnorePointer(
            ignoring: !sessionReady || _tab == 4 || _tab == 3,
            child: AnimatedScale(
              scale: !sessionReady || _tab == 4 || _tab == 3 ? 0 : 1,
              duration: motionDuration(context),
              curve: Curves.easeOutCubic,
              child: PulsingGlowFAB(
                child: BouncingInteractive(
                  onTap: () => _edit(),
                  scaleFactor: 0.92,
                  child: Container(
                    width: 58,
                    height: 58,
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(19),
                      gradient: const LinearGradient(
                        colors: [
                          Color(0xFF6A57FF),
                          Color(0xFF5B4DFF),
                        ],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      boxShadow: [
                        BoxShadow(
                          color: brandPurple.withValues(alpha: 0.45),
                          blurRadius: 18,
                          offset: const Offset(0, 6),
                        ),
                      ],
                    ),
                    child: const Center(
                      child: Icon(Icons.add_rounded, size: 34, color: Colors.white),
                    ),
                  ),
                ),
              ),
            ),
          ),
        ),
        bottomNavigationBar: AnimatedTaskbar(
          selectedIndex: _tab,
          onItemSelected: (value) { if (sessionReady) _selectTab(value); },
          items: [
            const TaskbarItem(
              icon: Icons.home_outlined,
              selectedIcon: Icons.home_rounded,
              label: 'Home',
            ),
            TaskbarItem(
              icon: Icons.checklist_outlined,
              selectedIcon: Icons.checklist_rounded,
              label: 'Tasks',
              badge: state.tasks.any(
                (t) => !t.completed && (t.priority == 'high' || t.overdue),
              )
                  ? '${state.tasks.where((t) => !t.completed && (t.priority == 'high' || t.overdue)).length}'
                  : null,
            ),
            const TaskbarItem(
              icon: Icons.calendar_month_outlined,
              selectedIcon: Icons.calendar_month_rounded,
              label: 'Calendar',
            ),
            const TaskbarItem(
              icon: Icons.center_focus_weak_rounded,
              selectedIcon: Icons.center_focus_strong_rounded,
              label: 'Focus',
            ),
            const TaskbarItem(
              icon: Icons.person_outline_rounded,
              selectedIcon: Icons.person_rounded,
              label: 'Profile',
            ),
          ],
        ),
      );
    },
  );

  List<Widget> _homeLoading() => [
    SliverToBoxAdapter(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const SizedBox(height: 6),
          _buildCleanHeader(
            eyebrow: 'WORKSPACE',
            title: 'Home',
            subtitle: state.startupError != null || _error != null
                ? 'Your workspace will appear when reconnected.'
                : 'Loading your tasks and schedule…',
          ),
          const SizedBox(height: 20),
          if (state.starting || state.busy)
            const TaskLoadingPlaceholder()
          else
            const EmptyTasks(
              title: 'Let’s reconnect',
              message: 'Pull down or tap Retry to load your workspace.',
            ),
        ],
      ),
    ),
  ];

  Widget _buildCleanHeader({
    required String eyebrow,
    required String title,
    required String subtitle,
    Widget? trailing,
    Color eyebrowColor = brandPurple,
    String? emoji,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: eyebrowColor.withValues(alpha: 0.08),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Container(
                          width: 4.5,
                          height: 4.5,
                          decoration: BoxDecoration(
                            color: eyebrowColor,
                            shape: BoxShape.circle,
                          ),
                        ),
                        const SizedBox(width: 5),
                        Text(
                          eyebrow,
                          style: TextStyle(
                            fontSize: 9.5,
                            fontWeight: FontWeight.w700,
                            letterSpacing: 0.8,
                            color: eyebrowColor,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 6),
                  Row(
                    children: [
                      Flexible(
                        child: Text(
                          title,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 26,
                            fontWeight: FontWeight.w800,
                            color: Color(0xFF1B1D28),
                            letterSpacing: -0.6,
                            height: 1.15,
                          ),
                        ),
                      ),
                      if (emoji != null) ...[
                        const SizedBox(width: 4),
                        Text(
                          emoji,
                          style: const TextStyle(fontSize: 22),
                        ),
                      ],
                    ],
                  ),
                  const SizedBox(height: 3),
                  Text(
                    subtitle,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w400,
                      color: Color(0xFF8E95A5),
                      height: 1.3,
                    ),
                  ),
                ],
              ),
            ),
            if (trailing != null) ...[
              const SizedBox(width: 12),
              trailing,
            ],
          ],
        ),
      ],
    );
  }

  List<Widget> _home() {
    final user = state.user;
    final total = state.tasks.length;
    final completed = state.tasks.where((t) => t.completed).length;
    final inProgressCount =
        state.tasks.where((t) => t.status == 'in_progress').length;
    final pendingCount = state.tasks.where((t) => t.status == 'pending').length;
    final progressRatio = total > 0 ? (completed / total).clamp(0.0, 1.0) : 0.0;

    return [
      SliverToBoxAdapter(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 6),
            // 1. Header: Avatar + "Hello, Chun 👋" + "Plan · Organize · Get Things Done" + Bell
            _buildHomeMockupHeader(user),
            const SizedBox(height: 18),

            // 2. Hero Slideshow / Carousel (Interactive, Auto-playing & Swipeable)
            HomeHeroSlideshow(
              total: total,
              completed: completed,
              streak: state.streak,
              onOpenTasks: () => _selectTab(1),
              onOpenFocus: () => _selectTab(3),
              onOpenStreak: () =>
                  showStreakCelebrationSheet(context, state.streak),
            ),
            const SizedBox(height: 18),

            // 3. "Today's Plan" Timeline Card with connecting line & status badges
            _buildTodaysPlanCard(state.tasks),
            const SizedBox(height: 18),

            // 4. Middle Row: "My Progress" (Donut Chart) + "Streak" (Live Animated Daily Streak)
            _buildMockupProgressAndStreakRow(
              total: total,
              completed: completed,
              inProgress: inProgressCount,
              pending: pendingCount,
              progressRatio: progressRatio,
              streak: state.streak,
            ),
            const SizedBox(height: 18),

            // 6. "Upcoming Tasks" Card with bullet dots & category tags
            _buildUpcomingTasksCard(state.tasks),
            const SizedBox(height: 28),
          ],
        ),
      ),
    ];
  }

  // 1. Header: Avatar, Greeting, Subtitle, Notification Bell
  Widget _buildHomeMockupHeader(AppUser? user) {
    final displayName = user?.name.trim().isNotEmpty == true
        ? user!.name.split(' ').first
        : 'Chun';
    final initial = user?.name.trim().isNotEmpty == true
        ? user!.name.trim()[0].toUpperCase()
        : 'C';

    return Row(
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        // User Profile Avatar with soft ring
        Container(
          width: 48,
          height: 48,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            gradient: const LinearGradient(
              colors: [Color(0xFF6366F1), Color(0xFF4F46E5)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            boxShadow: [
              BoxShadow(
                color: const Color(0xFF6366F1).withValues(alpha: 0.25),
                blurRadius: 10,
                offset: const Offset(0, 4),
              ),
            ],
            border: Border.all(color: Colors.white, width: 2),
          ),
          child: Center(
            child: Text(
              initial,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 20,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
        ),
        const SizedBox(width: 14),

        // User Greeting & Subtitle
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Flexible(
                    child: Text(
                      'Hello, $displayName',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 21,
                        fontWeight: FontWeight.w800,
                        color: Color(0xFF0F172A),
                        letterSpacing: -0.4,
                      ),
                    ),
                  ),
                  const SizedBox(width: 4),
                  const Text('👋', style: TextStyle(fontSize: 19)),
                ],
              ),
              const SizedBox(height: 2),
              const Text(
                'Plan · Organize · Get Things Done',
                style: TextStyle(
                  fontSize: 12.5,
                  fontWeight: FontWeight.w500,
                  color: Color(0xFF64748B),
                ),
              ),
            ],
          ),
        ),

        // Notification Bell Button
        BouncingInteractive(
          onTap: _showReminders,
          scaleFactor: 0.92,
          child: Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: Colors.white,
              shape: BoxShape.circle,
              border: Border.all(
                color: const Color(0xFFE2E8F0),
                width: 1,
              ),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.04),
                  blurRadius: 12,
                  offset: const Offset(0, 3),
                ),
              ],
            ),
            child: Stack(
              alignment: Alignment.center,
              children: [
                const Icon(
                  Icons.notifications_outlined,
                  color: Color(0xFF1E293B),
                  size: 22,
                ),
                if (_reminders.isNotEmpty)
                  Positioned(
                    top: 10,
                    right: 11,
                    child: Container(
                      width: 8,
                      height: 8,
                      decoration: BoxDecoration(
                        color: const Color(0xFFEF4444),
                        shape: BoxShape.circle,
                        border: Border.all(color: Colors.white, width: 1.5),
                      ),
                    ),
                  ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  // 3. "Today's Plan" Timeline Card
  Widget _buildTodaysPlanCard(List<Task> tasks) {
    final today = dateOnly(DateTime.now());
    final todayTasks = tasks.where((t) => t.dueDate == today).toList();
    final planTasks =
        todayTasks.isNotEmpty ? todayTasks.take(3).toList() : tasks.take(3).toList();

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: const Color(0xFFF1F5F9), width: 1.2),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 14,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Row(
            children: [
              Container(
                width: 38,
                height: 38,
                decoration: BoxDecoration(
                  color: const Color(0xFFEEF2FF),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.calendar_month_rounded,
                  color: Color(0xFF4F46E5),
                  size: 20,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      "Today's Plan",
                      style: TextStyle(
                        fontSize: 16.5,
                        fontWeight: FontWeight.w800,
                        color: Color(0xFF0F172A),
                        letterSpacing: -0.3,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      '${planTasks.length} tasks scheduled',
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w500,
                        color: Color(0xFF64748B),
                      ),
                    ),
                  ],
                ),
              ),
              TextButton(
                onPressed: () => _selectTab(1),
                style: TextButton.styleFrom(
                  foregroundColor: const Color(0xFF4F46E5),
                  padding: EdgeInsets.zero,
                  minimumSize: Size.zero,
                  tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                ),
                child: const Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      'View All',
                      style: TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 13,
                      ),
                    ),
                    SizedBox(width: 4),
                    Icon(Icons.arrow_forward_rounded, size: 14),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 18),

          // Timeline items
          if (state.busy && !state.hasLoadedTasks)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 20),
              child: Center(
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: const [
                    CupertinoActivityIndicator(radius: 8, color: Color(0xFF4F46E5)),
                    SizedBox(width: 10),
                    Text(
                      'Loading today\'s schedule…',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF64748B),
                      ),
                    ),
                  ],
                ),
              ),
            )
          else if (planTasks.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 12),
              child: Center(
                child: Column(
                  children: [
                    const Text(
                      'No tasks scheduled for today',
                      style: TextStyle(
                        fontSize: 13.5,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF64748B),
                      ),
                    ),
                    const SizedBox(height: 8),
                    TextButton.icon(
                      onPressed: () => _edit(),
                      icon: const Icon(Icons.add_rounded, size: 16),
                      label: const Text('Add Task to Today'),
                      style: TextButton.styleFrom(foregroundColor: brandPurple),
                    ),
                  ],
                ),
              ),
            )
          else
            ...List.generate(planTasks.length, (index) {
              final task = planTasks[index];
              final isLast = index == planTasks.length - 1;
              return _buildTimelineItem(task, isLast, index);
            }),
        ],
      ),
    );
  }

  Widget _buildTimelineItem(Task task, bool isLast, int index) {
    final isDone = task.completed;
    final isInProgress = task.status == 'in_progress';

    final timeStr = _formatTimelineTime(task.dueDate, index);

    return InkWell(
      onTap: () => _edit(task),
      borderRadius: BorderRadius.circular(12),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 4),
        child: IntrinsicHeight(
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Node + Connecting vertical line
              SizedBox(
                width: 24,
                child: Column(
                  children: [
                    GestureDetector(
                      onTap: () {
                        HapticFeedback.lightImpact();
                        _toggle(task);
                      },
                      child: isDone
                          ? Container(
                              width: 22,
                              height: 22,
                              decoration: const BoxDecoration(
                                color: Color(0xFF10B981),
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(
                                Icons.check_rounded,
                                color: Colors.white,
                                size: 15,
                              ),
                            )
                          : isInProgress
                              ? InProgressBreathingPulse(
                                  glowColor: const Color(0xFF7C3AED),
                                  child: Container(
                                    width: 22,
                                    height: 22,
                                    decoration: BoxDecoration(
                                      color: Colors.white,
                                      shape: BoxShape.circle,
                                      border: Border.all(
                                        color: const Color(0xFF7C3AED),
                                        width: 2.5,
                                      ),
                                    ),
                                    child: Center(
                                      child: Container(
                                        width: 9,
                                        height: 9,
                                        decoration: const BoxDecoration(
                                          color: Color(0xFF7C3AED),
                                          shape: BoxShape.circle,
                                        ),
                                      ),
                                    ),
                                  ),
                                )
                              : Container(
                                  width: 20,
                                  height: 20,
                                  decoration: BoxDecoration(
                                    color: Colors.white,
                                    shape: BoxShape.circle,
                                    border: Border.all(
                                      color: const Color(0xFFCBD5E1),
                                      width: 2,
                                    ),
                                  ),
                                ),
                    ),
                    if (!isLast)
                      Expanded(
                        child: Container(
                          width: 2,
                          margin: const EdgeInsets.symmetric(vertical: 4),
                          color: const Color(0xFFE2E8F0),
                        ),
                      ),
                  ],
                ),
              ),
              const SizedBox(width: 14),

              // Title & Time & Subtask checklist pill
              Expanded(
                child: Padding(
                  padding: EdgeInsets.only(bottom: isLast ? 0 : 18),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        task.title,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w700,
                          color: isDone
                              ? const Color(0xFF94A3B8)
                              : const Color(0xFF0F172A),
                          decoration: isDone ? TextDecoration.lineThrough : null,
                          decorationColor: const Color(0xFF94A3B8),
                        ),
                      ),
                      const SizedBox(height: 3),
                      Row(
                        children: [
                          Text(
                            timeStr,
                            style: const TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w500,
                              color: Color(0xFF64748B),
                            ),
                          ),
                          if (task.subtasks.isNotEmpty) ...[
                            const SizedBox(width: 8),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 6,
                                vertical: 2,
                              ),
                              decoration: BoxDecoration(
                                color: task.completedSubtasks ==
                                        task.subtasks.length
                                    ? const Color(0xFFECFDF5)
                                    : const Color(0xFFF1F5F9),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Icon(
                                    Icons.checklist_rounded,
                                    size: 11,
                                    color: task.completedSubtasks ==
                                            task.subtasks.length
                                        ? const Color(0xFF10B981)
                                        : const Color(0xFF64748B),
                                  ),
                                  const SizedBox(width: 3),
                                  Text(
                                    '${task.completedSubtasks}/${task.subtasks.length} steps',
                                    style: TextStyle(
                                      fontSize: 10.5,
                                      fontWeight: FontWeight.w700,
                                      color: task.completedSubtasks ==
                                              task.subtasks.length
                                          ? const Color(0xFF10B981)
                                          : const Color(0xFF64748B),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ],
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 8),

              // Interactive Animated Status Badge (Todo, In Progress, Done)
              InteractiveTaskStatusBadge(
                status: task.status,
                dense: true,
                onTap: () async {
                  final chosen = await showTaskStatusPickerSheet(
                    context,
                    currentStatus: task.status,
                  );
                  if (chosen != null && chosen != task.status) {
                    await state.updateTaskStatus(task, chosen);
                  }
                },
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _formatTimelineTime(DateTime? date, int index) {
    if (date != null) {
      final start = DateFormat('h:mm a').format(date);
      final end =
          DateFormat('h:mm a').format(date.add(const Duration(hours: 1)));
      return '$start – $end';
    }
    const defaultSlots = [
      '8:00 AM – 9:00 AM',
      '10:00 AM – 12:00 PM',
      '2:00 PM – 3:00 PM',
      '4:00 PM – 5:00 PM',
    ];
    return defaultSlots[index % defaultSlots.length];
  }

  // 4. Middle Row: "My Progress" (Donut Chart) + "Streak" (Live Animated Daily Streak)
  Widget _buildMockupProgressAndStreakRow({
    required int total,
    required int completed,
    required int inProgress,
    required int pending,
    required double progressRatio,
    required StreakManager streak,
  }) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Left: My Progress Card
        Expanded(
          flex: 6,
          child: Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(22),
              border: Border.all(color: const Color(0xFFF1F5F9), width: 1.2),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.025),
                  blurRadius: 10,
                  offset: const Offset(0, 3),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      width: 28,
                      height: 28,
                      decoration: BoxDecoration(
                        color: const Color(0xFFEEF2FF),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Icon(
                        Icons.bar_chart_rounded,
                        color: Color(0xFF6366F1),
                        size: 16,
                      ),
                    ),
                    const SizedBox(width: 8),
                    const Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'My Progress',
                            style: TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w800,
                              color: Color(0xFF0F172A),
                              letterSpacing: -0.2,
                            ),
                          ),
                          Text(
                            "This week's completion",
                            style: TextStyle(
                              fontSize: 10.5,
                              color: Color(0xFF64748B),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    // Donut circular progress ring
                    SizedBox(
                      width: 78,
                      height: 78,
                      child: Stack(
                        alignment: Alignment.center,
                        children: [
                          SizedBox(
                            width: 78,
                            height: 78,
                            child: CircularProgressIndicator(
                              value: progressRatio > 0 ? progressRatio : 0.05,
                              strokeWidth: 8,
                              strokeCap: StrokeCap.round,
                              backgroundColor: const Color(0xFFEEF2FF),
                              valueColor: const AlwaysStoppedAnimation<Color>(
                                Color(0xFF4F46E5),
                              ),
                            ),
                          ),
                          Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text(
                                '${(progressRatio * 100).toInt()}%',
                                style: const TextStyle(
                                  fontSize: 15,
                                  fontWeight: FontWeight.w800,
                                  color: Color(0xFF0F172A),
                                  letterSpacing: -0.4,
                                ),
                              ),
                              const Text(
                                'Completed',
                                style: TextStyle(
                                  fontSize: 8.5,
                                  fontWeight: FontWeight.w600,
                                  color: Color(0xFF64748B),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 14),
                    // Legend
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          _progressLegendItem(
                            icon: Icons.check_circle_rounded,
                            iconColor: const Color(0xFF10B981),
                            count: completed,
                            label: 'Done',
                          ),
                          const SizedBox(height: 6),
                          _progressLegendItem(
                            icon: Icons.radio_button_checked_rounded,
                            iconColor: const Color(0xFF7C3AED),
                            count: inProgress,
                            label: 'In Progress',
                          ),
                          const SizedBox(height: 6),
                          _progressLegendItem(
                            icon: Icons.radio_button_unchecked_rounded,
                            iconColor: const Color(0xFF94A3B8),
                            count: pending,
                            label: 'To Do',
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
        const SizedBox(width: 12),

        // Right: Animated Streak Card with Live Daily Flame & Celebration Sheet
        Expanded(
          flex: 4,
          child: AnimatedStreakCard(streak: streak),
        ),
      ],
    );
  }

  Widget _progressLegendItem({
    required IconData icon,
    required Color iconColor,
    required int count,
    required String label,
  }) {
    return Row(
      children: [
        Icon(icon, size: 14, color: iconColor),
        const SizedBox(width: 6),
        Text(
          '$count',
          style: const TextStyle(
            fontSize: 12.5,
            fontWeight: FontWeight.w800,
            color: Color(0xFF0F172A),
          ),
        ),
        const SizedBox(width: 4),
        Expanded(
          child: Text(
            label,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w500,
              color: Color(0xFF64748B),
            ),
          ),
        ),
      ],
    );
  }

  // 6. "Upcoming Tasks" Card with Direct Calendar Connection
  Widget _buildUpcomingTasksCard(List<Task> tasks) {
    final now = DateTime.now();
    final today = dateOnly(now);
    final tomorrow = today.add(const Duration(days: 1));

    // Sort strictly future upcoming tasks chronologically
    final futureTasks = tasks
        .where((t) =>
            !t.completed && t.dueDate != null && t.dueDate!.isAfter(today))
        .toList()
      ..sort((a, b) => a.dueDate!.compareTo(b.dueDate!));

    // Fallbacks if fewer than 3 future tasks: pending due today or unscheduled
    final remainingCapacity = 3 - futureTasks.length;
    final List<Task> upcoming = [...futureTasks];
    if (remainingCapacity > 0) {
      final additional = tasks
          .where((t) =>
              !t.completed &&
              !upcoming.any((u) => u.id == t.id) &&
              (t.dueDate == null || t.dueDate == today))
          .take(remainingCapacity)
          .toList();
      upcoming.addAll(additional);
    }

    final firstDate =
        futureTasks.isNotEmpty ? futureTasks.first.dueDate : tomorrow;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: const Color(0xFFF1F5F9), width: 1.2),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 14,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header with Direct Calendar Button
          Row(
            children: [
              Container(
                width: 38,
                height: 38,
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFFEEF2FF), Color(0xFFE0E7FF)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFF4F46E5).withValues(alpha: 0.12),
                      blurRadius: 8,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: const Icon(
                  Icons.event_note_rounded,
                  color: Color(0xFF4F46E5),
                  size: 20,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Upcoming Tasks',
                      style: TextStyle(
                        fontSize: 16.5,
                        fontWeight: FontWeight.w800,
                        color: Color(0xFF0F172A),
                        letterSpacing: -0.3,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      upcoming.isEmpty
                          ? 'No tasks scheduled'
                          : '${upcoming.length} upcoming in your calendar',
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w500,
                        color: Color(0xFF64748B),
                      ),
                    ),
                  ],
                ),
              ),
              // Direct Link to Calendar Page
              TextButton.icon(
                onPressed: () => _openCalendarForDate(firstDate),
                style: TextButton.styleFrom(
                  foregroundColor: const Color(0xFF4F46E5),
                  padding:
                      const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  backgroundColor: const Color(0xFFEEF2FF),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  minimumSize: Size.zero,
                  tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                ),
                icon: const Icon(Icons.calendar_month_rounded, size: 14),
                label: const Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      'Calendar',
                      style: TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 12.5,
                        letterSpacing: -0.1,
                      ),
                    ),
                    SizedBox(width: 3),
                    Icon(Icons.arrow_forward_rounded, size: 13),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),

          // Items
          if (state.busy && !state.hasLoadedTasks)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 16),
              child: Center(
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: const [
                    CupertinoActivityIndicator(
                        radius: 8, color: Color(0xFF4F46E5)),
                    SizedBox(width: 10),
                    Text(
                      'Loading upcoming tasks…',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF64748B),
                      ),
                    ),
                  ],
                ),
              ),
            )
          else if (upcoming.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 12),
              child: Center(
                child: Column(
                  children: [
                    Container(
                      width: 48,
                      height: 48,
                      decoration: BoxDecoration(
                        color: const Color(0xFFF1F5F9),
                        borderRadius: BorderRadius.circular(14),
                      ),
                      child: const Icon(
                        Icons.event_available_rounded,
                        color: Color(0xFF64748B),
                        size: 24,
                      ),
                    ),
                    const SizedBox(height: 10),
                    const Text(
                      'No upcoming tasks in your schedule',
                      style: TextStyle(
                        fontSize: 13.5,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFF1E293B),
                      ),
                    ),
                    const SizedBox(height: 4),
                    const Text(
                      'Schedule a task with a future deadline to see it here.',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 12,
                        color: Color(0xFF64748B),
                      ),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        FilledButton.icon(
                          onPressed: () => _edit(),
                          icon: const Icon(Icons.add_rounded, size: 16),
                          label: const Text('Add Task',
                              style: TextStyle(
                                  fontSize: 12.5, fontWeight: FontWeight.w700)),
                          style: FilledButton.styleFrom(
                            backgroundColor: brandPurple,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(
                                horizontal: 14, vertical: 8),
                            shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(10)),
                          ),
                        ),
                        const SizedBox(width: 8),
                        OutlinedButton.icon(
                          onPressed: () => _openCalendarForDate(today),
                          icon: const Icon(Icons.calendar_month_rounded,
                              size: 15),
                          label: const Text('Open Calendar',
                              style: TextStyle(
                                  fontSize: 12.5, fontWeight: FontWeight.w600)),
                          style: OutlinedButton.styleFrom(
                            foregroundColor: const Color(0xFF4F46E5),
                            side: const BorderSide(color: Color(0xFFC7D2FE)),
                            padding: const EdgeInsets.symmetric(
                                horizontal: 14, vertical: 8),
                            shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(10)),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            )
          else
            ...List.generate(upcoming.length, (index) {
              final task = upcoming[index];
              final isLast = index == upcoming.length - 1;
              return _buildUpcomingTaskItem(task, isLast);
            }),
        ],
      ),
    );
  }

  Widget _buildUpcomingTaskItem(Task task, bool isLast) {
    final catName =
        task.category.trim().isNotEmpty ? task.category.trim() : 'Work';
    final catClr = categoryColor(catName);
    final catBg = categoryBgColor(catName);
    final dateMeta = _getUpcomingDateMeta(task.dueDate);

    return Container(
      margin: EdgeInsets.only(bottom: isLast ? 0 : 10),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFF1F5F9), width: 1.2),
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () => _edit(task),
          borderRadius: BorderRadius.circular(16),
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                // 1. Mini Tear-off Calendar Tile (Tapping jumps to Calendar date!)
                Tooltip(
                  message: 'View on Calendar',
                  child: InkWell(
                    onTap: () => _openCalendarForDate(task.dueDate),
                    borderRadius: BorderRadius.circular(10),
                    child: Container(
                      width: 48,
                      height: 52,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(
                          color: dateMeta.accentColor.withValues(alpha: 0.3),
                          width: 1.2,
                        ),
                        boxShadow: [
                          BoxShadow(
                            color: dateMeta.accentColor.withValues(alpha: 0.1),
                            blurRadius: 6,
                            offset: const Offset(0, 2),
                          ),
                        ],
                      ),
                      child: Column(
                        children: [
                          // Top colored stripe with Day/Month label
                          Container(
                            height: 17,
                            width: double.infinity,
                            alignment: Alignment.center,
                            decoration: BoxDecoration(
                              color: dateMeta.accentColor,
                              borderRadius: const BorderRadius.vertical(
                                top: Radius.circular(8.5),
                              ),
                            ),
                            child: Text(
                              dateMeta.header,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                fontSize: 8.5,
                                fontWeight: FontWeight.w800,
                                color: Colors.white,
                                letterSpacing: 0.4,
                              ),
                            ),
                          ),
                          // Day number
                          Expanded(
                            child: Center(
                              child: Text(
                                dateMeta.day,
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w800,
                                  color: dateMeta.accentColor,
                                  letterSpacing: -0.5,
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 12),

                // 2. Main Task Details
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Task Title
                      Text(
                        task.title,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 14.5,
                          fontWeight: FontWeight.w700,
                          color: Color(0xFF0F172A),
                          letterSpacing: -0.2,
                        ),
                      ),
                      const SizedBox(height: 4),

                      // Schedule info & Subtask count
                      Wrap(
                        crossAxisAlignment: WrapCrossAlignment.center,
                        spacing: 8,
                        runSpacing: 4,
                        children: [
                          Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(
                                Icons.access_time_rounded,
                                size: 12.5,
                                color: dateMeta.accentColor,
                              ),
                              const SizedBox(width: 4),
                              Text(
                                dateMeta.label,
                                style: TextStyle(
                                  fontSize: 11.5,
                                  fontWeight: FontWeight.w600,
                                  color: dateMeta.isTomorrow
                                      ? dateMeta.accentColor
                                      : const Color(0xFF64748B),
                                ),
                              ),
                            ],
                          ),
                          if (task.subtasks.isNotEmpty)
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 6,
                                vertical: 1.5,
                              ),
                              decoration: BoxDecoration(
                                color: task.completedSubtasks ==
                                        task.subtasks.length
                                    ? const Color(0xFFECFDF5)
                                    : const Color(0xFFF1F5F9),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Text(
                                '✓ ${task.completedSubtasks}/${task.subtasks.length} steps',
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.w700,
                                  color: task.completedSubtasks ==
                                          task.subtasks.length
                                      ? const Color(0xFF10B981)
                                      : const Color(0xFF64748B),
                                ),
                              ),
                            ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),

                // 3. Category Tag & Action Button to jump to Calendar
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    // Category Badge Pill
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 3.5,
                      ),
                      decoration: BoxDecoration(
                        color: catBg,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(categoryIcon(catName),
                              size: 11, color: catClr),
                          const SizedBox(width: 3.5),
                          Text(
                            catName,
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              color: catClr,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 5),
                    // Quick "Open in Calendar" Icon button
                    Tooltip(
                      message: 'Show on Calendar',
                      child: InkWell(
                        onTap: () => _openCalendarForDate(task.dueDate),
                        borderRadius: BorderRadius.circular(8),
                        child: Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 6, vertical: 3),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: const Color(0xFFE2E8F0)),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: const [
                              Icon(
                                Icons.calendar_month_outlined,
                                size: 12.5,
                                color: Color(0xFF4F46E5),
                              ),
                              SizedBox(width: 3),
                              Text(
                                'View',
                                style: TextStyle(
                                  fontSize: 10.5,
                                  fontWeight: FontWeight.w700,
                                  color: Color(0xFF4F46E5),
                                ),
                              ),
                            ],
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
    );
  }

  ({String header, String day, String label, Color accentColor, bool isTomorrow})
      _getUpcomingDateMeta(DateTime? date) {
    if (date == null) {
      return (
        header: 'LATER',
        day: '--',
        label: 'No due date set',
        accentColor: const Color(0xFF64748B),
        isTomorrow: false,
      );
    }
    final now = DateTime.now();
    final today = dateOnly(now);
    final tomorrow = today.add(const Duration(days: 1));
    final d = dateOnly(date);
    final timeStr = DateFormat('h:mm a').format(date);

    if (d == today) {
      return (
        header: 'TODAY',
        day: '${date.day}',
        label: 'Today, $timeStr',
        accentColor: const Color(0xFF10B981),
        isTomorrow: false,
      );
    } else if (d == tomorrow) {
      return (
        header: 'TMRW',
        day: '${date.day}',
        label: 'Tomorrow, $timeStr',
        accentColor: const Color(0xFF6366F1),
        isTomorrow: true,
      );
    } else {
      final diff = d.difference(today).inDays;
      final header = diff > 1 && diff <= 6
          ? DateFormat('EEE').format(date).toUpperCase()
          : DateFormat('MMM').format(date).toUpperCase();
      final diffLabel = diff > 0 ? ' (In $diff days)' : '';
      return (
        header: header,
        day: '${date.day}',
        label: '${DateFormat('MMM d').format(date)}, $timeStr$diffLabel',
        accentColor: const Color(0xFF3864ED),
        isTomorrow: false,
      );
    }
  }

  Widget _section(String title, {Widget? trailing}) => Padding(
    padding: const EdgeInsets.only(bottom: 14),
    child: Row(
      children: [
        Expanded(
          child: Text(
            title,
            style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800),
          ),
        ),
        ?trailing,
      ],
    ),
  );

  Widget _tasksFilterPill({
    required String label,
    required int count,
    required IconData icon,
    required Color iconColor,
    required bool isSelected,
    required VoidCallback onTap,
  }) {
    return BouncingInteractive(
      onTap: onTap,
      scaleFactor: 0.94,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        curve: Curves.easeOutCubic,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
        decoration: BoxDecoration(
          color: isSelected ? brandPurple : Colors.white,
          borderRadius: BorderRadius.circular(22),
          border: Border.all(
            color: isSelected ? brandPurple : const Color(0xFFE5E7EB),
            width: 1.2,
          ),
          boxShadow: [
            BoxShadow(
              color: isSelected
                  ? brandPurple.withValues(alpha: 0.3)
                  : Colors.black.withValues(alpha: 0.03),
              blurRadius: isSelected ? 8 : 4,
              offset: isSelected ? const Offset(0, 3) : const Offset(0, 1),
            ),
          ],
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              icon,
              size: 17,
              color: isSelected ? Colors.white : iconColor,
            ),
            const SizedBox(width: 8),
            Text(
              '$label ($count)',
              style: TextStyle(
                fontSize: 13,
                fontWeight: isSelected ? FontWeight.w700 : FontWeight.w600,
                color: isSelected ? Colors.white : const Color(0xFF1B1D28),
                letterSpacing: -0.2,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _taskSectionHeader({
    required String sectionKey,
    required String title,
    required int count,
    required IconData icon,
    required Color iconColor,
    required bool isCollapsed,
  }) {
    return BouncingInteractive(
      onTap: () {
        setState(() {
          if (isCollapsed) {
            _collapsedSections.remove(sectionKey);
          } else {
            _collapsedSections.add(sectionKey);
          }
        });
      },
      scaleFactor: 0.98,
      child: Padding(
        padding: const EdgeInsets.only(top: 18, bottom: 10),
        child: Row(
          children: [
            Icon(icon, size: 20, color: iconColor),
            const SizedBox(width: 8),
            Text(
              '$title ($count)',
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w800,
                color: Color(0xFF1B1D28),
                letterSpacing: -0.2,
              ),
            ),
            const Spacer(),
            AnimatedRotation(
              turns: isCollapsed ? 0.5 : 0.0,
              duration: motionDuration(context, 240),
              curve: Curves.easeOutCubic,
              child: const Icon(
                Icons.keyboard_arrow_up_rounded,
                size: 22,
                color: Color(0xFF6B7280),
              ),
            ),
          ],
        ),
      ),
    );
  }

  List<Widget> _buildTaskSection({
    required String sectionKey,
    required String title,
    required List<Task> tasks,
    required IconData icon,
    required Color iconColor,
  }) {
    if (tasks.isEmpty) return const [];
    final isCollapsed = _collapsedSections.contains(sectionKey);

    return [
      SliverToBoxAdapter(
        child: _taskSectionHeader(
          sectionKey: sectionKey,
          title: title,
          count: tasks.length,
          icon: icon,
          iconColor: iconColor,
          isCollapsed: isCollapsed,
        ),
      ),
      if (!isCollapsed)
        SliverList.builder(
          itemCount: tasks.length,
          itemBuilder: (context, index) {
            final task = tasks[index];
            return TaskEntrance(
              key: ValueKey(task.id),
              index: index,
              child: Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: TaskCard(
                  task: task,
                  busy: state.isTaskBusy(task.id),
                  onTap: () => _edit(task),
                  onToggle: () => _toggle(task),
                  onDelete: () => _deleteTask(task),
                  onToggleSubtask: (index) => state.toggleSubtask(task, index),
                  onStatusChange: (newStatus) =>
                      state.updateTaskStatus(task, newStatus),
                  onAddSubtask: (title) => state.addSubtask(task, title),
                  onToggleSubPoint: (stepIdx, ptIdx) =>
                      state.toggleSubPoint(task, stepIdx, ptIdx),
                ),
              ),
            );
          },
        ),
    ];
  }

  void _showPriorityFilterSheet() {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) {
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 20),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: const Color(0xFFE5E7EB),
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                const Text(
                  'Filter by Priority',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF1B1D28),
                  ),
                ),
                const SizedBox(height: 12),
                ListTile(
                  leading: const Icon(Icons.all_inclusive_rounded, color: brandPurple),
                  title: const Text('All priorities', style: TextStyle(fontWeight: FontWeight.w600)),
                  trailing: _priority == 'all'
                      ? const Icon(Icons.check_rounded, color: brandPurple)
                      : null,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  onTap: () {
                    setState(() => _priority = 'all');
                    Navigator.pop(context);
                  },
                ),
                ListTile(
                  leading: const Icon(Icons.flag_rounded, color: Color(0xFFEF4444)),
                  title: const Text('High priority', style: TextStyle(fontWeight: FontWeight.w600)),
                  trailing: _priority == 'high'
                      ? const Icon(Icons.check_rounded, color: brandPurple)
                      : null,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  onTap: () {
                    setState(() => _priority = 'high');
                    Navigator.pop(context);
                  },
                ),
                ListTile(
                  leading: const Icon(Icons.outlined_flag_rounded, color: Color(0xFFF59E0B)),
                  title: const Text('Medium priority', style: TextStyle(fontWeight: FontWeight.w600)),
                  trailing: _priority == 'medium'
                      ? const Icon(Icons.check_rounded, color: brandPurple)
                      : null,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  onTap: () {
                    setState(() => _priority = 'medium');
                    Navigator.pop(context);
                  },
                ),
                ListTile(
                  leading: const Icon(Icons.flag_outlined, color: Color(0xFF10B981)),
                  title: const Text('Low priority', style: TextStyle(fontWeight: FontWeight.w600)),
                  trailing: _priority == 'low'
                      ? const Icon(Icons.check_rounded, color: brandPurple)
                      : null,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  onTap: () {
                    setState(() => _priority = 'low');
                    Navigator.pop(context);
                  },
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  List<Widget> _tasks() {
    final totalTasks = state.tasks.length;
    final doneTasksCount = state.tasks.where((t) => t.completed || t.status == 'completed').length;
    final inProgressTasksCount = state.tasks.where((t) => !t.completed && t.status == 'in_progress').length;
    final todoTasksCount = state.tasks.where((t) => !t.completed && t.status != 'in_progress').length;
    final percent = totalTasks == 0 ? 0 : ((doneTasksCount / totalTasks) * 100).round();

    bool matchesFilter(Task t) {
      if (_priority != 'all' && t.priority != _priority) return false;
      if (_query.isNotEmpty) {
        final searchBody =
            '${t.title} ${t.description} ${t.category} ${t.tags.join(' ')}'
                .toLowerCase();
        if (!searchBody.contains(_query)) return false;
      }
      return true;
    }

    final List<Task> sectionHigh;
    final List<Task> sectionInProgress;
    final List<Task> sectionTodo;
    final List<Task> sectionDone;

    if (_status == 'all') {
      sectionHigh = _sorted(state.tasks.where((t) => matchesFilter(t) && !t.completed && t.status != 'completed' && t.priority == 'high'));
      sectionInProgress = _sorted(state.tasks.where((t) => matchesFilter(t) && !t.completed && t.status == 'in_progress' && t.priority != 'high'));
      sectionTodo = _sorted(state.tasks.where((t) => matchesFilter(t) && !t.completed && t.status != 'in_progress' && t.priority != 'high'));
      sectionDone = _sorted(state.tasks.where((t) => matchesFilter(t) && (t.completed || t.status == 'completed')));
    } else if (_status == 'pending') {
      sectionHigh = _sorted(state.tasks.where((t) => matchesFilter(t) && !t.completed && t.status != 'in_progress' && t.priority == 'high'));
      sectionInProgress = [];
      sectionTodo = _sorted(state.tasks.where((t) => matchesFilter(t) && !t.completed && t.status != 'in_progress' && t.priority != 'high'));
      sectionDone = [];
    } else if (_status == 'in_progress') {
      sectionHigh = _sorted(state.tasks.where((t) => matchesFilter(t) && !t.completed && t.status == 'in_progress' && t.priority == 'high'));
      sectionInProgress = _sorted(state.tasks.where((t) => matchesFilter(t) && !t.completed && t.status == 'in_progress' && t.priority != 'high'));
      sectionTodo = [];
      sectionDone = [];
    } else if (_status == 'completed') {
      sectionHigh = [];
      sectionInProgress = [];
      sectionTodo = [];
      sectionDone = _sorted(state.tasks.where((t) => matchesFilter(t) && (t.completed || t.status == 'completed')));
    } else {
      sectionHigh = [];
      sectionInProgress = [];
      sectionTodo = [];
      sectionDone = [];
    }

    final hasAnyTasks = sectionHigh.isNotEmpty ||
        sectionInProgress.isNotEmpty ||
        sectionTodo.isNotEmpty ||
        sectionDone.isNotEmpty;

    return [
      SliverToBoxAdapter(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 6),
            // Clean iOS Tasks Header
            _buildCleanHeader(
              eyebrow: 'WORKSPACE · ALL TASKS',
              title: 'Tasks',
              subtitle: 'Manage and track your tasks',
              trailing: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  BouncingInteractive(
                    onTap: () {
                      _searchFocusNode.requestFocus();
                    },
                    scaleFactor: 0.92,
                    child: Container(
                      width: 40,
                      height: 40,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        shape: BoxShape.circle,
                        border: Border.all(
                          color: const Color(0xFFE5E7EB),
                          width: 1,
                        ),
                        boxShadow: [
                          BoxShadow(
                            color: const Color(0xFF1B1D28).withValues(alpha: 0.04),
                            blurRadius: 8,
                            offset: const Offset(0, 2),
                          ),
                        ],
                      ),
                      child: const Icon(
                        Icons.search_rounded,
                        color: Color(0xFF1B1D28),
                        size: 20,
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  BouncingInteractive(
                    onTap: _showPriorityFilterSheet,
                    scaleFactor: 0.92,
                    child: Container(
                      width: 40,
                      height: 40,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        shape: BoxShape.circle,
                        border: Border.all(
                          color: const Color(0xFFE5E7EB),
                          width: 1,
                        ),
                        boxShadow: [
                          BoxShadow(
                            color: const Color(0xFF1B1D28).withValues(alpha: 0.04),
                            blurRadius: 8,
                            offset: const Offset(0, 2),
                          ),
                        ],
                      ),
                      child: const Icon(
                        Icons.tune_rounded,
                        color: Color(0xFF1B1D28),
                        size: 20,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            // Horizontal Filter Pills: All, Todo, In Progress, Done
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              physics: const BouncingScrollPhysics(),
              child: Row(
                children: [
                  _tasksFilterPill(
                    label: 'All',
                    count: totalTasks,
                    icon: Icons.layers_rounded,
                    iconColor: brandPurple,
                    isSelected: _status == 'all',
                    onTap: () => setState(() => _status = 'all'),
                  ),
                  const SizedBox(width: 8),
                  _tasksFilterPill(
                    label: 'Todo',
                    count: todoTasksCount,
                    icon: Icons.radio_button_unchecked_rounded,
                    iconColor: const Color(0xFF3864ED),
                    isSelected: _status == 'pending',
                    onTap: () => setState(() => _status = 'pending'),
                  ),
                  const SizedBox(width: 8),
                  _tasksFilterPill(
                    label: 'In Progress',
                    count: inProgressTasksCount,
                    icon: Icons.play_arrow_outlined,
                    iconColor: const Color(0xFFF59E0B),
                    isSelected: _status == 'in_progress',
                    onTap: () => setState(() => _status = 'in_progress'),
                  ),
                  const SizedBox(width: 8),
                  _tasksFilterPill(
                    label: 'Done',
                    count: doneTasksCount,
                    icon: Icons.check_circle_outline_rounded,
                    iconColor: const Color(0xFF10B981),
                    isSelected: _status == 'completed',
                    onTap: () => setState(() => _status = 'completed'),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),

            // Hero Card: Your Tasks (57% circular donut, count, and 3D clipboard)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 18),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(24),
                gradient: const LinearGradient(
                  colors: [
                    Color(0xFF5345FF),
                    Color(0xFF6B5CFF),
                    Color(0xFF7E72FF),
                  ],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                boxShadow: [
                  BoxShadow(
                    color: brandPurple.withValues(alpha: 0.35),
                    blurRadius: 20,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: Row(
                children: [
                  _CircularProgressDonut(
                    progress: totalTasks == 0 ? 0.0 : doneTasksCount / totalTasks,
                    percentText: '$percent%',
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Text(
                          'Your Tasks',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 20,
                            fontWeight: FontWeight.w800,
                            letterSpacing: -0.3,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          '$doneTasksCount of $totalTasks tasks completed',
                          style: TextStyle(
                            color: Colors.white.withValues(alpha: 0.88),
                            fontSize: 13,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  const TaskClipboardGraphic(size: 78),
                ],
              ),
            ),
            const SizedBox(height: 14),

            // Search Bar TextField
            Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFFE8EBF2)),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.02),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: TextField(
                controller: _search,
                focusNode: _searchFocusNode,
                onChanged: _searchTasks,
                style: const TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  color: Color(0xFF1B1D28),
                ),
                decoration: InputDecoration(
                  hintText: 'Search tasks...',
                  hintStyle: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w500,
                    color: Color(0xFF9CA3AF),
                  ),
                  prefixIcon: const Icon(
                    Icons.search_rounded,
                    color: Color(0xFF7E8494),
                    size: 20,
                  ),
                  suffixIcon: _query.isNotEmpty
                      ? IconButton(
                          icon: const Icon(Icons.close_rounded, size: 18),
                          onPressed: () {
                            _search.clear();
                            _searchTasks('');
                          },
                        )
                      : null,
                  border: InputBorder.none,
                  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                ),
              ),
            ),

            if (_priority != 'all')
              Padding(
                padding: const EdgeInsets.only(top: 8),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: const Color(0xFFECE9FF),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            'Priority: ${_priority[0].toUpperCase()}${_priority.substring(1)}',
                            style: const TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w700,
                              color: brandPurple,
                            ),
                          ),
                          const SizedBox(width: 4),
                          GestureDetector(
                            onTap: () => setState(() => _priority = 'all'),
                            child: const Icon(Icons.close_rounded, size: 14, color: brandPurple),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
          ],
        ),
      ),

      if (!hasAnyTasks)
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsets.only(top: 32),
            child: EmptyTasks(
              title: _query.isEmpty ? 'No tasks here yet' : 'No matching tasks',
              message: _query.isEmpty
                  ? 'Try a different filter or add your next task.'
                  : 'Try another title, category or tag.',
              onAdd: () => _edit(),
            ),
          ),
        )
      else ...[
        ..._buildTaskSection(
          sectionKey: 'high',
          title: 'High Priority',
          tasks: sectionHigh,
          icon: Icons.flag_rounded,
          iconColor: const Color(0xFFEF4444),
        ),
        ..._buildTaskSection(
          sectionKey: 'in_progress',
          title: 'In Progress',
          tasks: sectionInProgress,
          icon: Icons.play_arrow_outlined,
          iconColor: const Color(0xFFF59E0B),
        ),
        ..._buildTaskSection(
          sectionKey: 'todo',
          title: 'Todo',
          tasks: sectionTodo,
          icon: Icons.radio_button_unchecked_rounded,
          iconColor: const Color(0xFF3864ED),
        ),
        ..._buildTaskSection(
          sectionKey: 'done',
          title: 'Done',
          tasks: sectionDone,
          icon: Icons.check_circle_outline_rounded,
          iconColor: const Color(0xFF10B981),
        ),
      ],
    ];
  }

  List<Widget> _calendar() {
    final first = DateTime(_month.year, _month.month, 1);
    final days = DateTime(_month.year, _month.month + 1, 0).day;
    final offset = first.weekday - 1;
    final datesWithTasks = state.tasks.map((task) => task.dueDate).toSet();
    final selected = _sorted(
      state.tasks.where((t) => t.dueDate == _selectedDay),
    );
    return [
      SliverToBoxAdapter(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 6),
            _buildCleanHeader(
              eyebrow: 'SCHEDULE & DEADLINES',
              title: 'Calendar',
              subtitle: 'Track your schedule & deadlines',
              eyebrowColor: const Color(0xFF3864ED),
            ),
            const SizedBox(height: 16),
            Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(
                  color: const Color(0x10000000),
                  width: 0.5,
                ),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.03),
                    blurRadius: 10,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              padding: const EdgeInsets.all(18),
              child: Column(
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          DateFormat('MMMM yyyy').format(_month),
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w700,
                            color: Color(0xFF1C1C1E),
                            letterSpacing: -0.3,
                          ),
                        ),
                      ),
                      // Quick "Today" jump button
                      TextButton.icon(
                        style: TextButton.styleFrom(
                          foregroundColor: brandPurple,
                          padding: const EdgeInsets.symmetric(
                              horizontal: 10, vertical: 4),
                          backgroundColor: const Color(0xFFECE9FF),
                          shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(10)),
                          minimumSize: Size.zero,
                          tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                        ),
                        onPressed: () =>
                            _openCalendarForDate(DateTime.now()),
                        icon: const Icon(Icons.today_rounded, size: 14),
                        label: const Text('Today',
                            style: TextStyle(
                                fontWeight: FontWeight.w700, fontSize: 12)),
                      ),
                      const SizedBox(width: 8),
                      Container(
                        decoration: BoxDecoration(
                          color: const Color(0xFFF2F2F7),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            IconButton(
                              tooltip: 'Previous month',
                              constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
                              padding: EdgeInsets.zero,
                              onPressed: () => setState(
                                () => _month = DateTime(
                                  _month.year,
                                  _month.month - 1,
                                ),
                              ),
                              icon: const Icon(Icons.chevron_left_rounded, size: 22, color: Color(0xFF1C1C1E)),
                            ),
                            Container(width: 0.5, height: 18, color: const Color(0x22000000)),
                            IconButton(
                              tooltip: 'Next month',
                              constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
                              padding: EdgeInsets.zero,
                              onPressed: () => setState(
                                () => _month = DateTime(
                                  _month.year,
                                  _month.month + 1,
                                ),
                              ),
                              icon: const Icon(Icons.chevron_right_rounded, size: 22, color: Color(0xFF1C1C1E)),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  Row(
                    children: [
                      for (final day in ['M', 'T', 'W', 'T', 'F', 'S', 'S'])
                        Expanded(
                          child: Center(
                            child: Text(
                              day,
                              style: const TextStyle(
                                color: Color(0xFF8E8E93),
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  AnimatedSize(
                    duration: motionDuration(context),
                    alignment: Alignment.topCenter,
                    child: MotionSwitcher(
                      child: GridView.builder(
                        key: ValueKey(_month),
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        gridDelegate:
                            const SliverGridDelegateWithFixedCrossAxisCount(
                              crossAxisCount: 7,
                              mainAxisSpacing: 4,
                              crossAxisSpacing: 2,
                              childAspectRatio: .85,
                            ),
                        itemCount: ((days + offset + 6) ~/ 7) * 7,
                        itemBuilder: (context, index) {
                          final day = index - offset + 1;
                          if (day < 1 || day > days) {
                            return const SizedBox.shrink();
                          }
                          final date = DateTime(first.year, first.month, day);
                          final active = date == _selectedDay;
                          final hasTasks = datesWithTasks.contains(date);
                          final isToday = date == dateOnly(DateTime.now());
                          return Semantics(
                            label: DateFormat('MMMM d, yyyy').format(date),
                            selected: active,
                            button: true,
                            child: InkWell(
                              onTap: () =>
                                  setState(() => _selectedDay = date),
                              borderRadius: BorderRadius.circular(12),
                              child: AnimatedContainer(
                                duration: motionDuration(context, 160),
                                decoration: BoxDecoration(
                                  color: active
                                      ? brandPurple
                                      : isToday
                                          ? const Color(0xFFECE9FF)
                                          : null,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Column(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    Text(
                                      '$day',
                                      style: TextStyle(
                                        color: active
                                            ? Colors.white
                                            : isToday
                                                ? brandPurple
                                                : const Color(0xFF1C1C1E),
                                        fontWeight: active || isToday
                                            ? FontWeight.w700
                                            : FontWeight.w500,
                                      ),
                                    ),
                                    const SizedBox(height: 4),
                                    Container(
                                      width: 4,
                                      height: 4,
                                      decoration: BoxDecoration(
                                        shape: BoxShape.circle,
                                        color: hasTasks
                                            ? active
                                                  ? Colors.white
                                                  : brandPurple
                                            : Colors.transparent,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          );
                        },
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            Row(
              children: [
                Expanded(
                  child: _section(
                    _selectedDay == dateOnly(DateTime.now())
                        ? 'Today • ${DateFormat('EEEE, MMM d').format(_selectedDay)}'
                        : _selectedDay ==
                                dateOnly(DateTime.now()
                                    .add(const Duration(days: 1)))
                            ? 'Tomorrow • ${DateFormat('EEEE, MMM d').format(_selectedDay)}'
                            : DateFormat('EEEE, MMM d, yyyy')
                                .format(_selectedDay),
                  ),
                ),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: selected.isNotEmpty
                        ? const Color(0xFFECE9FF)
                        : const Color(0xFFF1F5F9),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Text(
                    '${selected.length} tasks',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: selected.isNotEmpty
                          ? brandPurple
                          : const Color(0xFF64748B),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
      _cards(
        selected,
        emptyTitle: 'Your day is clear',
        emptyMessage: 'No tasks are due on this date.',
      ),
    ];
  }

  Widget _iosSectionHeader(String title) => Padding(
    padding: const EdgeInsets.only(left: 16, bottom: 8),
    child: Text(
      title,
      style: const TextStyle(
        fontSize: 13,
        fontWeight: FontWeight.w600,
        color: Color(0xFF8E8E93),
        letterSpacing: -0.2,
      ),
    ),
  );

  Widget _iosGroup(List<Widget> children) => Container(
    decoration: BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(16),
      border: Border.all(color: const Color(0x10000000), width: 0.5),
    ),
    child: ClipRRect(
      borderRadius: BorderRadius.circular(16),
      child: Column(children: children),
    ),
  );

  Widget _iosTile({
    required Widget icon,
    required String title,
    String? subtitle,
    Widget? trailing,
    VoidCallback? onTap,
    Color? titleColor,
  }) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          child: Row(
            children: [
              icon,
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w500,
                        color: titleColor ?? const Color(0xFF1C1C1E),
                        letterSpacing: -0.2,
                      ),
                    ),
                    if (subtitle != null) ...[
                      const SizedBox(height: 2),
                      Text(
                        subtitle,
                        style: const TextStyle(
                          fontSize: 13,
                          color: Color(0xFF8E8E93),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              ?trailing,
            ],
          ),
        ),
      ),
    );
  }

  Widget _squircleIcon(IconData icon, Color color) => Container(
    width: 32,
    height: 32,
    decoration: BoxDecoration(
      color: color,
      borderRadius: BorderRadius.circular(8),
    ),
    child: Icon(icon, color: Colors.white, size: 18),
  );

  Widget _profile() {
    final user = state.user!;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const SizedBox(height: 6),
        _buildCleanHeader(
          eyebrow: 'SETTINGS & ACCOUNT',
          title: 'Profile',
          subtitle: 'Account settings & preferences',
          eyebrowColor: const Color(0xFF168268),
        ),
        const SizedBox(height: 20),
        Center(
          child: Column(
            children: [
              Container(
                width: 84,
                height: 84,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: const LinearGradient(
                    colors: [Color(0xFF5B4DFF), Color(0xFF8B5CF6)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  boxShadow: [
                    BoxShadow(
                      color: brandPurple.withValues(alpha: 0.3),
                      blurRadius: 16,
                      offset: const Offset(0, 6),
                    ),
                  ],
                ),
                child: Center(
                  child: Text(
                    user.name.characters.first.toUpperCase(),
                    style: const TextStyle(
                      fontSize: 34,
                      color: Colors.white,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 14),
              Text(
                user.name,
                style: const TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.w800,
                  color: Color(0xFF1C1C1E),
                  letterSpacing: -0.3,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                user.email,
                style: const TextStyle(
                  fontSize: 14,
                  color: Color(0xFF8E8E93),
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 28),
        _iosSectionHeader('ACCOUNT & TASKS'),
        _iosGroup([
          _iosTile(
            icon: _squircleIcon(Icons.check_rounded, const Color(0xFF007AFF)),
            title: 'Completed tasks',
            trailing: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  '${state.tasks.where((t) => t.completed).length}',
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFF8E8E93),
                  ),
                ),
              ],
            ),
          ),
          const Divider(height: 0.5, thickness: 0.5, indent: 62, color: Color(0x1F000000)),
          _iosTile(
            icon: _squircleIcon(Icons.cloud_done_rounded, const Color(0xFF34C759)),
            title: 'Connected account',
            subtitle: 'Shared with your Task Manager website',
          ),
          const Divider(height: 0.5, thickness: 0.5, indent: 62, color: Color(0x1F000000)),
          _iosTile(
            icon: _squircleIcon(Icons.auto_stories_rounded, const Color(0xFF5856D6)),
            title: 'App Tour & Features',
            subtitle: 'Revisit the feature walkthrough',
            trailing: const Icon(Icons.chevron_right_rounded, color: Color(0xFFC7C7CC), size: 22),
            onTap: () {
              Navigator.of(context).push(
                MaterialPageRoute<void>(
                  builder: (_) => OnboardingScreen(
                    onGetStarted: () => Navigator.of(context).pop(),
                    onSignIn: () => Navigator.of(context).pop(),
                  ),
                ),
              );
            },
          ),
        ]),
        const SizedBox(height: 24),
        _iosSectionHeader('SYSTEM'),
        _iosGroup([
          _iosTile(
            icon: _squircleIcon(Icons.info_outline_rounded, const Color(0xFF8E8E93)),
            title: 'Task Manager',
            subtitle: 'iOS Edition · Version 1.0.0',
            trailing: const Text(
              'v1.0.0',
              style: TextStyle(
                fontSize: 14,
                color: Color(0xFF8E8E93),
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
        ]),
        const SizedBox(height: 24),
        _iosSectionHeader('SESSION'),
        _iosGroup([
          Material(
            color: Colors.transparent,
            child: InkWell(
              onTap: _signingOut ? null : _logout,
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
                child: Center(
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      if (_signingOut)
                        const Padding(
                          padding: EdgeInsets.only(right: 10),
                          child: SizedBox(
                            width: 16,
                            height: 16,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              valueColor: AlwaysStoppedAnimation(Color(0xFFFF3B30)),
                            ),
                          ),
                        )
                      else
                        const Padding(
                          padding: EdgeInsets.only(right: 8),
                          child: Icon(
                            Icons.logout_rounded,
                            color: Color(0xFFFF3B30),
                            size: 20,
                          ),
                        ),
                      Text(
                        _signingOut ? 'Signing out…' : 'Sign out',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w600,
                          color: Color(0xFFFF3B30),
                          letterSpacing: -0.2,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ]),
      ],
    );
  }

  Future<void> _logout() async {
    setState(() => _signingOut = true);
    try {
      await state.logout();
    } catch (e) {
      if (mounted) {
        _message(
          e is ApiException
              ? e.message
              : 'Could not sign out. Please try again.',
        );
      }
    } finally {
      if (mounted) setState(() => _signingOut = false);
    }
  }

  List<Task> get _reminders {
    final today = dateOnly(DateTime.now());
    final tomorrow = today.add(const Duration(days: 1));
    return _sorted(
      state.tasks.where(
        (t) => !t.completed && (t.endDate == today || t.endDate == tomorrow),
      ),
    );
  }

  void _showReminders() => showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    showDragHandle: true,
    builder: (sheetContext) => SafeArea(
      child: SizedBox(
        height: MediaQuery.sizeOf(context).height * .65,
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 0, 20, 24),
          children: [
            const Text(
              'Due reminders',
              style: TextStyle(fontSize: 24, fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 8),
            const Text(
              'Tasks ending today or tomorrow.',
              style: TextStyle(color: Colors.blueGrey),
            ),
            const SizedBox(height: 20),
            if (_reminders.isEmpty)
              const EmptyTasks(
                title: 'You’re all caught up',
                message: 'No tasks are ending today or tomorrow.',
              ),
            for (final task in _reminders)
              ListTile(
                contentPadding: const EdgeInsets.symmetric(vertical: 8),
                leading: const CircleAvatar(
                  backgroundColor: Color(0xFFEAF0FF),
                  child: Icon(
                    Icons.notifications_active_outlined,
                    color: brandBlue,
                  ),
                ),
                title: Text(task.title),
                subtitle: Text(
                  'Ends ${DateFormat('MMM d').format(task.endDate!)}',
                ),
                trailing: const Icon(Icons.chevron_right),
                onTap: () {
                  Navigator.pop(sheetContext);
                  _edit(task);
                },
              ),
          ],
        ),
      ),
    ),
  );
}

class _CircularProgressDonut extends StatelessWidget {
  const _CircularProgressDonut({
    required this.progress,
    required this.percentText,
  });

  final double progress;
  final String percentText;

  @override
  Widget build(BuildContext context) {
    final disableMotion = MediaQuery.disableAnimationsOf(context);
    return SizedBox(
      width: 76,
      height: 76,
      child: TweenAnimationBuilder<double>(
        tween: Tween<double>(begin: 0.0, end: progress.clamp(0.0, 1.0)),
        duration: disableMotion ? Duration.zero : const Duration(milliseconds: 700),
        curve: Curves.easeOutCubic,
        builder: (context, value, _) {
          final animatedPercent = (value * 100).round();
          return Stack(
            alignment: Alignment.center,
            children: [
              CustomPaint(
                size: const Size(76, 76),
                painter: _DonutProgressPainter(
                  progress: value,
                  backgroundColor: Colors.white.withValues(alpha: 0.22),
                  progressColor: Colors.white,
                  strokeWidth: 8,
                ),
              ),
              Text(
                '$animatedPercent%',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 18,
                  fontWeight: FontWeight.w800,
                  letterSpacing: -0.5,
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}

class _DonutProgressPainter extends CustomPainter {
  const _DonutProgressPainter({
    required this.progress,
    required this.backgroundColor,
    required this.progressColor,
    required this.strokeWidth,
  });

  final double progress;
  final Color backgroundColor;
  final Color progressColor;
  final double strokeWidth;

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final radius = (size.width - strokeWidth) / 2;

    final bgPaint = Paint()
      ..color = backgroundColor
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth;

    canvas.drawCircle(center, radius, bgPaint);

    if (progress > 0) {
      final fgPaint = Paint()
        ..color = progressColor
        ..style = PaintingStyle.stroke
        ..strokeWidth = strokeWidth
        ..strokeCap = StrokeCap.round;

      const startAngle = -math.pi / 2;
      final sweepAngle = 2 * math.pi * progress;
      canvas.drawArc(
        Rect.fromCircle(center: center, radius: radius),
        startAngle,
        sweepAngle,
        false,
        fgPaint,
      );
    }
  }

  @override
  bool shouldRepaint(covariant _DonutProgressPainter old) =>
      old.progress != progress ||
      old.backgroundColor != backgroundColor ||
      old.progressColor != progressColor ||
      old.strokeWidth != strokeWidth;
}



