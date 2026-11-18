import 'dart:ui';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:intl/intl.dart';

import '../models/models.dart';
import '../services/api.dart';
import '../state/app_state.dart';
import '../widgets/sweet_alert.dart';
import '../widgets/motion.dart';
import '../widgets/task_status_widgets.dart';

class TaskEditor extends StatefulWidget {
  const TaskEditor({super.key, required this.state, this.task});
  final AppState state;
  final Task? task;

  @override
  State<TaskEditor> createState() => _TaskEditorState();
}

class _TaskEditorState extends State<TaskEditor> {
  final _form = GlobalKey<FormState>();
  late final TextEditingController _title;
  late final TextEditingController _description;
  late final TextEditingController _category;
  final TextEditingController _subtask = TextEditingController();
  final TextEditingController _tagInput = TextEditingController();

  late String _priority;
  late String _status;
  late bool _pinned;
  late DateTime? _dueDate;
  late DateTime? _endDate;
  late List<Map<String, dynamic>> _subtasks;
  late List<String> _tagList;

  bool _busy = false;
  bool _dirty = false;
  bool _confirming = false;
  bool _exitingSession = false;
  String? _error;

  static const Color brandPurple = Color(0xFF5B4DFF);
  static const Color brandPurpleLight = Color(0xFFEEF0FF);

  static const List<({String name, IconData icon, Color color})> _presetCategories = [
    (name: 'Work', icon: Icons.work_rounded, color: Color(0xFF6366F1)),
    (name: 'Personal', icon: Icons.person_rounded, color: Color(0xFFEC4899)),
    (name: 'Study', icon: Icons.school_rounded, color: Color(0xFF8B5CF6)),
    (name: 'Project', icon: Icons.rocket_launch_rounded, color: Color(0xFF0EA5E9)),
    (name: 'Idea', icon: Icons.lightbulb_rounded, color: Color(0xFFF59E0B)),
    (name: 'Shopping', icon: Icons.shopping_bag_rounded, color: Color(0xFF10B981)),
  ];

  static const List<String> _suggestedTags = [
    'urgent',
    'design',
    'feature',
    'review',
    'meeting',
    'bug',
  ];

  @override
  void initState() {
    super.initState();
    final task = widget.task;
    _title = TextEditingController(text: task?.title ?? '');
    _description = TextEditingController(text: task?.description ?? '');
    _category = TextEditingController(text: task?.category ?? '');
    _priority = task?.priority ?? 'medium';
    _status = task?.status ?? 'pending';
    _pinned = task?.isPinned ?? false;
    _dueDate = task?.dueDate;
    _endDate = task?.endDate;
    _subtasks = task?.subtasks.map((s) => Map<String, dynamic>.from(s)).toList() ?? [];
    _tagList = task?.tags.toList() ?? [];

    for (final c in [_title, _description, _category, _subtask, _tagInput]) {
      c.addListener(_markDirty);
    }
    widget.state.addListener(_checkSession);
  }

  void _markDirty() {
    if (!_dirty) setState(() => _dirty = true);
  }

