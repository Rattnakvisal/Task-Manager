import 'dart:ui';

import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../api.dart';
import '../app.dart';
import '../app_state.dart';
import '../models.dart';
import '../widgets/motion.dart';

class TaskEditor extends StatefulWidget {
  const TaskEditor({super.key, required this.state, this.task});
  final AppState state;
  final Task? task;
  @override
  State<TaskEditor> createState() => _TaskEditorState();
}

class _TaskEditorState extends State<TaskEditor> {
  final _form = GlobalKey<FormState>();
  late final TextEditingController _title, _description, _category, _tags;
  final _subtask = TextEditingController();
  late String _priority, _status;
  late bool _pinned;
  late DateTime? _dueDate, _endDate;
  late List<Map<String, dynamic>> _subtasks;
  bool _busy = false, _dirty = false;
  bool _confirming = false, _exitingSession = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    final task = widget.task;
    _title = TextEditingController(text: task?.title ?? '');
    _description = TextEditingController(text: task?.description ?? '');
    _category = TextEditingController(text: task?.category ?? '');
    _tags = TextEditingController(text: task?.tags.join(', ') ?? '');
    _priority = task?.priority ?? 'medium';
    _status = task?.status ?? 'pending';
    _pinned = task?.isPinned ?? false;
    _dueDate = task?.dueDate;
    _endDate = task?.endDate;
    _subtasks =
        task?.subtasks.map((s) => Map<String, dynamic>.from(s)).toList() ?? [];
    for (final c in [_title, _description, _category, _tags, _subtask]) {
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
    for (final c in [_title, _description, _category, _tags, _subtask]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<bool> _confirm(String title, String message, String action) async =>
      await showDialog<bool>(
        context: context,
        builder: (context) => AlertDialog(
          title: Text(title),
          content: Text(message),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: Text(action),
            ),
          ],
        ),
      ) ??
      false;
  Future<void> _back() async {
    if (_busy || _confirming) return;
    _confirming = true;
    try {
      if (!_dirty ||
          await _confirm(
            'Discard changes?',
            'Your changes have not been saved.',
            'Discard',
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
    if (_subtask.text.trim().isEmpty) return;
    setState(() {
      _subtasks.add({
        'id': 'mobile-${DateTime.now().microsecondsSinceEpoch}',
        'title': _subtask.text.trim(),
        'completed': false,
      });
      _subtask.clear();
      _dirty = true;
    });
  }

  Future<void> _save() async {
    if (_busy || _exitingSession) return;
    if (!_form.currentState!.validate()) return;
    if (_dueDate != null && _endDate != null && _endDate!.isBefore(_dueDate!)) {
      setState(() => _error = 'End date must be on or after the due date.');
      return;
    }
    _addSubtask();
    FocusManager.instance.primaryFocus?.unfocus();
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
        'tags': _tags.text
            .split(',')
            .map((s) => s.trim())
            .where((s) => s.isNotEmpty)
            .toList(),
        'subtasks': _subtasks,
        'is_pinned': _pinned,
      }, id: widget.task?.id);
      if (mounted) {
        setState(() {
          _dirty = false;
          _busy = false;
        });
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              widget.task == null ? 'Task created.' : 'Task saved.',
            ),
          ),
        );
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
    final confirmed = await _confirm(
      'Delete task?',
      'This task and its subtasks will be deleted.',
      'Delete',
    );
    _confirming = false;
    if (!mounted || !confirmed || _exitingSession) return;
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
        Navigator.pop(context);
      }
    } catch (e) {
      if (mounted) {
        setState(
          () =>
              _error = e is ApiException ? e.message : 'Could not delete task.',
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
    );
    if (date != null && mounted && !_exitingSession) {
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

  Widget _iosSectionHeader(String title) => Padding(
    padding: const EdgeInsets.only(left: 16, bottom: 8, top: 22),
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

  Widget _iosCard(List<Widget> children) => Container(
    decoration: BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(16),
      border: Border.all(color: const Color(0x10000000), width: 0.5),
    ),
    child: ClipRRect(
      borderRadius: BorderRadius.circular(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: children,
      ),
    ),
  );

  @override
  Widget build(BuildContext context) => PopScope(
    canPop: !_busy && !_dirty,
    onPopInvokedWithResult: (didPop, result) {
      if (!didPop) _back();
    },
    child: Scaffold(
      backgroundColor: const Color(0xFFF2F2F7),
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
          bottom: BorderSide(
            color: Color(0x18000000),
            width: 0.5,
          ),
        ),
        leading: TextButton(
          onPressed: _busy ? null : _back,
          child: const Text(
            'Cancel',
            style: TextStyle(
              color: brandPurple,
              fontSize: 16,
              fontWeight: FontWeight.w400,
            ),
          ),
        ),
        leadingWidth: 80,
        title: Text(
          widget.task == null ? 'New Task' : 'Details',
          style: const TextStyle(
            fontSize: 17,
            fontWeight: FontWeight.w700,
            color: Color(0xFF1C1C1E),
            letterSpacing: -0.3,
          ),
        ),
        centerTitle: true,
        actions: [
          TextButton(
            onPressed: _busy ? null : _save,
            child: _busy
                ? const SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      valueColor: AlwaysStoppedAnimation(brandPurple),
                    ),
                  )
                : const Text(
                    'Done',
                    style: TextStyle(
                      color: brandPurple,
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 640),
            child: Form(
              key: _form,
              child: SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 40),
                child: AbsorbPointer(
                  absorbing: _busy,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _iosCard([
                        TextFormField(
                          controller: _title,
                          maxLength: 255,
                          textCapitalization: TextCapitalization.sentences,
                          style: const TextStyle(
                            fontSize: 17,
                            fontWeight: FontWeight.w600,
                            color: Color(0xFF1C1C1E),
                          ),
                          decoration: const InputDecoration(
                            hintText: 'What needs to get done?',
                            hintStyle: TextStyle(
                              fontSize: 17,
                              fontWeight: FontWeight.w400,
                              color: Color(0xFFC7C7CC),
                            ),
                            border: InputBorder.none,
                            contentPadding: EdgeInsets.symmetric(
                              horizontal: 16,
                              vertical: 14,
                            ),
                            counterText: '',
                          ),
                          validator: (v) => v == null || v.trim().isEmpty
                              ? 'Give your task a title.'
                              : null,
                        ),
                        const Divider(
                          height: 0.5,
                          thickness: 0.5,
                          indent: 16,
                          color: Color(0x1A000000),
                        ),
                        TextFormField(
                          controller: _description,
                          minLines: 3,
                          maxLines: 6,
                          style: const TextStyle(
                            fontSize: 15,
                            color: Color(0xFF1C1C1E),
                          ),
                          decoration: const InputDecoration(
                            hintText: 'Notes & details…',
                            hintStyle: TextStyle(
                              fontSize: 15,
                              color: Color(0xFFC7C7CC),
                            ),
                            border: InputBorder.none,
                            contentPadding: EdgeInsets.symmetric(
                              horizontal: 16,
                              vertical: 14,
                            ),
                          ),
                        ),
                      ]),

                      _iosSectionHeader('SCHEDULE & PRIORITY'),
                      _iosCard([
                        Padding(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 16,
                            vertical: 6,
                          ),
                          child: Row(
                            children: [
                              const Text(
                                'Priority',
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w500,
                                  color: Color(0xFF1C1C1E),
                                ),
                              ),
                              const Spacer(),
                              DropdownButtonHideUnderline(
                                child: DropdownButton<String>(
                                  value: _priority,
                                  style: const TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w600,
                                    color: brandPurple,
                                  ),
                                  items: const [
                                    DropdownMenuItem(
                                      value: 'low',
                                      child: Text('Low'),
                                    ),
                                    DropdownMenuItem(
                                      value: 'medium',
                                      child: Text('Medium'),
                                    ),
                                    DropdownMenuItem(
                                      value: 'high',
                                      child: Text('High'),
                                    ),
                                  ],
                                  onChanged: (v) => setState(() {
                                    _priority = v!;
                                    _dirty = true;
                                  }),
                                ),
                              ),
                            ],
                          ),
                        ),
                        const Divider(
                          height: 0.5,
                          thickness: 0.5,
                          indent: 16,
                          color: Color(0x1A000000),
                        ),
                        Padding(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 16,
                            vertical: 6,
                          ),
                          child: Row(
                            children: [
                              const Text(
                                'Status',
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w500,
                                  color: Color(0xFF1C1C1E),
                                ),
                              ),
                              const Spacer(),
                              DropdownButtonHideUnderline(
                                child: DropdownButton<String>(
                                  value: _status,
                                  style: const TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w600,
                                    color: brandPurple,
                                  ),
                                  items: const [
                                    DropdownMenuItem(
                                      value: 'pending',
                                      child: Text('To do'),
                                    ),
                                    DropdownMenuItem(
                                      value: 'in_progress',
                                      child: Text('In progress'),
                                    ),
                                    DropdownMenuItem(
                                      value: 'completed',
                                      child: Text('Completed'),
                                    ),
                                  ],
                                  onChanged: (v) => setState(() {
                                    _status = v!;
                                    _dirty = true;
                                  }),
                                ),
                              ),
                            ],
                          ),
                        ),
                        const Divider(
                          height: 0.5,
                          thickness: 0.5,
                          indent: 16,
                          color: Color(0x1A000000),
                        ),
                        _dateTile('Due date', _dueDate, false),
                        const Divider(
                          height: 0.5,
                          thickness: 0.5,
                          indent: 16,
                          color: Color(0x1A000000),
                        ),
                        _dateTile('End date', _endDate, true),
                      ]),

                      _iosSectionHeader('ORGANIZATION'),
                      _iosCard([
                        Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                          child: Row(
                            children: [
                              const Text(
                                'Category',
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w500,
                                  color: Color(0xFF1C1C1E),
                                ),
                              ),
                              const SizedBox(width: 16),
                              Expanded(
                                child: TextFormField(
                                  controller: _category,
                                  maxLength: 50,
                                  textAlign: TextAlign.end,
                                  style: const TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w500,
                                    color: brandPurple,
                                  ),
                                  decoration: const InputDecoration(
                                    hintText: 'Work, Personal…',
                                    hintStyle: TextStyle(
                                      fontSize: 16,
                                      color: Color(0xFFC7C7CC),
                                    ),
                                    border: InputBorder.none,
                                    contentPadding: EdgeInsets.symmetric(
                                      vertical: 12,
                                    ),
                                    counterText: '',
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                        const Divider(
                          height: 0.5,
                          thickness: 0.5,
                          indent: 16,
                          color: Color(0x1A000000),
                        ),
                        Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                          child: Row(
                            children: [
                              const Text(
                                'Tags',
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w500,
                                  color: Color(0xFF1C1C1E),
                                ),
                              ),
                              const SizedBox(width: 16),
                              Expanded(
                                child: TextFormField(
                                  controller: _tags,
                                  textAlign: TextAlign.end,
                                  style: const TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w500,
                                    color: brandPurple,
                                  ),
                                  decoration: const InputDecoration(
                                    hintText: 'design, planning',
                                    hintStyle: TextStyle(
                                      fontSize: 16,
                                      color: Color(0xFFC7C7CC),
                                    ),
                                    border: InputBorder.none,
                                    contentPadding: EdgeInsets.symmetric(
                                      vertical: 12,
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ]),

                      _iosSectionHeader('CHECKLIST'),
                      _iosCard([
                        for (var i = 0; i < _subtasks.length; i++) ...[
                          if (i > 0)
                            const Divider(
                              height: 0.5,
                              thickness: 0.5,
                              indent: 52,
                              color: Color(0x1A000000),
                            ),
                          Padding(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 4,
                            ),
                            child: Row(
                              children: [
                                Checkbox(
                                  value: _subtasks[i]['completed'] == true,
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(6),
                                  ),
                                  activeColor: brandPurple,
                                  onChanged: (v) => setState(() {
                                    _subtasks[i]['completed'] = v;
                                    _dirty = true;
                                  }),
                                ),
                                Expanded(
                                  child: Text(
                                    '${_subtasks[i]['title'] ?? ''}',
                                    style: TextStyle(
                                      fontSize: 15,
                                      decoration:
                                          _subtasks[i]['completed'] == true
                                              ? TextDecoration.lineThrough
                                              : null,
                                      color: _subtasks[i]['completed'] == true
                                          ? const Color(0xFF8E8E93)
                                          : const Color(0xFF1C1C1E),
                                    ),
                                  ),
                                ),
                                IconButton(
                                  tooltip: 'Remove subtask',
                                  onPressed: () => setState(() {
                                    _subtasks.removeAt(i);
                                    _dirty = true;
                                  }),
                                  icon: const Icon(
                                    Icons.remove_circle_outline_rounded,
                                    color: Color(0xFFFF3B30),
                                    size: 20,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                        if (_subtasks.isNotEmpty)
                          const Divider(
                            height: 0.5,
                            thickness: 0.5,
                            color: Color(0x1A000000),
                          ),
                        Padding(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 16,
                            vertical: 4,
                          ),
                          child: Row(
                            children: [
                              const Icon(
                                Icons.add_circle_outline_rounded,
                                color: brandPurple,
                                size: 22,
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: TextField(
                                  controller: _subtask,
                                  onSubmitted: (_) => _addSubtask(),
                                  textCapitalization:
                                      TextCapitalization.sentences,
                                  decoration: const InputDecoration(
                                    hintText: 'Add a step…',
                                    hintStyle: TextStyle(
                                      color: Color(0xFFC7C7CC),
                                      fontSize: 15,
                                    ),
                                    border: InputBorder.none,
                                  ),
                                ),
                              ),
                              IconButton(
                                onPressed: _addSubtask,
                                tooltip: 'Add subtask',
                                icon: const Icon(
                                  Icons.arrow_upward_rounded,
                                  color: brandPurple,
                                  size: 20,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ]),

                      _iosSectionHeader('OPTIONS'),
                      _iosCard([
                        SwitchListTile.adaptive(
                          contentPadding: const EdgeInsets.symmetric(
                            horizontal: 16,
                            vertical: 4,
                          ),
                          title: const Text(
                            'Pin this task',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w500,
                              color: Color(0xFF1C1C1E),
                            ),
                          ),
                          subtitle: const Text(
                            'Keep at the top of your list',
                            style: TextStyle(
                              fontSize: 13,
                              color: Color(0xFF8E8E93),
                            ),
                          ),
                          activeTrackColor: brandPurple,
                          value: _pinned,
                          onChanged: (v) => setState(() {
                            _pinned = v;
                            _dirty = true;
                          }),
                        ),
                      ]),

                      if (widget.task != null) ...[
                        const SizedBox(height: 24),
                        _iosCard([
                          Material(
                            color: Colors.transparent,
                            child: InkWell(
                              onTap: _busy ? null : _delete,
                              child: const Padding(
                                padding: EdgeInsets.symmetric(vertical: 14),
                                child: Center(
                                  child: Text(
                                    'Delete Task',
                                    style: TextStyle(
                                      fontSize: 16,
                                      fontWeight: FontWeight.w600,
                                      color: Color(0xFFFF3B30),
                                    ),
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ]),
                      ],

                      if (_error != null)
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          child: Text(
                            _error!,
                            style: TextStyle(
                              color: Theme.of(context).colorScheme.error,
                              fontSize: 14,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ),
                      const SizedBox(height: 20),
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

  Widget _dateTile(String label, DateTime? date, bool end) => ListTile(
    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 2),
    leading: Container(
      width: 32,
      height: 32,
      decoration: BoxDecoration(
        color: end ? const Color(0xFF34C759) : const Color(0xFF007AFF),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Icon(
        end ? Icons.event_available_rounded : Icons.calendar_today_rounded,
        color: Colors.white,
        size: 18,
      ),
    ),
    title: Text(
      label,
      style: const TextStyle(
        fontSize: 16,
        fontWeight: FontWeight.w500,
        color: Color(0xFF1C1C1E),
      ),
    ),
    trailing: Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
          decoration: BoxDecoration(
            color: const Color(0xFFF2F2F7),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Text(
            date == null ? 'Not set' : DateFormat('MMM d, yyyy').format(date),
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w600,
              color: date == null ? const Color(0xFF8E8E93) : brandPurple,
            ),
          ),
        ),
        if (date != null) ...[
          const SizedBox(width: 4),
          IconButton(
            tooltip: 'Clear $label',
            icon: const Icon(
              Icons.close_rounded,
              size: 18,
              color: Color(0xFF8E8E93),
            ),
            onPressed: () => setState(() {
              if (end) {
                _endDate = null;
              } else {
                _dueDate = null;
              }
              _dirty = true;
            }),
          ),
        ],
      ],
    ),
    onTap: () => _pickDate(end),
  );
}
