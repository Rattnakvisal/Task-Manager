import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:intl/intl.dart';

import '../app.dart';
import '../models.dart';
import 'motion.dart';
import 'task_status_widgets.dart';

Color priorityColor(String value) => switch (value) {
  'high' => const Color(0xFFEF4444),
  'medium' => const Color(0xFFD97706),
  _ => const Color(0xFF10B981),
};

Color priorityBgColor(String value) => switch (value) {
  'high' => const Color(0xFFFEE2E2),
  'medium' => const Color(0xFFFEF3C7),
  _ => const Color(0xFFD1FAE5),
};

Color categoryColor(String category) {
  final c = category.toLowerCase();
  if (c.contains('ui') || c.contains('ux') || c.contains('design')) {
    return const Color(0xFF7C3AED);
  } else if (c.contains('dev') ||
      c.contains('code') ||
      c.contains('feature') ||
      c.contains('tech')) {
    return const Color(0xFF2563EB);
  } else if (c.contains('doc') || c.contains('write')) {
    return const Color(0xFFD97706);
  } else if (c.contains('plan') || c.contains('strategy')) {
    return const Color(0xFF059669);
  }
  return const Color(0xFF64748B);
}

Color categoryBgColor(String category) {
  final c = category.toLowerCase();
  if (c.contains('ui') || c.contains('ux') || c.contains('design')) {
    return const Color(0xFFF5F3FF);
  } else if (c.contains('dev') ||
      c.contains('code') ||
      c.contains('feature') ||
      c.contains('tech')) {
    return const Color(0xFFEFF6FF);
  } else if (c.contains('doc') || c.contains('write')) {
    return const Color(0xFFFEF3C7);
  } else if (c.contains('plan') || c.contains('strategy')) {
    return const Color(0xFFECFDF5);
  }
  return const Color(0xFFF1F5F9);
}

IconData categoryIcon(String category) {
  final c = category.toLowerCase();
  if (c.contains('ui') || c.contains('ux') || c.contains('design')) {
    return Icons.local_offer_outlined;
  } else if (c.contains('dev') ||
      c.contains('code') ||
      c.contains('feature') ||
      c.contains('tech')) {
    return Icons.code_rounded;
  } else if (c.contains('doc') || c.contains('write')) {
    return Icons.description_outlined;
  } else if (c.contains('plan') || c.contains('strategy')) {
    return Icons.layers_outlined;
  }
  return Icons.local_offer_outlined;
}

class TaskCard extends StatelessWidget {
  const TaskCard({
    super.key,
    required this.task,
    required this.onTap,
    required this.onToggle,
    this.busy = false,
    this.onDelete,
    this.showLeftAccent = true,
    this.onToggleSubtask,
    this.onStatusChange,
    this.onAddSubtask,
  });

  final Task task;
  final VoidCallback onTap, onToggle;
  final bool busy;
  final VoidCallback? onDelete;
  final bool showLeftAccent;
  final ValueChanged<int>? onToggleSubtask;
  final ValueChanged<String>? onStatusChange;
  final ValueChanged<String>? onAddSubtask;

