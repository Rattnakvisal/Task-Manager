import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'motion.dart';

/// Style and configuration for task statuses: 'pending' (Todo), 'in_progress', and 'completed' (Done).
class TaskStatusStyle {
  const TaskStatusStyle({
    required this.id,
    required this.label,
    required this.icon,
    required this.color,
    required this.bgColor,
    required this.borderColor,
    required this.glowColor,
  });

  final String id;
  final String label;
  final IconData icon;
  final Color color;
  final Color bgColor;
  final Color borderColor;
  final Color glowColor;

  static const pending = TaskStatusStyle(
    id: 'pending',
    label: 'To do',
    icon: Icons.radio_button_unchecked_rounded,
    color: Color(0xFF3864ED),
    bgColor: Color(0xFFEFF6FF),
    borderColor: Color(0xFFBFDBFE),
    glowColor: Color(0x333864ED),
  );

  static const inProgress = TaskStatusStyle(
    id: 'in_progress',
    label: 'In Progress',
    icon: Icons.play_arrow_rounded,
    color: Color(0xFF7C3AED),
    bgColor: Color(0xFFF5F3FF),
    borderColor: Color(0xFFDDD6FE),
    glowColor: Color(0x337C3AED),
  );

  static const done = TaskStatusStyle(
    id: 'completed',
    label: 'Done',
    icon: Icons.check_circle_rounded,
    color: Color(0xFF10B981),
    bgColor: Color(0xFFECFDF5),
    borderColor: Color(0xFFA7F3D0),
    glowColor: Color(0x3310B981),
  );

  static TaskStatusStyle of(String status) => switch (status) {
        'in_progress' => inProgress,
        'completed' => done,
        _ => pending,
      };

  static const List<TaskStatusStyle> all = [pending, inProgress, done];
}

/// Breathing pulse animation widget for "In Progress" states.
class InProgressBreathingPulse extends StatefulWidget {
  const InProgressBreathingPulse({
    super.key,
    required this.child,
    this.glowColor = const Color(0xFF7C3AED),
    this.maxScale = 1.08,
  });

  final Widget child;
  final Color glowColor;
  final double maxScale;

  @override
  State<InProgressBreathingPulse> createState() => _InProgressBreathingPulseState();
}

class _InProgressBreathingPulseState extends State<InProgressBreathingPulse>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1400),
    );
    if (!isRunningInTest) {
      _controller.repeat(reverse: true);
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (MediaQuery.disableAnimationsOf(context) || isRunningInTest) {
      return widget.child;
    }

    return AnimatedBuilder(
      animation: _controller,
      builder: (context, child) {
        final curved = Curves.easeInOutSine.transform(_controller.value);
        final scale = 1.0 + ((widget.maxScale - 1.0) * curved);
        final alpha = (0.15 + (0.35 * curved)).clamp(0.0, 1.0);

        return Container(
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            boxShadow: [
              BoxShadow(
                color: widget.glowColor.withValues(alpha: alpha),
                blurRadius: 10 + (6 * curved),
                spreadRadius: 1 + (2 * curved),
              ),
            ],
          ),
          child: Transform.scale(
            scale: scale,
            child: child,
          ),
        );
      },
      child: widget.child,
    );
  }
}

/// Rotating progress ring for "In Progress" activities.
class RotatingRingIcon extends StatefulWidget {
  const RotatingRingIcon({
    super.key,
    this.size = 18,
    this.color = const Color(0xFF7C3AED),
  });

  final double size;
  final Color color;

  @override
  State<RotatingRingIcon> createState() => _RotatingRingIconState();
}