  void _checkSession() {
    if (widget.state.user == null && mounted && !_exitingSession) {
      _exitingSession = true;
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) Navigator.of(context).popUntil((route) => route.isFirst);
      });
    }
  }

  @override
  void dispose() {
    widget.state.removeListener(_checkSession);
    for (final c in [_title, _description, _category, _subtask, _tagInput]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _back() async {
    if (_busy || _confirming) return;
    _confirming = true;
    try {
      if (!_dirty ||
          await SweetAlert.warning(
            context,
            title: 'Discard changes?',
            message: 'Your changes have not been saved. Are you sure you want to discard them?',
            confirmText: 'Discard',
            cancelText: 'Keep Editing',
          )) {
        if (mounted) {
          setState(() => _dirty = false);
          Navigator.pop(context);
        }
      }
    } finally {
      _confirming = false;
    }
  }

  void _addSubtask() {
    final text = _subtask.text.trim();
    if (text.isEmpty) return;
    HapticFeedback.lightImpact();
    setState(() {
      _subtasks.add({
        'id': 'mobile-${DateTime.now().microsecondsSinceEpoch}',
        'title': text,
        'completed': false,
      });
      _subtask.clear();
      _dirty = true;
    });
  }

  void _addTag(String rawTag) {
    final tag = rawTag.replaceAll('#', '').trim().toLowerCase();
    if (tag.isEmpty || _tagList.contains(tag)) return;
    HapticFeedback.selectionClick();
    setState(() {
      _tagList.add(tag);
      _dirty = true;
    });
  }

  void _removeTag(String tag) {
    HapticFeedback.selectionClick();
    setState(() {
      _tagList.remove(tag);
      _dirty = true;
    });
  }

  void _addTagFromInput() {
    final text = _tagInput.text.trim();
    if (text.isEmpty) return;
    final parts = text.split(',');
    for (final p in parts) {
      _addTag(p);
    }
    _tagInput.clear();
  }

  Future<void> _save() async {
    if (_busy || _exitingSession) return;
    if (!_form.currentState!.validate()) return;
    if (_dueDate != null && _endDate != null && _endDate!.isBefore(_dueDate!)) {
      setState(() => _error = 'End date must be on or after the due date.');
      return;
    }
    _addSubtask();
    _addTagFromInput();
    FocusManager.instance.primaryFocus?.unfocus();

    if (widget.task != null) {
      final confirmed = await SweetAlert.confirm(
        context,
        title: 'Save changes?',
        message: 'Are you ready to save your changes to "${_title.text.trim()}"?',
        confirmText: 'Done',
        cancelText: 'Cancel',
        confirmColor: const Color(0xFF6366F1),
        icon: Icons.edit_note_rounded,
      );
      if (!confirmed || !mounted) return;
    }

    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await widget.state.saveTask({
        'title': _title.text.trim(),
        'description': _description.text.trim(),
        'priority': _priority,
        'status': _status,
        'category': _category.text.trim(),
        'due_date': apiDate(_dueDate),
        'end_date': apiDate(_endDate),
        'tags': _tagList,
        'subtasks': _subtasks,
        'is_pinned': _pinned,
      }, id: widget.task?.id);
      if (mounted) {
        setState(() {
          _dirty = false;
          _busy = false;
        });
        await SweetAlert.success(
          context,
          title: widget.task == null
              ? '🎉 Task Created!'
              : '🎉 Done!',
          message: widget.task == null
              ? 'Task "${_title.text.trim()}" has been created successfully.'
              : 'All changes to "${_title.text.trim()}" have been saved successfully.',
          confirmText: 'OK',
          autoCloseDuration: const Duration(milliseconds: 2000),
        );
        if (mounted) {
          Navigator.pop(context);
        }
      }
    } catch (e) {
      if (mounted) {
        setState(
          () => _error = e is ApiException
              ? e.message
              : 'Could not save task. Please try again.',
        );
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _delete() async {
    if (_busy || _confirming || _exitingSession) return;
    _confirming = true;
    final confirmed = await SweetAlert.danger(
      context,
      title: 'Delete Task?',
      message: 'This task and all its subtasks will be permanently removed.',
      confirmText: 'Yes, Delete',
      cancelText: 'Cancel',
    );
    _confirming = false;
    if (!confirmed || !mounted || _exitingSession) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await widget.state.deleteTask(widget.task!.id);
      if (mounted) {
        setState(() {
          _dirty = false;
          _busy = false;
        });
        await SweetAlert.success(
          context,
          title: 'Task Deleted',
          message: 'The task has been permanently deleted.',
          confirmText: 'OK',
          autoCloseDuration: const Duration(milliseconds: 1800),
        );
        if (mounted) {
          Navigator.pop(context);
        }
      }
    } catch (e) {
      if (mounted) {
        setState(
          () => _error = e is ApiException ? e.message : 'Could not delete task.',
        );
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _pickDate(bool end) async {
    final current = end ? _endDate : _dueDate;
    final date = await showDatePicker(
      context: context,
      initialDate: current ?? DateTime.now(),
      firstDate: DateTime(1900),
      lastDate: DateTime(2200),
      builder: (context, child) => Theme(
        data: Theme.of(context).copyWith(
          colorScheme: ColorScheme.light(
            primary: brandPurple,
            onPrimary: Colors.white,
            surface: Colors.white,
            onSurface: const Color(0xFF1E293B),
          ),
        ),
        child: child!,
      ),
    );
    if (date != null && mounted && !_exitingSession) {
      HapticFeedback.selectionClick();
      setState(() {
        if (end) {
          _endDate = date;
        } else {
          _dueDate = date;
        }
        _dirty = true;
      });
    }
  }

  void _setQuickDueDate(int daysOffset) {
    HapticFeedback.selectionClick();
    final now = DateTime.now();
    final target = DateTime(now.year, now.month, now.day + daysOffset);
    setState(() {
      _dueDate = target;
      _dirty = true;
    });
  }

  Widget _sectionHeader(String title, {IconData? icon, Widget? trailing}) => Padding(
    padding: const EdgeInsets.only(left: 4, right: 4, bottom: 8, top: 22),
    child: Row(
      children: [
        if (icon != null) ...[
          Icon(icon, size: 14, color: const Color(0xFF64748B)),
          const SizedBox(width: 6),
        ],
        Text(
          title,
          style: const TextStyle(
            fontSize: 11.5,
            fontWeight: FontWeight.w700,
            color: Color(0xFF64748B),
            letterSpacing: 0.8,
          ),
        ),
        if (trailing != null) ...[
          const Spacer(),
          trailing,
        ],
      ],
    ),
  );

  Widget _customCard({required List<Widget> children, EdgeInsetsGeometry? padding}) => Container(
    width: double.infinity,
    padding: padding ?? const EdgeInsets.all(16),
    decoration: BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(20),
      border: Border.all(color: const Color(0xFFF0F2F8), width: 1.2),
      boxShadow: [
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.03),
          blurRadius: 10,
          offset: const Offset(0, 3),
        ),
      ],
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: children,
    ),
  );

  @override
  Widget build(BuildContext context) => PopScope(
    canPop: !_busy && !_dirty,
    onPopInvokedWithResult: (didPop, result) {
      if (!didPop) _back();
    },
    child: Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white.withValues(alpha: 0.85),
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        scrolledUnderElevation: 0,
        flexibleSpace: ClipRect(
          child: BackdropFilter(
            filter: ImageFilter.blur(sigmaX: 25, sigmaY: 25),
            child: Container(color: Colors.transparent),
          ),
        ),
        shape: const Border(
          bottom: BorderSide(color: Color(0xFFF1F5F9), width: 1.0),
        ),
        leading: TextButton(
          onPressed: _busy ? null : _back,
          child: const Text(
            'Cancel',
            style: TextStyle(
              color: Color(0xFF64748B),
              fontSize: 15,
              fontWeight: FontWeight.w500,
            ),
          ),
        ),
        leadingWidth: 76,
        title: Text(
          widget.task == null ? 'New Task' : 'Edit Task',
          style: const TextStyle(
            fontSize: 17,
            fontWeight: FontWeight.w800,
            color: Color(0xFF1E293B),
            letterSpacing: -0.2,
          ),
        ),
        centerTitle: true,
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 12),
            child: FilledButton(
              style: FilledButton.styleFrom(
                backgroundColor: brandPurple,
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                minimumSize: Size.zero,
                tapTargetSize: MaterialTapTargetSize.shrinkWrap,
              ),
              onPressed: _busy ? null : _save,
              child: _busy
                  ? const SizedBox(
                      width: 14,
                      height: 14,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        valueColor: AlwaysStoppedAnimation(Colors.white),
                      ),
                    )
                  : const Text(
                      'Done',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
            ),
          ),
        ],
      ),
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 640),
            child: Form(
              key: _form,
              child: SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(16, 14, 16, 40),
                child: AbsorbPointer(
                  absorbing: _busy,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // 1. Task Title & Notes Hero Card
                      _buildHeroCard(),

                      // 2. Priority Level Selector (1-Tap Chips)
                      _sectionHeader('PRIORITY LEVEL', icon: Icons.flag_rounded),
                      _buildPrioritySelector(),

                      // 3. Status Selector (1-Tap Chips)
                      _sectionHeader('TASK STATUS', icon: Icons.pending_actions_rounded),
                      _buildStatusSelector(),

                      // 4. Schedule & Dates
                      _sectionHeader('SCHEDULE & TIMELINE', icon: Icons.calendar_month_rounded),
                      _buildScheduleCard(),

                      // 5. Category Organization
                      _sectionHeader('CATEGORY', icon: Icons.category_rounded),
                      _buildCategoryCard(),

                      // 6. Interactive Tags
                      _sectionHeader('TAGS', icon: Icons.tag_rounded),
                      _buildTagsCard(),

                      // 7. Checklist / Subtasks
                      _sectionHeader(
                        'CHECKLIST & STEPS',
                        icon: Icons.checklist_rounded,
                        trailing: _subtasks.isNotEmpty
                            ? Text(
                                '${_subtasks.where((s) => s['completed'] == true).length}/${_subtasks.length} done',
                                style: const TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600,
                                  color: Color(0xFF64748B),
                                ),
                              )
                            : null,
                      ),
                      _buildChecklistCard(),

                      // 8. Options (Pin to Top)
                      _sectionHeader('PREFERENCES', icon: Icons.tune_rounded),
                      _buildPreferencesCard(),

                      // Error message if any
                      if (_error != null) ...[
                        const SizedBox(height: 16),
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                          decoration: BoxDecoration(
                            color: const Color(0xFFFEF2F2),
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: const Color(0xFFFCA5A5), width: 1.2),
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.error_outline_rounded, color: Color(0xFFDC2626), size: 20),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Text(
                                  _error!,
                                  style: const TextStyle(
                                    color: Color(0xFFB91C1C),
                                    fontSize: 13.5,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],

                      const SizedBox(height: 28),

                      // 9. Primary Action Button (Create / Save)
                      _buildSubmitButton(),

                      // 10. Delete Button (if editing existing task)
                      if (widget.task != null) ...[
                        const SizedBox(height: 12),
                        _buildDeleteButton(),
                      ],
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    ),
  );

  // 1. Hero Card: Title & Notes
  Widget _buildHeroCard() => _customCard(
    padding: const EdgeInsets.all(18),
    children: [
      Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            margin: const EdgeInsets.only(top: 2),
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: _priority == 'high'
                    ? [const Color(0xFFEF4444), const Color(0xFFDC2626)]
                    : _priority == 'low'
                        ? [const Color(0xFF10B981), const Color(0xFF059669)]
                        : [brandPurple, const Color(0xFF4F46E5)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(12),
              boxShadow: [
                BoxShadow(
                  color: (_priority == 'high' ? const Color(0xFFDC2626) : brandPurple)
                      .withValues(alpha: 0.25),
                  blurRadius: 8,
                  offset: const Offset(0, 3),
                ),
              ],
            ),
            child: const Icon(
              Icons.edit_note_rounded,
              color: Colors.white,
              size: 22,
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: TextFormField(
              controller: _title,
              maxLength: 255,
              textCapitalization: TextCapitalization.sentences,
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w700,
                color: Color(0xFF1E293B),
                letterSpacing: -0.3,
              ),
              decoration: const InputDecoration(
                hintText: 'What needs to get done?',
                hintStyle: TextStyle(
                  fontSize: 17,
                  fontWeight: FontWeight.w400,
                  color: Color(0xFF94A3B8),
                ),
                border: InputBorder.none,
                contentPadding: EdgeInsets.zero,
                counterText: '',
              ),
              validator: (v) => v == null || v.trim().isEmpty
                  ? 'Please give your task a title.'
                  : null,
            ),
          ),
        ],
      ),
      const SizedBox(height: 12),
      const Divider(height: 1, thickness: 1, color: Color(0xFFF1F5F9)),
      const SizedBox(height: 8),
      TextFormField(
        controller: _description,
        minLines: 2,
        maxLines: 6,
        style: const TextStyle(
          fontSize: 14.5,
          color: Color(0xFF334155),
          height: 1.4,
        ),
        decoration: const InputDecoration(
          hintText: 'Add notes, context, or links...',
          hintStyle: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w400,
            color: Color(0xFF94A3B8),
          ),
          border: InputBorder.none,
          contentPadding: EdgeInsets.symmetric(vertical: 4),
        ),
      ),
    ],
  );

  // 2. Priority Selector (1-Tap Segmented Chips)
  Widget _buildPrioritySelector() {
    final options = [
      (
        id: 'high',
        label: 'High',
        subtitle: 'Urgent',
        icon: Icons.local_fire_department_rounded,
        color: const Color(0xFFEF4444),
        activeBg: const Color(0xFFFEF2F2),
      ),
      (
        id: 'medium',
        label: 'Medium',
        subtitle: 'Standard',
        icon: Icons.flag_rounded,
        color: const Color(0xFFF59E0B),
        activeBg: const Color(0xFFFFFBEB),
      ),
      (
        id: 'low',
        label: 'Low',
        subtitle: 'Casual',
        icon: Icons.low_priority_rounded,
        color: const Color(0xFF10B981),
        activeBg: const Color(0xFFF0FDF4),
      ),
    ];

    return Row(
      children: options.map((opt) {
        final active = _priority == opt.id;
        return Expanded(
          child: Padding(
            padding: EdgeInsets.only(
              left: opt.id == 'high' ? 0 : 4,
              right: opt.id == 'low' ? 0 : 4,
            ),
            child: GestureDetector(
              onTap: () {
                HapticFeedback.selectionClick();
                setState(() {
                  _priority = opt.id;
                  _dirty = true;
                });
              },
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 180),
                padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
                decoration: BoxDecoration(
                  color: active ? opt.activeBg : Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(
                    color: active ? opt.color : const Color(0xFFF0F2F8),
                    width: active ? 1.8 : 1.2,
                  ),
                  boxShadow: [
                    BoxShadow(
                      color: active
                          ? opt.color.withValues(alpha: 0.15)
                          : Colors.black.withValues(alpha: 0.02),
                      blurRadius: active ? 8 : 4,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: Column(
                  children: [
                    Icon(opt.icon, color: active ? opt.color : const Color(0xFF94A3B8), size: 22),
                    const SizedBox(height: 6),
                    Text(
                      opt.label,
                      style: TextStyle(
                        fontSize: 13.5,
                        fontWeight: active ? FontWeight.w800 : FontWeight.w600,
                        color: active ? opt.color : const Color(0xFF475569),
                      ),
                    ),
                    Text(
                      opt.subtitle,
                      style: TextStyle(
                        fontSize: 10.5,
                        color: active ? opt.color.withValues(alpha: 0.8) : const Color(0xFF94A3B8),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        );
      }).toList(),
    );
  }

  // 3. Status Selector (Animated Sliding Pill)
  Widget _buildStatusSelector() {
    return AnimatedTaskStatusSelector(
      selectedStatus: _status,
      onChanged: (newStatus) {
        setState(() {
          _status = newStatus;
          _dirty = true;
        });
      },
    );
  }

  // 4. Schedule & Dates Card
  Widget _buildScheduleCard() => _customCard(
    padding: const EdgeInsets.all(16),
    children: [
      // Quick Due Date Shortcuts
      Row(
        children: [
          const Text(
            'Quick set:',
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: Color(0xFF94A3B8),
            ),
          ),
          const SizedBox(width: 8),
          _quickDateChip('Today', () => _setQuickDueDate(0)),
          const SizedBox(width: 6),
          _quickDateChip('Tomorrow', () => _setQuickDueDate(1)),
          const SizedBox(width: 6),
          _quickDateChip('+3 Days', () => _setQuickDueDate(3)),
          if (_dueDate != null) ...[
            const Spacer(),
            GestureDetector(
              onTap: () {
                HapticFeedback.selectionClick();
                setState(() {
                  _dueDate = null;
                  _dirty = true;
                });
              },
              child: const Text(
                'Clear',
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: Color(0xFFEF4444),
                ),
              ),
            ),
          ],
        ],
      ),
      const SizedBox(height: 14),
      const Divider(height: 1, thickness: 1, color: Color(0xFFF1F5F9)),
      const SizedBox(height: 12),

      // Due Date Tile
      _dateActionRow(
        title: 'Due Date',
        subtitle: _getRelativeDateText(_dueDate),
        date: _dueDate,
        icon: Icons.calendar_today_rounded,
        iconColor: const Color(0xFF0284C7),
        iconBg: const Color(0xFFE0F2FE),
        onTap: () => _pickDate(false),
        onClear: () => setState(() {
          _dueDate = null;
          _dirty = true;
        }),
      ),

      const SizedBox(height: 12),
      const Divider(height: 1, thickness: 1, color: Color(0xFFF1F5F9)),
      const SizedBox(height: 12),

      // End Date Tile
      _dateActionRow(
        title: 'End Date (Optional)',
        subtitle: _getRelativeDateText(_endDate),
        date: _endDate,
        icon: Icons.event_available_rounded,
        iconColor: const Color(0xFF059669),
        iconBg: const Color(0xFFD1FAE5),
        onTap: () => _pickDate(true),
        onClear: () => setState(() {
          _endDate = null;
          _dirty = true;
        }),
      ),
    ],
  );

  Widget _quickDateChip(String label, VoidCallback onTap) => GestureDetector(
    onTap: onTap,
    child: Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: const Color(0xFFF1F5F9),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Text(
        label,
        style: const TextStyle(
          fontSize: 11.5,
          fontWeight: FontWeight.w600,
          color: Color(0xFF475569),
        ),
      ),
    ),
  );

  Widget _dateActionRow({
    required String title,
    required String subtitle,
    required DateTime? date,
    required IconData icon,
    required Color iconColor,
    required Color iconBg,
    required VoidCallback onTap,
    required VoidCallback onClear,
  }) => InkWell(
    onTap: onTap,
    borderRadius: BorderRadius.circular(12),
    child: Padding(
      padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 2),
      child: Row(
        children: [
          Container(
            width: 36,
            height: 36,
            decoration: BoxDecoration(
              color: iconBg,
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, color: iconColor, size: 18),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 14.5,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFF1E293B),
                  ),
                ),
                Text(
                  subtitle,
                  style: TextStyle(
                    fontSize: 12,
                    color: date == null ? const Color(0xFF94A3B8) : brandPurple,
                    fontWeight: date == null ? FontWeight.w400 : FontWeight.w600,
                  ),
                ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            decoration: BoxDecoration(
              color: date == null ? const Color(0xFFF8FAFC) : brandPurpleLight,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(
                color: date == null ? const Color(0xFFE2E8F0) : brandPurple.withValues(alpha: 0.3),
              ),
            ),
            child: Text(
              date == null ? 'Set Date' : DateFormat('MMM d, yyyy').format(date),
              style: TextStyle(
                fontSize: 12.5,
                fontWeight: FontWeight.w700,
                color: date == null ? const Color(0xFF64748B) : brandPurple,
              ),
            ),
          ),
          if (date != null) ...[
            const SizedBox(width: 6),
            GestureDetector(
              onTap: onClear,
              child: Container(
                padding: const EdgeInsets.all(4),
                decoration: const BoxDecoration(
                  color: Color(0xFFF1F5F9),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.close_rounded, size: 16, color: Color(0xFF64748B)),
              ),
            ),
          ],
        ],
      ),
    ),
  );

  String _getRelativeDateText(DateTime? date) {
    if (date == null) return 'Not scheduled';
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final target = DateTime(date.year, date.month, date.day);
    final diff = target.difference(today).inDays;
    if (diff == 0) return 'Scheduled for Today';
    if (diff == 1) return 'Scheduled for Tomorrow';
    if (diff == -1) return 'Overdue by 1 day';
    if (diff < -1) return 'Overdue by ${diff.abs()} days';
    if (diff < 7) return 'Due in $diff days';
    return DateFormat('EEEE, MMM d').format(date);
  }

  // 5. Category Organization Card
  Widget _buildCategoryCard() => _customCard(
    padding: const EdgeInsets.all(16),
    children: [
      Wrap(
        spacing: 8,
        runSpacing: 8,
        children: _presetCategories.map((cat) {
          final isSelected = _category.text.trim().toLowerCase() == cat.name.toLowerCase();
          return GestureDetector(
            onTap: () {
              HapticFeedback.selectionClick();
              setState(() {
                if (isSelected) {
                  _category.clear();
                } else {
                  _category.text = cat.name;
                }
                _dirty = true;
              });
            },
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 160),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
              decoration: BoxDecoration(
                color: isSelected ? cat.color : const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: isSelected ? cat.color : const Color(0xFFE2E8F0),
                ),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(
                    cat.icon,
                    size: 15,
                    color: isSelected ? Colors.white : cat.color,
                  ),
                  const SizedBox(width: 6),
                  Text(
                    cat.name,
                    style: TextStyle(
                      fontSize: 12.5,
                      fontWeight: isSelected ? FontWeight.w700 : FontWeight.w600,
                      color: isSelected ? Colors.white : const Color(0xFF334155),
                    ),
                  ),
                ],
              ),
            ),
          );
        }).toList(),
      ),
      const SizedBox(height: 12),
      TextFormField(
        controller: _category,
        maxLength: 50,
        style: const TextStyle(
          fontSize: 14,
          fontWeight: FontWeight.w600,
          color: Color(0xFF1E293B),
        ),
        decoration: InputDecoration(
          hintText: 'Or type custom category...',
          hintStyle: const TextStyle(fontSize: 13, color: Color(0xFF94A3B8)),
          prefixIcon: const Icon(Icons.edit_outlined, size: 16, color: Color(0xFF94A3B8)),
          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
          filled: true,
          fillColor: const Color(0xFFF8FAFC),
          counterText: '',
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: const BorderSide(color: brandPurple, width: 1.5),
          ),
        ),
      ),
    ],
  );

  // 6. Interactive Tags Card
  Widget _buildTagsCard() => _customCard(
    padding: const EdgeInsets.all(16),
    children: [
      // Display current active tags
      if (_tagList.isNotEmpty) ...[
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: _tagList.map((tag) => Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
            decoration: BoxDecoration(
              color: brandPurpleLight,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: brandPurple.withValues(alpha: 0.2)),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  '#$tag',
                  style: const TextStyle(
                    fontSize: 12.5,
                    fontWeight: FontWeight.w700,
                    color: brandPurple,
                  ),
                ),
                const SizedBox(width: 6),
                GestureDetector(
                  onTap: () => _removeTag(tag),
                  child: const Icon(Icons.close_rounded, size: 14, color: brandPurple),
                ),
              ],
            ),
          )).toList(),
        ),
        const SizedBox(height: 12),
      ],

      // Add tag input row
      Row(
        children: [
          Expanded(
            child: TextField(
              controller: _tagInput,
              onSubmitted: (_) => _addTagFromInput(),
              style: const TextStyle(fontSize: 13.5),
              decoration: InputDecoration(
                hintText: 'Add a tag (e.g. mobile, design)...',
                hintStyle: const TextStyle(fontSize: 13, color: Color(0xFF94A3B8)),
                prefixIcon: const Icon(Icons.tag_rounded, size: 18, color: Color(0xFF94A3B8)),
                contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                filled: true,
                fillColor: const Color(0xFFF8FAFC),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                ),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: const BorderSide(color: brandPurple, width: 1.5),
                ),
              ),
            ),
          ),
          const SizedBox(width: 8),
          IconButton.filled(
            style: IconButton.styleFrom(
              backgroundColor: brandPurple,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            onPressed: _addTagFromInput,
            icon: const Icon(Icons.add_rounded, color: Colors.white, size: 20),
            tooltip: 'Add Tag',
          ),
        ],
      ),

      const SizedBox(height: 10),
      // Suggested Tags Quick Add
      Wrap(
        spacing: 6,
        runSpacing: 6,
        children: _suggestedTags
            .where((t) => !_tagList.contains(t))
            .map((sTag) => GestureDetector(
                  onTap: () => _addTag(sTag),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF1F5F9),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      '+ #$sTag',
                      style: const TextStyle(
                        fontSize: 11.5,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF64748B),
                      ),
                    ),
                  ),
                ))
            .toList(),
      ),
    ],
  );

  // 7. Checklist / Subtasks Card with Live Progress Bar
  Widget _buildChecklistCard() {
    final total = _subtasks.length;
    final done = _subtasks.where((s) => s['completed'] == true).length;
    final allDone = total > 0 && done == total;
    final progress = total == 0 ? 0.0 : done / total;
    const stepSuggestions = [
      'Draft outline',
      'Research & Plan',
      'Design mockup',
      'Code implementation',
      'Review & polish',
    ];

    return _customCard(
      padding: const EdgeInsets.all(16),
      children: [
        if (total > 0) ...[
          Row(
            children: [
              Expanded(
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(6),
                  child: LinearProgressIndicator(
                    value: progress,
                    minHeight: 6,
                    backgroundColor: const Color(0xFFE2E8F0),
                    valueColor: AlwaysStoppedAnimation(
                      allDone ? const Color(0xFF10B981) : brandPurple,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Text(
                '$done/$total done',
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: allDone ? const Color(0xFF10B981) : const Color(0xFF64748B),
                ),
              ),
            ],
          ),
          if (allDone) ...[
            const SizedBox(height: 10),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                color: const Color(0xFFECFDF5),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: const Color(0xFFA7F3D0)),
              ),
              child: Row(
                children: const [
                  Icon(Icons.check_circle_rounded, size: 16, color: Color(0xFF10B981)),
                  SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'All steps completed! You can mark the status as Done.',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF059669),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
          const SizedBox(height: 14),
        ],

        // Subtask list
        if (_subtasks.isEmpty)
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 8),
            child: Text(
              'No subtasks yet. Break this task into clear, actionable steps.',
              style: TextStyle(
                fontSize: 13,
                color: Color(0xFF94A3B8),
              ),
            ),
          )
        else
          for (var i = 0; i < _subtasks.length; i++) ...[
            Builder(
              builder: (context) {
                final isStepDone = _subtasks[i]['completed'] == true;
                return Container(
                  margin: const EdgeInsets.only(bottom: 8),
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  decoration: BoxDecoration(
                    color: isStepDone ? const Color(0xFFF0FDF4) : const Color(0xFFF8FAFC),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: isStepDone ? const Color(0xFFD1FAE5) : const Color(0xFFF1F5F9),
                    ),
                  ),
                  child: Row(
                    children: [
                      // Step Number Badge
                      Container(
                        width: 20,
                        height: 20,
                        decoration: BoxDecoration(
                          color: isStepDone ? const Color(0xFFECFDF5) : const Color(0xFFE2E8F0),
                          shape: BoxShape.circle,
                        ),
                        child: Center(
                          child: Text(
                            '${i + 1}',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              color: isStepDone ? const Color(0xFF10B981) : const Color(0xFF64748B),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 6),
                      TaskSparkleBurst(
                        trigger: isStepDone,
                        color: const Color(0xFF10B981),
                        child: Checkbox(
                          value: isStepDone,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
                          activeColor: const Color(0xFF10B981),
                          onChanged: (v) {
                            HapticFeedback.lightImpact();
                            setState(() {
                              _subtasks[i]['completed'] = v;
                              _dirty = true;
                            });
                          },
                        ),
                      ),
                      Expanded(
                        child: Text(
                          '${_subtasks[i]['title'] ?? ''}',
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w500,
                            decoration: isStepDone
                                ? TextDecoration.lineThrough
                                : null,
                            color: isStepDone
                                ? const Color(0xFF94A3B8)
                                : const Color(0xFF1E293B),
                          ),
                        ),
                      ),
                      IconButton(
                        tooltip: 'Remove step',
                        icon: const Icon(
                          Icons.close_rounded,
                          size: 18,
                          color: Color(0xFF94A3B8),
                        ),
                        onPressed: () {
                          HapticFeedback.selectionClick();
                          setState(() {
                            _subtasks.removeAt(i);
                            _dirty = true;
                          });
                        },
                      ),
                    ],
                  ),
                );
              },
            ),
          ],

        const SizedBox(height: 8),

        // Quick Suggestion Chips for Fast Step Creation
        Wrap(
          spacing: 6,
          runSpacing: 6,
          children: stepSuggestions.map((sug) {
            return InkWell(
              borderRadius: BorderRadius.circular(8),
              onTap: () {
                HapticFeedback.lightImpact();
                setState(() {
                  _subtasks.add({
                    'id': 'mobile-${DateTime.now().microsecondsSinceEpoch}',
                    'title': sug,
                    'completed': false,
                  });
                  _dirty = true;
                });
              },
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: const Color(0xFFF1F5F9),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.add_rounded, size: 13, color: Color(0xFF64748B)),
                    const SizedBox(width: 4),
                    Text(
                      sug,
                      style: const TextStyle(
                        fontSize: 11.5,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF475569),
                      ),
                    ),
                  ],
                ),
              ),
            );
          }).toList(),
        ),

        const SizedBox(height: 10),

        // Add subtask input field
        Row(
          children: [
            Expanded(
              child: TextField(
                controller: _subtask,
                onSubmitted: (_) => _addSubtask(),
                textCapitalization: TextCapitalization.sentences,
                style: const TextStyle(fontSize: 14),
                decoration: InputDecoration(
                  hintText: 'Add a subtask step...',
                  hintStyle: const TextStyle(fontSize: 13.5, color: Color(0xFF94A3B8)),
                  prefixIcon: const Icon(Icons.add_task_rounded, size: 18, color: Color(0xFF64748B)),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  filled: true,
                  fillColor: const Color(0xFFF8FAFC),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(color: brandPurple, width: 1.5),
                  ),
                ),
              ),
            ),
            const SizedBox(width: 8),
            IconButton.filled(
              style: IconButton.styleFrom(
                backgroundColor: const Color(0xFF1E293B),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              onPressed: _addSubtask,
              icon: const Icon(Icons.arrow_upward_rounded, color: Colors.white, size: 18),
              tooltip: 'Add step',
            ),
          ],
        ),
      ],
    );
  }

  // 8. Preferences Card (Pin to Top)
  Widget _buildPreferencesCard() => _customCard(
    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
    children: [
      SwitchListTile.adaptive(
        contentPadding: EdgeInsets.zero,
        secondary: Container(
          width: 36,
          height: 36,
          decoration: BoxDecoration(
            color: const Color(0xFFFEF3C7),
            borderRadius: BorderRadius.circular(10),
          ),
          child: const Icon(Icons.push_pin_rounded, color: Color(0xFFD97706), size: 18),
        ),
        title: const Text(
          'Pin this task',
          style: TextStyle(
            fontSize: 15,
            fontWeight: FontWeight.w700,
            color: Color(0xFF1E293B),
          ),
        ),
        subtitle: const Text(
          'Keeps this task at the top of your lists',
          style: TextStyle(
            fontSize: 12,
            color: Color(0xFF64748B),
          ),
        ),
        activeTrackColor: brandPurple,
        value: _pinned,
        onChanged: (v) {
          HapticFeedback.selectionClick();
          setState(() {
            _pinned = v;
            _dirty = true;
          });
        },
      ),
    ],
  );

  // 9. Primary Action Button
  Widget _buildSubmitButton() => Container(
    width: double.infinity,
    height: 52,
    decoration: BoxDecoration(
      gradient: const LinearGradient(
        colors: [Color(0xFF6366F1), Color(0xFF4F46E5)],
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
      ),
      borderRadius: BorderRadius.circular(16),
      boxShadow: [
        BoxShadow(
          color: const Color(0xFF4F46E5).withValues(alpha: 0.3),
          blurRadius: 14,
          offset: const Offset(0, 5),
        ),
      ],
    ),
    child: ElevatedButton.icon(
      style: ElevatedButton.styleFrom(
        backgroundColor: Colors.transparent,
        shadowColor: Colors.transparent,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      ),
      onPressed: _busy ? null : _save,
      icon: _busy
          ? const SizedBox(
              width: 18,
              height: 18,
              child: CircularProgressIndicator(
                strokeWidth: 2,
                valueColor: AlwaysStoppedAnimation(Colors.white),
              ),
            )
          : const Icon(Icons.check_circle_rounded, color: Colors.white, size: 20),
      label: Text(
        _busy
            ? 'Saving...'
            : (widget.task == null ? 'Create Task' : 'Done'),
        style: const TextStyle(
          color: Colors.white,
          fontSize: 16,
          fontWeight: FontWeight.w800,
          letterSpacing: 0.2,
        ),
      ),
    ),
  );

  // 10. Delete Button
  Widget _buildDeleteButton() => SizedBox(
    width: double.infinity,
    height: 48,
    child: OutlinedButton.icon(
      style: OutlinedButton.styleFrom(
        foregroundColor: const Color(0xFFEF4444),
        side: const BorderSide(color: Color(0xFFFCA5A5), width: 1.2),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      ),
      onPressed: _busy ? null : _delete,
      icon: const Icon(Icons.delete_outline_rounded, size: 18),
      label: const Text(
        'Delete Task',
        style: TextStyle(
          fontSize: 15,
          fontWeight: FontWeight.w700,
        ),
      ),
    ),
  );
}
