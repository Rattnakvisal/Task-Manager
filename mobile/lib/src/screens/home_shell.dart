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
  String _homeFilter = 'all';
  String _query = '', _status = 'all', _priority = 'all';
  String? _error;
  bool _signingOut = false;
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
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) _refresh();
    });
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

  void _searchTasks(String value) {
    _searchDebounce?.cancel();
    _searchDebounce = Timer(const Duration(milliseconds: 180), () {
      if (mounted) setState(() => _query = value.trim().toLowerCase());
    });
  }

  Future<void> _refresh() async {
    if (!mounted || state.user == null) return;
    setState(() => _error = null);
    try {
      await state.refresh();
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
    try {
      await state.toggleTask(task);
      if (mounted && !MediaQuery.disableAnimationsOf(context)) {
        HapticFeedback.selectionClick();
      }
    } catch (e) {
      if (mounted) {
        _message(e is ApiException ? e.message : 'Could not update task.');
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
              onDelete: () => state.deleteTask(task.id),
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
      if (state.user == null) return const SizedBox.shrink();
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
                  // Floating iOS Sync Pill when refreshing tasks
                  AnimatedSlide(
                    duration: const Duration(milliseconds: 260),
                    curve: Curves.easeOutCubic,
                    offset: state.busy && _tab != 3
                        ? Offset.zero
                        : const Offset(0, -1),
                    child: AnimatedOpacity(
                      duration: const Duration(milliseconds: 200),
                      opacity: state.busy && _tab != 3 ? 1.0 : 0.0,
                      child: Padding(
                        padding: const EdgeInsets.only(top: 4, bottom: 4),
                        child: Center(
                          child: Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 14,
                              vertical: 6,
                            ),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(20),
                              border: Border.all(
                                color: const Color(0xFFE5E7EB),
                                width: 1,
                              ),
                              boxShadow: [
                                BoxShadow(
                                  color: brandPurple.withValues(alpha: 0.12),
                                  blurRadius: 14,
                                  offset: const Offset(0, 4),
                                ),
                              ],
                            ),
                            child: const Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                CupertinoActivityIndicator(
                                  radius: 7,
                                  color: brandPurple,
                                ),
                                SizedBox(width: 8),
                                Text(
                                  'Syncing workspace…',
                                  style: TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w700,
                                    color: brandPurple,
                                    letterSpacing: -0.2,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                  MotionSwitcher(
                    child: _error == null || _tab == 3
                        ? const SizedBox.shrink(key: ValueKey('no-error'))
                        : MaterialBanner(
                            key: ValueKey(_error),
                            content: Text(_error!),
                            leading: const Icon(Icons.cloud_off_outlined),
                            actions: [
                              TextButton(
                                onPressed: _refresh,
                                child: const Text('Retry'),
                              ),
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
                          onRefresh: _refresh,
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
                                        !state.hasLoadedTasks &&
                                                state.tasks.isEmpty &&
                                                _tab != 4 &&
                                                _tab != 3
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
          ],
        ),
        floatingActionButton: ExcludeSemantics(
          excluding: _tab == 4 || _tab == 3,
          child: IgnorePointer(
            ignoring: _tab == 4 || _tab == 3,
            child: AnimatedScale(
              scale: _tab == 4 || _tab == 3 ? 0 : 1,
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
          onItemSelected: _selectTab,
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
    final user = state.user!;
    final total = state.tasks.length;
    final completed = state.tasks.where((t) => t.completed).length;
    final pendingCount = state.tasks.where((t) => t.status == 'pending').length;
    final inProgressCount =
        state.tasks.where((t) => t.status == 'in_progress').length;

    final today = dateOnly(DateTime.now());
    final todayTasks = _sorted(state.tasks.where((t) => t.dueDate == today));

    final List<Task> sourceTasks =
        todayTasks.isNotEmpty ? todayTasks : _sorted(state.tasks);
    final displayedTasks = _homeFilter == 'all'
        ? sourceTasks
        : _sorted(state.tasks.where((t) => t.status == _homeFilter));

    final progressRatio = total > 0 ? (completed / total).clamp(0.0, 1.0) : 0.0;

    return [
      SliverToBoxAdapter(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 6),
            // Clean iOS Greeting Header
            _buildCleanHeader(
              eyebrow: DateFormat('EEEE, MMM d').format(DateTime.now()).toUpperCase(),
              title: 'Hello, ${user.name.split(' ').first}',
              emoji: '👋',
              subtitle: 'Let’s make today productive!',
              trailing: BouncingInteractive(
                onTap: _showReminders,
                scaleFactor: 0.92,
                child: Container(
                  width: 42,
                  height: 42,
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
                        blurRadius: 10,
                        offset: const Offset(0, 3),
                      ),
                    ],
                  ),
                  child: Stack(
                    alignment: Alignment.center,
                    children: [
                      const Icon(
                        Icons.notifications_none_rounded,
                        color: Color(0xFF1B1D28),
                        size: 21,
                      ),
                      if (_reminders.isNotEmpty)
                        Positioned(
                          top: 9,
                          right: 9,
                          child: Container(
                            width: 8,
                            height: 8,
                            decoration: BoxDecoration(
                              color: const Color(0xFFFF3B30),
                              shape: BoxShape.circle,
                              border: Border.all(color: Colors.white, width: 1.5),
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
              ),
            ),
            const SizedBox(height: 20),

            // Hero "Task Overview" Banner Card
            Container(
              width: double.infinity,
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [
                    Color(0xFF4361EE),
                    Color(0xFF5B4DFF),
                    Color(0xFF6B48FF),
                  ],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(26),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFF5B4DFF).withValues(alpha: 0.38),
                    blurRadius: 26,
                    offset: const Offset(0, 10),
                  ),
                ],
              ),
              child: Stack(
                children: [
                  Positioned.fill(
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(26),
                      child: CustomPaint(
                        painter: _WavyCardBackgroundPainter(),
                      ),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 20,
                      vertical: 22,
                    ),
                    child: Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'Task Overview',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 16,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                              const SizedBox(height: 3),
                              const Text(
                                'Your task progress today',
                                style: TextStyle(
                                  color: Color(0xFFD6DEFF),
                                  fontSize: 12.5,
                                  fontWeight: FontWeight.w400,
                                ),
                              ),
                              const SizedBox(height: 18),
                              Text(
                                '$completed of $total tasks done',
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 22,
                                  fontWeight: FontWeight.w800,
                                  letterSpacing: -0.4,
                                ),
                              ),
                              const SizedBox(height: 12),
                              ClipRRect(
                                borderRadius: BorderRadius.circular(10),
                                child: AnimatedTaskProgress(
                                  value: progressRatio,
                                  height: 8,
                                  color: Colors.white,
                                  backgroundColor:
                                      Colors.white.withValues(alpha: 0.28),
                                ),
                              ),
                              const SizedBox(height: 8),
                              Text(
                                total == 0
                                    ? 'Ready to make today productive!'
                                    : completed == total
                                        ? '100% completed · All caught up! 🎉'
                                        : '${(progressRatio * 100).toInt()}% completed',
                                style: const TextStyle(
                                  color: Color(0xFFE2E8FF),
                                  fontSize: 12,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 8),
                        TaskClipboardGraphic(
                          completedCount: completed,
                          totalCount: total,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),

            // Horizontal Row of 4 Filter Cards
            Row(
              children: [
                Expanded(
                  child: _homeFilterCard(
                    keyName: 'all',
                    label: 'All',
                    subtitle: 'All Tasks',
                    count: total,
                    icon: Icons.layers_rounded,
                    accentColor: brandPurple,
                    activeBg: const Color(0xFFECE9FF),
                    activeBorder: const Color(0xFF8C7DFF),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _homeFilterCard(
                    keyName: 'pending',
                    label: 'Todo',
                    subtitle: 'To Do',
                    count: pendingCount,
                    icon: Icons.radio_button_unchecked_rounded,
                    accentColor: const Color(0xFF3864ED),
                    activeBg: const Color(0xFFEFF6FF),
                    activeBorder: const Color(0xFF60A5FA),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _homeFilterCard(
                    keyName: 'in_progress',
                    label: 'In Progress',
                    subtitle: 'In Progress',
                    count: inProgressCount,
                    icon: Icons.play_arrow_outlined,
                    accentColor: const Color(0xFFF59E0B),
                    activeBg: const Color(0xFFFEF3C7),
                    activeBorder: const Color(0xFFFBBF24),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _homeFilterCard(
                    keyName: 'completed',
                    label: 'Done',
                    subtitle: 'Completed',
                    count: completed,
                    icon: Icons.check_circle_outline_rounded,
                    accentColor: const Color(0xFF10B981),
                    activeBg: const Color(0xFFECFDF5),
                    activeBorder: const Color(0xFF34D399),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),

            // Section Header: "Today's Tasks"
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        "Today's Tasks",
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.w800,
                          color: Color(0xFF1B1D28),
                          letterSpacing: -0.3,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        '${displayedTasks.length} tasks for today',
                        style: const TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w400,
                          color: Color(0xFF8E95A5),
                        ),
                      ),
                    ],
                  ),
                ),
                TextButton(
                  onPressed: () => _selectTab(1),
                  style: TextButton.styleFrom(
                    foregroundColor: brandPurple,
                    padding: const EdgeInsets.symmetric(horizontal: 10),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: const [
                      Text(
                        'View All',
                        style: TextStyle(
                          fontWeight: FontWeight.w700,
                          fontSize: 13.5,
                        ),
                      ),
                      SizedBox(width: 4),
                      Icon(Icons.chevron_right_rounded, size: 18),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
          ],
        ),
      ),
      _cards(
        displayedTasks,
        emptyTitle: 'No tasks found',
        emptyMessage: _homeFilter == 'all'
            ? 'A little breathing room! No tasks are due today.'
            : 'No tasks currently matching this filter.',
      ),
    ];
  }

  Widget _homeFilterCard({
    required String keyName,
    required String label,
    required String subtitle,
    required int count,
    required IconData icon,
    required Color accentColor,
    required Color activeBg,
    required Color activeBorder,
  }) {
    final isSelected = _homeFilter == keyName;
    return BouncingInteractive(
      onTap: () {
        HapticFeedback.selectionClick();
        setState(() => _homeFilter = keyName);
      },
      scaleFactor: 0.94,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 12),
        decoration: BoxDecoration(
          color: isSelected ? activeBg : Colors.white,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(
            color: isSelected ? activeBorder : const Color(0xFFF0F2F8),
            width: isSelected ? 1.6 : 1.2,
          ),
          boxShadow: [
            if (isSelected)
              BoxShadow(
                color: accentColor.withValues(alpha: 0.22),
                blurRadius: 14,
                offset: const Offset(0, 5),
              )
            else
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.03),
                blurRadius: 8,
                offset: const Offset(0, 2),
              ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, color: accentColor, size: 22),
            const SizedBox(height: 8),
            Text(
              label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontSize: 12.5,
                fontWeight: isSelected ? FontWeight.w700 : FontWeight.w600,
                color: const Color(0xFF1B1D28),
              ),
            ),
            const SizedBox(height: 4),
            MotionSwitcher(
              child: Text(
                key: ValueKey('$keyName-$count'),
                '$count',
                style: const TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.w800,
                  color: Color(0xFF1B1D28),
                  letterSpacing: -0.5,
                ),
              ),
            ),
            const SizedBox(height: 2),
            Text(
              subtitle,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                fontSize: 10.5,
                fontWeight: FontWeight.w500,
                color: Color(0xFF8E95A5),
              ),
            ),
          ],
        ),
      ),
    );
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
                  onDelete: () => state.deleteTask(task.id),
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
            _section(DateFormat('EEEE, MMM d').format(_selectedDay)),
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

class _WavyCardBackgroundPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = Colors.white.withValues(alpha: 0.08)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 32;

    final path1 = Path()
      ..moveTo(-30, size.height * 0.8)
      ..quadraticBezierTo(
        size.width * 0.35,
        size.height * 0.2,
        size.width * 0.8,
        size.height * 0.9,
      );
    canvas.drawPath(path1, paint);

    final path2 = Path()
      ..moveTo(size.width * 0.2, size.height * 1.1)
      ..quadraticBezierTo(
        size.width * 0.6,
        size.height * 0.4,
        size.width * 1.1,
        size.height * 0.3,
      );
    canvas.drawPath(path2, paint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
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