class _RotatingRingIconState extends State<RotatingRingIcon>
    with SingleTickerProviderStateMixin {
  late final AnimationController _spin;

  @override
  void initState() {
    super.initState();
    _spin = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2000),
    );
    if (!isRunningInTest) {
      _spin.repeat();
    }
  }

  @override
  void dispose() {
    _spin.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (MediaQuery.disableAnimationsOf(context) || isRunningInTest) {
      return Icon(Icons.timelapse_rounded, size: widget.size, color: widget.color);
    }

    return AnimatedBuilder(
      animation: _spin,
      builder: (context, child) {
        return Transform.rotate(
          angle: _spin.value * 2 * math.pi,
          child: child,
        );
      },
      child: Icon(Icons.timelapse_rounded, size: widget.size, color: widget.color),
    );
  }
}

/// Dynamic Animated Status Badge:
/// Displays the current task status with rich micro-animations:
/// - Todo: Clean crisp circle
/// - In Progress: Breathing pulse + rotating ring
/// - Done: Checkmark bounce + sparkle burst
///
/// Tapping it either switches to the next status or opens a bottom sheet selector.
class InteractiveTaskStatusBadge extends StatelessWidget {
  const InteractiveTaskStatusBadge({
    super.key,
    required this.status,
    this.onTap,
    this.dense = false,
  });

  final String status;
  final VoidCallback? onTap;
  final bool dense;

  @override
  Widget build(BuildContext context) {
    final style = TaskStatusStyle.of(status);
    final isInProgress = status == 'in_progress';
    final isDone = status == 'completed';

    Widget iconWidget;
    if (isInProgress) {
      iconWidget = InProgressBreathingPulse(
        glowColor: style.color,
        maxScale: 1.12,
        child: Container(
          width: dense ? 16 : 18,
          height: dense ? 16 : 18,
          decoration: BoxDecoration(
            color: style.color,
            shape: BoxShape.circle,
          ),
          child: const Center(
            child: Icon(
              Icons.play_arrow_rounded,
              size: 12,
              color: Colors.white,
            ),
          ),
        ),
      );
    } else if (isDone) {
      iconWidget = TaskSparkleBurst(
        trigger: true,
        color: style.color,
        child: Container(
          width: dense ? 16 : 18,
          height: dense ? 16 : 18,
          decoration: BoxDecoration(
            color: style.color,
            shape: BoxShape.circle,
          ),
          child: const Center(
            child: Icon(
              Icons.check_rounded,
              size: 12,
              color: Colors.white,
            ),
          ),
        ),
      );
    } else {
      iconWidget = Container(
        width: dense ? 16 : 18,
        height: dense ? 16 : 18,
        decoration: BoxDecoration(
          color: Colors.white,
          shape: BoxShape.circle,
          border: Border.all(color: style.color, width: 2),
        ),
      );
    }

    final badgeContent = AnimatedContainer(
      duration: const Duration(milliseconds: 220),
      curve: Curves.easeOutCubic,
      padding: EdgeInsets.symmetric(
        horizontal: dense ? 8 : 10,
        vertical: dense ? 4 : 5.5,
      ),
      decoration: BoxDecoration(
        color: style.bgColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: style.borderColor,
          width: 1.2,
        ),
        boxShadow: isInProgress
            ? [
                BoxShadow(
                  color: style.color.withValues(alpha: 0.16),
                  blurRadius: 8,
                  offset: const Offset(0, 2),
                ),
              ]
            : null,
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          iconWidget,
          SizedBox(width: dense ? 5 : 6.5),
          AnimatedDefaultTextStyle(
            duration: const Duration(milliseconds: 200),
            style: TextStyle(
              fontSize: dense ? 11 : 12,
              fontWeight: FontWeight.w700,
              color: style.color,
              letterSpacing: -0.1,
            ),
            child: Text(style.label),
          ),
          if (onTap != null) ...[
            const SizedBox(width: 3),
            Icon(
              Icons.keyboard_arrow_down_rounded,
              size: dense ? 13 : 14,
              color: style.color.withValues(alpha: 0.7),
            ),
          ],
        ],
      ),
    );

    if (onTap == null) return badgeContent;

    return BouncingInteractive(
      scaleFactor: 0.94,
      onTap: () {
        HapticFeedback.selectionClick();
        onTap!();
      },
      child: badgeContent,
    );
  }
}