  @override
  Widget build(BuildContext context) {
    final isDone = task.completed;
    final today = dateOnly(DateTime.now());
    final isDueToday = task.dueDate != null && task.dueDate == today;
    final isDueTomorrow =
        task.dueDate != null && task.dueDate == today.add(const Duration(days: 1));

    final String dateText;
    if (task.dueDate != null &&
        task.endDate != null &&
        task.dueDate != task.endDate) {
      dateText =
          '${DateFormat('MMM d').format(task.dueDate!)} – ${DateFormat('MMM d').format(task.endDate!)}';
    } else if (task.dueDate != null) {
      if (isDueToday) {
        dateText = 'Today, ${DateFormat('MMM d').format(task.dueDate!)}';
      } else if (isDueTomorrow) {
        dateText = 'Tomorrow, ${DateFormat('MMM d').format(task.dueDate!)}';
      } else {
        dateText = DateFormat('MMM d').format(task.dueDate!);
      }
    } else {
      dateText = 'Today, ${DateFormat('MMM d').format(DateTime.now())}';
    }

    final catName = task.category.trim().isNotEmpty
        ? task.category.trim()
        : 'UI/UX';

    final catClr = categoryColor(catName);
    final catBg = categoryBgColor(catName);

    final Color leftAccentColor = isDone
        ? const Color(0xFF10B981)
        : task.priority == 'high'
            ? const Color(0xFFEF4444)
            : task.status == 'in_progress'
                ? brandPurple
                : const Color(0xFF3864ED);

    final Color checkFillColor = isDone
        ? const Color(0xFF10B981)
        : brandPurple;

    return Dismissible(
      key: ValueKey('task-dismiss-${task.id}'),
      direction: DismissDirection.horizontal,
      confirmDismiss: (direction) async {
        if (direction == DismissDirection.startToEnd) {
          // Swipe Right: Quick Toggle
          HapticFeedback.mediumImpact();
          onToggle();
          return false; // Bounce back smoothly
        } else if (direction == DismissDirection.endToStart) {
          // Swipe Left: Delete
          if (onDelete != null) {
            HapticFeedback.heavyImpact();
            onDelete!();
            return true;
          }
          return false;
        }
        return false;
      },
      background: Container(
        alignment: Alignment.centerLeft,
        padding: const EdgeInsets.symmetric(horizontal: 22),
        decoration: BoxDecoration(
          color: const Color(0xFF10B981),
          borderRadius: BorderRadius.circular(20),
        ),
        child: Row(
          children: [
            Icon(
              isDone ? Icons.restart_alt_rounded : Icons.check_circle_rounded,
              color: Colors.white,
              size: 26,
            ),
            const SizedBox(width: 8),
            Text(
              isDone ? 'Mark To Do' : 'Complete',
              style: const TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w700,
                fontSize: 14,
              ),
            ),
          ],
        ),
      ),
      secondaryBackground: Container(
        alignment: Alignment.centerRight,
        padding: const EdgeInsets.symmetric(horizontal: 22),
        decoration: BoxDecoration(
          color: const Color(0xFFEF4444),
          borderRadius: BorderRadius.circular(20),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.end,
          children: const [
            Text(
              'Delete',
              style: TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w700,
                fontSize: 14,
              ),
            ),
            SizedBox(width: 8),
            Icon(
              Icons.delete_outline_rounded,
              color: Colors.white,
              size: 26,
            ),
          ],
        ),
      ),
      child: BouncingInteractive(
        onTap: busy ? null : onTap,
        scaleFactor: 0.985,
        child: AnimatedContainer(
          duration: motionDuration(context),
          decoration: BoxDecoration(
            color: isDone ? const Color(0xFFF9F8FE) : Colors.white,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(
              color: isDone ? const Color(0xFFE4DCFF) : const Color(0xFFF0F2F8),
              width: 1.2,
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.04),
                blurRadius: 14,
                offset: const Offset(0, 4),
              ),
              if (isDone)
                BoxShadow(
                  color: brandPurple.withValues(alpha: 0.04),
                  blurRadius: 10,
                  offset: const Offset(0, 2),
                ),
            ],
          ),
          child: Stack(
            children: [
              // Left Accent Strip
              if (showLeftAccent)
                Positioned(
                  left: 0,
                  top: 10,
                  bottom: 10,
                  child: Container(
                    width: 4,
                    decoration: BoxDecoration(
                      color: leftAccentColor,
                      borderRadius: const BorderRadius.horizontal(
                        right: Radius.circular(4),
                      ),
                    ),
                  ),
                ),

              // Main Content
              Padding(
                padding: const EdgeInsets.fromLTRB(14, 13, 14, 11),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    // Top Section: Checkbox, Title & Info, Priority Badge
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Custom squircle Checkbox with scale bounce
                        Semantics(
                          label:
                              '${task.completed ? 'Reopen' : 'Complete'} ${task.title}',
                          button: true,
                          child: InkWell(
                            onTap: busy
                                ? null
                                : () {
                                    HapticFeedback.lightImpact();
                                    onToggle();
                                  },
                            borderRadius: BorderRadius.circular(10),
                            child: Padding(
                              padding: const EdgeInsets.all(4),
                              child: MotionSwitcher(
                                child: busy
                                    ? const SizedBox(
                                        width: 24,
                                        height: 24,
                                        child: CircularProgressIndicator(
                                          strokeWidth: 2,
                                          valueColor:
                                              AlwaysStoppedAnimation(brandPurple),
                                        ),
                                      )
                                    : TaskSparkleBurst(
                                        trigger: isDone,
                                        child: AnimatedScale(
                                          scale: 1.0,
                                          duration:
                                              const Duration(milliseconds: 140),
                                          curve: Curves.easeOutBack,
                                          child: AnimatedContainer(
                                            key: ValueKey(isDone),
                                            duration:
                                                motionDuration(context, 180),
                                            width: 24,
                                            height: 24,
                                            decoration: BoxDecoration(
                                              color: isDone
                                                  ? checkFillColor
                                                  : Colors.white,
                                              borderRadius:
                                                  BorderRadius.circular(7.5),
                                              border: isDone
                                                  ? null
                                                  : Border.all(
                                                      color:
                                                          const Color(0xFFCBD5E1),
                                                      width: 1.8,
                                                    ),
                                              boxShadow: isDone
                                                  ? [
                                                      BoxShadow(
                                                        color: checkFillColor
                                                            .withValues(
                                                                alpha: 0.35),
                                                        blurRadius: 6,
                                                        offset:
                                                            const Offset(0, 2),
                                                      ),
                                                    ]
                                                  : null,
                                            ),
                                            child: isDone
                                                ? const Icon(
                                                    Icons.check_rounded,
                                                    size: 17,
                                                    color: Colors.white,
                                                  )
                                                : null,
                                          ),
                                        ),
                                      ),
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 10),

                        // Main Task Details
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Row(
                                children: [
                                  Expanded(
                                    child: AnimatedDefaultTextStyle(
                                      duration: motionDuration(context),
                                      style: TextStyle(
                                        fontSize: 15.5,
                                        fontWeight: FontWeight.w700,
                                        decoration: isDone
                                            ? TextDecoration.lineThrough
                                            : null,
                                        decorationColor:
                                            const Color(0xFF7E8494),
                                        color: isDone
                                            ? const Color(0xFF7E8494)
                                            : const Color(0xFF1B1D28),
                                        letterSpacing: -0.2,
                                      ),
                                      child: Text(
                                        task.title,
                                        maxLines: 2,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
                                  ),
                                  if (task.isPinned)
                                    const Padding(
                                      padding: EdgeInsets.only(left: 6),
                                      child: Icon(
                                        Icons.push_pin_rounded,
                                        size: 15,
                                        color: brandPurple,
                                      ),
                                    ),
                                ],
                              ),
                              const SizedBox(height: 6),
                              Wrap(
                                crossAxisAlignment: WrapCrossAlignment.center,
                                spacing: 8,
                                runSpacing: 4,
                                children: [
                                  // Calendar & Due Date
                                  Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      Icon(
                                        Icons.calendar_today_outlined,
                                        size: 13,
                                        color: task.overdue
                                            ? const Color(0xFFEF4444)
                                            : const Color(0xFF8E95A5),
                                      ),
                                      const SizedBox(width: 4),
                                      Flexible(
                                        child: Text(
                                          dateText,
                                          overflow: TextOverflow.ellipsis,
                                          style: TextStyle(
                                            fontSize: 12.5,
                                            fontWeight: FontWeight.w500,
                                            color: task.overdue
                                                ? const Color(0xFFEF4444)
                                                : const Color(0xFF8E95A5),
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),

                                  // Semantic Category Tag Pill (if present)
                                  if (task.category.isNotEmpty)
                                    Container(
                                      padding: const EdgeInsets.symmetric(
                                        horizontal: 7,
                                        vertical: 2.5,
                                      ),
                                      decoration: BoxDecoration(
                                        color: catBg,
                                        borderRadius: BorderRadius.circular(6),
                                      ),
                                      child: Row(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          Icon(
                                            categoryIcon(catName),
                                            size: 12,
                                            color: catClr,
                                          ),
                                          const SizedBox(width: 3.5),
                                          Text(
                                            catName,
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                            style: TextStyle(
                                              fontSize: 11.5,
                                              fontWeight: FontWeight.w600,
                                              color: catClr,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                ],
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 8),

                      // Status & Priority Badges (Top Right)
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          // Interactive Animated Status Badge (Todo, In Progress, Done)
                          InteractiveTaskStatusBadge(
                            status: task.status,
                            dense: true,
                            onTap: busy
                                ? null
                                : () async {
                                    if (onStatusChange != null) {
                                      final chosen =
                                          await showTaskStatusPickerSheet(
                                        context,
                                        currentStatus: task.status,
                                      );
                                      if (chosen != null &&
                                          chosen != task.status) {
                                        onStatusChange!(chosen);
                                      }
                                    } else {
                                      onToggle();
                                    }
                                  },
                          ),
                          const SizedBox(height: 5),
                          // Priority Badge Pill
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 3,
                            ),
                            decoration: BoxDecoration(
                              color: priorityBgColor(task.priority),
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(
                                color: priorityColor(task.priority)
                                    .withValues(alpha: 0.25),
                                width: 1,
                              ),
                            ),
                            child: Text(
                              '${task.priority[0].toUpperCase()}${task.priority.substring(1)}',
                              style: TextStyle(
                                color: priorityColor(task.priority),
                                fontSize: 10.5,
                                fontWeight: FontWeight.w700,
                                letterSpacing: 0.2,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),

                  // Interactive Checklist & Steps Accordion (1-tap toggle, strike-through, progress)
                  if (task.subtasks.isNotEmpty)
                    TaskChecklistSection(
                      subtasks: task.subtasks,
                      onToggleSubtask: (index) {
                        if (onToggleSubtask != null) {
                          onToggleSubtask!(index);
                        }
                      },
                      onAddSubtask: onAddSubtask,
                    ),

                  // Subtle separator line
                    const SizedBox(height: 10),
                    Container(
                      height: 1,
                      color: isDone
                          ? const Color(0xFFEDE9FE)
                          : const Color(0xFFF1F5F9),
                    ),
                    const SizedBox(height: 8),

                    // Direct Action Bar (Mark done, Edit, Delete - No dropdown!)
                    Row(
                      children: [
                        // 1. Mark Done / Mark to do Action Button
                        _ActionPill(
                          icon: isDone
                              ? Icons.restart_alt_rounded
                              : Icons.check_circle_rounded,
                          label: isDone ? 'Mark to do' : 'Mark done',
                          textColor: isDone
                              ? const Color(0xFF64748B)
                              : const Color(0xFF059669),
                          bgColor: isDone
                              ? const Color(0xFFF1F5F9)
                              : const Color(0xFFECFDF5),
                          borderColor: isDone
                              ? const Color(0xFFE2E8F0)
                              : const Color(0xFFA7F3D0),
                          onTap: busy
                              ? null
                              : () {
                                  HapticFeedback.lightImpact();
                                  onToggle();
                                },
                        ),

                        const Spacer(),

                        // 2. Edit Action Button
                        _ActionPill(
                          icon: Icons.edit_outlined,
                          label: 'Edit',
                          textColor: const Color(0xFF2563EB),
                          bgColor: const Color(0xFFEFF6FF),
                          borderColor: const Color(0xFFBFDBFE),
                          onTap: busy
                              ? null
                              : () {
                                  HapticFeedback.lightImpact();
                                  onTap();
                                },
                        ),

                        // 3. Delete Action Button
                        if (onDelete != null) ...[
                          const SizedBox(width: 6),
                          _ActionPill(
                            icon: Icons.delete_outline_rounded,
                            label: 'Delete',
                            textColor: const Color(0xFFE11D48),
                            bgColor: const Color(0xFFFEF2F2),
                            borderColor: const Color(0xFFFECDD3),
                            onTap: busy
                                ? null
                                : () async {
                                    HapticFeedback.lightImpact();
                                    final confirmed =
                                        await _showDeleteConfirmation(
                                      context,
                                      task.title,
                                    );
                                    if (confirmed == true && onDelete != null) {
                                      onDelete!();
                                    }
                                  },
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

Future<bool?> _showDeleteConfirmation(BuildContext context, String taskTitle) {
  return showGeneralDialog<bool>(
    context: context,
    barrierDismissible: true,
    barrierLabel: 'Dismiss',
    barrierColor: Colors.black.withValues(alpha: 0.5),
    transitionDuration: const Duration(milliseconds: 250),
    pageBuilder: (ctx, anim1, anim2) => const SizedBox(),
    transitionBuilder: (ctx, anim, secondaryAnim, child) {
      final curved = CurvedAnimation(parent: anim, curve: Curves.easeOutBack);
      return Transform.scale(
        scale: 0.85 + (0.15 * curved.value),
        child: Opacity(
          opacity: anim.value.clamp(0.0, 1.0),
          child: BackdropFilter(
            filter: ImageFilter.blur(
              sigmaX: 5 * anim.value,
              sigmaY: 5 * anim.value,
            ),
            child: Center(
              child: Container(
                margin: const EdgeInsets.symmetric(horizontal: 28),
                padding: const EdgeInsets.fromLTRB(24, 26, 24, 22),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(24),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.15),
                      blurRadius: 30,
                      offset: const Offset(0, 12),
                    ),
                  ],
                ),
                child: Material(
                  color: Colors.transparent,
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Container(
                        width: 58,
                        height: 58,
                        decoration: BoxDecoration(
                          color: const Color(0xFFFEE2E2),
                          shape: BoxShape.circle,
                          boxShadow: [
                            BoxShadow(
                              color: const Color(0xFFEF4444)
                                  .withValues(alpha: 0.25),
                              blurRadius: 18,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        child: const Icon(
                          Icons.delete_outline_rounded,
                          color: Color(0xFFDC2626),
                          size: 30,
                        ),
                      ),
                      const SizedBox(height: 18),
                      const Text(
                        'Delete Task?',
                        style: TextStyle(
                          fontSize: 18.5,
                          fontWeight: FontWeight.w800,
                          color: Color(0xFF1E293B),
                          letterSpacing: -0.3,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'Are you sure you want to delete "$taskTitle"? This action cannot be undone.',
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          fontSize: 13.5,
                          color: Color(0xFF64748B),
                          height: 1.45,
                        ),
                      ),
                      const SizedBox(height: 24),
                      Row(
                        children: [
                          Expanded(
                            child: OutlinedButton(
                              style: OutlinedButton.styleFrom(
                                padding:
                                    const EdgeInsets.symmetric(vertical: 12),
                                side:
                                    const BorderSide(color: Color(0xFFE2E8F0)),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(14),
                                ),
                                backgroundColor: const Color(0xFFF8FAFC),
                              ),
                              onPressed: () => Navigator.pop(ctx, false),
                              child: const Text(
                                'Cancel',
                                style: TextStyle(
                                  fontSize: 14,
                                  fontWeight: FontWeight.w600,
                                  color: Color(0xFF64748B),
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: FilledButton(
                              style: FilledButton.styleFrom(
                                padding:
                                    const EdgeInsets.symmetric(vertical: 12),
                                backgroundColor: const Color(0xFFDC2626),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(14),
                                ),
                                elevation: 0,
                              ),
                              onPressed: () => Navigator.pop(ctx, true),
                              child: const Text(
                                'Delete',
                                style: TextStyle(
                                  fontSize: 14,
                                  fontWeight: FontWeight.w700,
                                  color: Colors.white,
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
        ),
      );
    },
  );
}

class _ActionPill extends StatelessWidget {
  const _ActionPill({
    required this.icon,
    required this.label,
    required this.textColor,
    required this.bgColor,
    required this.borderColor,
    this.onTap,
  });

  final IconData icon;
  final String label;
  final Color textColor;
  final Color bgColor;
  final Color borderColor;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(10),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5.5),
          decoration: BoxDecoration(
            color: bgColor,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: borderColor, width: 1),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(icon, size: 14.5, color: textColor),
              const SizedBox(width: 4.5),
              Text(
                label,
                style: TextStyle(
                  fontSize: 11.5,
                  fontWeight: FontWeight.w700,
                  color: textColor,
                  letterSpacing: -0.1,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class EmptyTasks extends StatelessWidget {
  const EmptyTasks({
    super.key,
    this.title = 'A fresh start',
    this.message = 'Tap + to add your first task.',
    this.onAdd,
  });
  final String title, message;
  final VoidCallback? onAdd;
  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 36, horizontal: 16),
    child: Column(
      children: [
        Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            color: const Color(0xFFECE9FF),
            shape: BoxShape.circle,
            boxShadow: [
              BoxShadow(
                color: brandPurple.withValues(alpha: 0.12),
                blurRadius: 18,
                offset: const Offset(0, 6),
              ),
            ],
          ),
          child: const Icon(
            Icons.checklist_rounded,
            color: brandPurple,
            size: 38,
          ),
        ),
        const SizedBox(height: 18),
        Text(
          title,
          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
        ),
        const SizedBox(height: 8),
        Text(
          message,
          textAlign: TextAlign.center,
          style: const TextStyle(color: Color(0xFF8E95A5)),
        ),
        if (onAdd != null) ...[
          const SizedBox(height: 18),
          BouncingInteractive(
            onTap: onAdd,
            child: FilledButton.icon(
              onPressed: onAdd,
              icon: const Icon(Icons.add_rounded),
              label: const Text(
                'Create a task',
                style: TextStyle(fontWeight: FontWeight.w700),
              ),
              style: FilledButton.styleFrom(
                backgroundColor: brandPurple,
                foregroundColor: Colors.white,
                padding:
                    const EdgeInsets.symmetric(horizontal: 22, vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                ),
              ),
            ),
          ),
        ],
      ],
    ),
  );
}