/// 3-State Sliding Pill Status Selector:
/// Seamlessly animates between Todo, In Progress, and Done with a spring sliding indicator,
/// dynamic colored background, glowing pulse for In Progress, and checkmark bounce for Done.
class AnimatedTaskStatusSelector extends StatelessWidget {
  const AnimatedTaskStatusSelector({
    super.key,
    required this.selectedStatus,
    required this.onChanged,
  });

  final String selectedStatus;
  final ValueChanged<String> onChanged;

  @override
  Widget build(BuildContext context) {
    const statuses = TaskStatusStyle.all;
    final activeIndex = switch (selectedStatus) {
      'in_progress' => 1,
      'completed' => 2,
      _ => 0,
    };
    final activeStyle = statuses[activeIndex];

    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: const Color(0xFFF1F5F9),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0), width: 1.2),
      ),
      child: LayoutBuilder(
        builder: (context, constraints) {
          final itemWidth = (constraints.maxWidth - 8) / statuses.length;

          return Stack(
            children: [
              // Sliding active pill background with spring physics
              AnimatedPositioned(
                duration: motionDuration(context, 260),
                curve: Curves.easeOutBack,
                left: activeIndex * itemWidth,
                top: 0,
                bottom: 0,
                width: itemWidth,
                child: Container(
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: activeStyle.borderColor,
                      width: 1.5,
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: activeStyle.color.withValues(alpha: 0.18),
                        blurRadius: 10,
                        offset: const Offset(0, 3),
                      ),
                    ],
                  ),
                ),
              ),

              // 3 Buttons
              Row(
                children: List.generate(statuses.length, (index) {
                  final st = statuses[index];
                  final isActive = index == activeIndex;

                  Widget iconChild;
                  if (st.id == 'in_progress' && isActive) {
                    iconChild = InProgressBreathingPulse(
                      glowColor: st.color,
                      child: Icon(st.icon, size: 16, color: st.color),
                    );
                  } else if (st.id == 'completed' && isActive) {
                    iconChild = TaskSparkleBurst(
                      trigger: true,
                      color: st.color,
                      child: Icon(st.icon, size: 16, color: st.color),
                    );
                  } else {
                    iconChild = Icon(
                      st.icon,
                      size: 16,
                      color: isActive ? st.color : const Color(0xFF94A3B8),
                    );
                  }

                  return Expanded(
                    child: InkWell(
                      borderRadius: BorderRadius.circular(12),
                      onTap: () {
                        if (!isActive) {
                          HapticFeedback.selectionClick();
                          onChanged(st.id);
                        }
                      },
                      child: Padding(
                        padding: const EdgeInsets.symmetric(vertical: 10),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            iconChild,
                            const SizedBox(width: 6),
                            Flexible(
                              child: Text(
                                st.label,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  fontSize: 12.5,
                                  fontWeight: isActive
                                      ? FontWeight.w800
                                      : FontWeight.w600,
                                  color: isActive
                                      ? st.color
                                      : const Color(0xFF64748B),
                                  letterSpacing: -0.2,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  );
                }),
              ),
            ],
          );
        },
      ),
    );
  }
}

/// Interactive Checklist & Steps Accordion:
/// Designed for task cards to give users a delightful, easy-to-use checklist experience.
/// Includes:
/// - 1-tap squircle checkboxes with scale bounce and strikethrough animation
/// - Dynamic progress bar (changes to emerald when 100% complete)
/// - Step ratio summary (e.g. 2/4 Steps (50%))
/// - Expand/collapse accordion
/// - Quick add step field
class TaskChecklistSection extends StatefulWidget {
  const TaskChecklistSection({
    super.key,
    required this.subtasks,
    required this.onToggleSubtask,
    this.onAddSubtask,
    this.initiallyExpanded = true,
  });

  final List<Map<String, dynamic>> subtasks;
  final ValueChanged<int> onToggleSubtask;
  final ValueChanged<String>? onAddSubtask;
  final bool initiallyExpanded;

  @override
  State<TaskChecklistSection> createState() => _TaskChecklistSectionState();
}

class _TaskChecklistSectionState extends State<TaskChecklistSection> {
  late bool _expanded;
  final TextEditingController _newStepController = TextEditingController();
  bool _showAddInput = false;

  @override
  void initState() {
    super.initState();
    _expanded = widget.initiallyExpanded;
  }

  @override
  void dispose() {
    _newStepController.dispose();
    super.dispose();
  }

  void _submitNewStep() {
    final text = _newStepController.text.trim();
    if (text.isNotEmpty && widget.onAddSubtask != null) {
      widget.onAddSubtask!(text);
      _newStepController.clear();
      setState(() => _showAddInput = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (widget.subtasks.isEmpty) {
      return const SizedBox.shrink();
    }

    final total = widget.subtasks.length;
    final done = widget.subtasks.where((s) => s['completed'] == true).length;
    final allDone = total > 0 && done == total;
    final progress = total == 0 ? 0.0 : done / total;
    final percent = (progress * 100).round();

    return Container(
      margin: const EdgeInsets.only(top: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: allDone ? const Color(0xFFF0FDF4) : const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: allDone ? const Color(0xFFA7F3D0) : const Color(0xFFE2E8F0),
          width: 1.2,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header Row: Checklist Title, Progress, Expand Toggle
          InkWell(
            onTap: () {
              HapticFeedback.lightImpact();
              setState(() => _expanded = !_expanded);
            },
            borderRadius: BorderRadius.circular(10),
            child: Row(
              children: [
                Container(
                  width: 26,
                  height: 26,
                  decoration: BoxDecoration(
                    color: allDone
                        ? const Color(0xFF10B981)
                        : const Color(0xFF5B4DFF).withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(
                    allDone ? Icons.done_all_rounded : Icons.checklist_rounded,
                    size: 16,
                    color: allDone ? Colors.white : const Color(0xFF5B4DFF),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          const Text(
                            'Checklist & Steps',
                            style: TextStyle(
                              fontSize: 12.5,
                              fontWeight: FontWeight.w700,
                              color: Color(0xFF1E293B),
                              letterSpacing: -0.2,
                            ),
                          ),
                          const Spacer(),
                          Text(
                            '$done of $total ($percent%)',
                            style: TextStyle(
                              fontSize: 11.5,
                              fontWeight: FontWeight.w700,
                              color: allDone
                                  ? const Color(0xFF059669)
                                  : const Color(0xFF64748B),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 5),
                      // Smooth Progress Bar
                      ClipRRect(
                        borderRadius: BorderRadius.circular(6),
                        child: AnimatedTaskProgress(
                          value: progress,
                          height: 5,
                          color: allDone
                              ? const Color(0xFF10B981)
                              : const Color(0xFF5B4DFF),
                          backgroundColor: const Color(0xFFE2E8F0),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                AnimatedRotation(
                  turns: _expanded ? 0.5 : 0.0,
                  duration: const Duration(milliseconds: 200),
                  child: const Icon(
                    Icons.keyboard_arrow_down_rounded,
                    size: 20,
                    color: Color(0xFF94A3B8),
                  ),
                ),
              ],
            ),
          ),

          // Collapsible Steps List
          AnimatedCrossFade(
            firstChild: const SizedBox(width: double.infinity, height: 0),
            secondChild: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SizedBox(height: 10),
                const Divider(height: 1, color: Color(0xFFE2E8F0)),
                const SizedBox(height: 8),

                // Subtask Items
                ...List.generate(total, (index) {
                  final step = widget.subtasks[index];
                  final isStepDone = step['completed'] == true;
                  final title = '${step['title'] ?? ''}';

                  return Padding(
                    padding: const EdgeInsets.only(bottom: 6),
                    child: InkWell(
                      borderRadius: BorderRadius.circular(10),
                      onTap: () {
                        HapticFeedback.lightImpact();
                        widget.onToggleSubtask(index);
                      },
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 8,
                          vertical: 6,
                        ),
                        decoration: BoxDecoration(
                          color: isStepDone
                              ? Colors.white.withValues(alpha: 0.7)
                              : Colors.white,
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(
                            color: isStepDone
                                ? const Color(0xFFD1FAE5)
                                : const Color(0xFFF1F5F9),
                          ),
                        ),
                        child: Row(
                          children: [
                            // Step Number Chip
                            Container(
                              width: 18,
                              height: 18,
                              decoration: BoxDecoration(
                                color: isStepDone
                                    ? const Color(0xFFECFDF5)
                                    : const Color(0xFFF1F5F9),
                                shape: BoxShape.circle,
                              ),
                              child: Center(
                                child: Text(
                                  '${index + 1}',
                                  style: TextStyle(
                                    fontSize: 10,
                                    fontWeight: FontWeight.w700,
                                    color: isStepDone
                                        ? const Color(0xFF10B981)
                                        : const Color(0xFF64748B),
                                  ),
                                ),
                              ),
                            ),
                            const SizedBox(width: 8),

                            // Squircle Checkbox with scale bounce
                            TaskSparkleBurst(
                              trigger: isStepDone,
                              color: const Color(0xFF10B981),
                              child: AnimatedContainer(
                                duration: const Duration(milliseconds: 180),
                                width: 20,
                                height: 20,
                                decoration: BoxDecoration(
                                  color: isStepDone
                                      ? const Color(0xFF10B981)
                                      : Colors.white,
                                  borderRadius: BorderRadius.circular(6),
                                  border: isStepDone
                                      ? null
                                      : Border.all(
                                          color: const Color(0xFFCBD5E1),
                                          width: 1.6,
                                        ),
                                  boxShadow: isStepDone
                                      ? [
                                          BoxShadow(
                                            color: const Color(0xFF10B981)
                                                .withValues(alpha: 0.3),
                                            blurRadius: 4,
                                            offset: const Offset(0, 1),
                                          ),
                                        ]
                                      : null,
                                ),
                                child: isStepDone
                                    ? const Icon(
                                        Icons.check_rounded,
                                        size: 14,
                                        color: Colors.white,
                                      )
                                    : null,
                              ),
                            ),
                            const SizedBox(width: 10),

                            // Step Title Text with strikethrough animation
                            Expanded(
                              child: AnimatedDefaultTextStyle(
                                duration: const Duration(milliseconds: 180),
                                style: TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w600,
                                  color: isStepDone
                                      ? const Color(0xFF94A3B8)
                                      : const Color(0xFF1E293B),
                                  decoration: isStepDone
                                      ? TextDecoration.lineThrough
                                      : null,
                                  decorationColor: const Color(0xFF94A3B8),
                                ),
                                child: Text(title),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  );
                }),

                // Inline "Add Step" option if supported
                if (widget.onAddSubtask != null) ...[
                  if (_showAddInput) ...[
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Expanded(
                          child: Container(
                            height: 38,
                            padding: const EdgeInsets.symmetric(horizontal: 10),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(color: const Color(0xFF5B4DFF)),
                            ),
                            child: TextField(
                              controller: _newStepController,
                              autofocus: true,
                              textCapitalization: TextCapitalization.sentences,
                              style: const TextStyle(fontSize: 13),
                              decoration: const InputDecoration(
                                hintText: 'Enter step title...',
                                hintStyle: TextStyle(
                                  fontSize: 13,
                                  color: Color(0xFF94A3B8),
                                ),
                                border: InputBorder.none,
                                isDense: true,
                                contentPadding: EdgeInsets.symmetric(vertical: 8),
                              ),
                              onSubmitted: (_) => _submitNewStep(),
                            ),
                          ),
                        ),
                        const SizedBox(width: 6),
                        IconButton(
                          tooltip: 'Add Step',
                          style: IconButton.styleFrom(
                            backgroundColor: const Color(0xFF5B4DFF),
                            foregroundColor: Colors.white,
                            minimumSize: const Size(36, 36),
                            padding: EdgeInsets.zero,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(10),
                            ),
                          ),
                          icon: const Icon(Icons.check_rounded, size: 18),
                          onPressed: _submitNewStep,
                        ),
                        const SizedBox(width: 4),
                        IconButton(
                          tooltip: 'Cancel',
                          style: IconButton.styleFrom(
                            backgroundColor: const Color(0xFFF1F5F9),
                            foregroundColor: const Color(0xFF64748B),
                            minimumSize: const Size(36, 36),
                            padding: EdgeInsets.zero,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(10),
                            ),
                          ),
                          icon: const Icon(Icons.close_rounded, size: 18),
                          onPressed: () {
                            _newStepController.clear();
                            setState(() => _showAddInput = false);
                          },
                        ),
                      ],
                    ),
                  ] else ...[
                    const SizedBox(height: 2),
                    InkWell(
                      borderRadius: BorderRadius.circular(8),
                      onTap: () {
                        HapticFeedback.lightImpact();
                        setState(() => _showAddInput = true);
                      },
                      child: Padding(
                        padding: const EdgeInsets.symmetric(
                          vertical: 6,
                          horizontal: 4,
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: const [
                            Icon(
                              Icons.add_circle_outline_rounded,
                              size: 16,
                              color: Color(0xFF5B4DFF),
                            ),
                            SizedBox(width: 6),
                            Text(
                              'Add step',
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w700,
                                color: Color(0xFF5B4DFF),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ],
              ],
            ),
            crossFadeState: _expanded
                ? CrossFadeState.showSecond
                : CrossFadeState.showFirst,
            duration: const Duration(milliseconds: 220),
          ),
        ],
      ),
    );
  }
}

/// Helper modal sheet to quickly switch task status with animated options.
Future<String?> showTaskStatusPickerSheet(
  BuildContext context, {
  required String currentStatus,
}) {
  return showModalBottomSheet<String>(
    context: context,
    backgroundColor: Colors.white,
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
    ),
    builder: (ctx) {
      return SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
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
                'Change Task Status',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w800,
                  color: Color(0xFF1E293B),
                  letterSpacing: -0.3,
                ),
              ),
              const SizedBox(height: 6),
              const Text(
                'Select a progress state for this task',
                style: TextStyle(
                  fontSize: 13,
                  color: Color(0xFF64748B),
                ),
              ),
              const SizedBox(height: 16),
              ...TaskStatusStyle.all.map((st) {
                final isSelected = st.id == currentStatus;
                return Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: InkWell(
                    borderRadius: BorderRadius.circular(16),
                    onTap: () {
                      HapticFeedback.selectionClick();
                      Navigator.pop(ctx, st.id);
                    },
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 180),
                      padding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 14,
                      ),
                      decoration: BoxDecoration(
                        color: isSelected ? st.bgColor : const Color(0xFFF8FAFC),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(
                          color: isSelected
                              ? st.borderColor
                              : const Color(0xFFF1F5F9),
                          width: isSelected ? 1.6 : 1.2,
                        ),
                      ),
                      child: Row(
                        children: [
                          Container(
                            width: 36,
                            height: 36,
                            decoration: BoxDecoration(
                              color: st.bgColor,
                              shape: BoxShape.circle,
                              border: Border.all(
                                color: st.borderColor,
                                width: 1.5,
                              ),
                            ),
                            child: Icon(st.icon, size: 18, color: st.color),
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Text(
                              st.label,
                              style: TextStyle(
                                fontSize: 15,
                                fontWeight: isSelected
                                    ? FontWeight.w800
                                    : FontWeight.w600,
                                color: isSelected
                                    ? st.color
                                    : const Color(0xFF1E293B),
                              ),
                            ),
                          ),
                          if (isSelected)
                            Icon(
                              Icons.check_circle_rounded,
                              size: 20,
                              color: st.color,
                            ),
                        ],
                      ),
                    ),
                  ),
                );
              }),
            ],
          ),
        ),
      );
    },
  );
}
