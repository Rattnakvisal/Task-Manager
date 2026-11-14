import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:intl/intl.dart';

import '../app.dart';
import '../models.dart';
import 'motion.dart';

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
  });

  final Task task;
  final VoidCallback onTap, onToggle;
  final bool busy;
  final VoidCallback? onDelete;
  final bool showLeftAccent;

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

    final String badgeLabel = isDone
        ? 'Done'
        : '${task.priority[0].toUpperCase()}${task.priority.substring(1)}';
    final Color badgeColor =
        isDone ? const Color(0xFF10B981) : priorityColor(task.priority);
    final Color badgeBg =
        isDone ? const Color(0xFFD1FAE5) : priorityBgColor(task.priority);

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

              // Main Row Content
              Padding(
                padding: const EdgeInsets.fromLTRB(14, 14, 12, 14),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.center,
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
                                      duration: const Duration(milliseconds: 140),
                                      curve: Curves.easeOutBack,
                                      child: AnimatedContainer(
                                        key: ValueKey(isDone),
                                        duration: motionDuration(context, 180),
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
                                                  color: const Color(0xFFCBD5E1),
                                                  width: 1.8,
                                                ),
                                          boxShadow: isDone
                                              ? [
                                                  BoxShadow(
                                                    color: checkFillColor
                                                        .withValues(alpha: 0.35),
                                                    blurRadius: 6,
                                                    offset: const Offset(0, 2),
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
                                    decorationColor: const Color(0xFF7E8494),
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
                          Row(
                            children: [
                              // Calendar & Due Date
                              Icon(
                                Icons.calendar_today_outlined,
                                size: 13,
                                color: task.overdue
                                    ? const Color(0xFFEF4444)
                                    : const Color(0xFF8E95A5),
                              ),
                              const SizedBox(width: 4),
                              Text(
                                dateText,
                                style: TextStyle(
                                  fontSize: 12.5,
                                  fontWeight: FontWeight.w500,
                                  color: task.overdue
                                      ? const Color(0xFFEF4444)
                                      : const Color(0xFF8E95A5),
                                ),
                              ),
                              const SizedBox(width: 12),

                              // Semantic Category Tag Pill (if present)
                              if (task.category.isNotEmpty) ...[
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
                            ],
                          ),
                          if (task.subtasks.isNotEmpty) ...[
                            const SizedBox(height: 8),
                            Row(
                              children: [
                                Expanded(
                                  child: AnimatedTaskProgress(
                                    value: task.completedSubtasks /
                                        task.subtasks.length,
                                    height: 4,
                                    color: brandPurple,
                                    backgroundColor: const Color(0xFFEEF1F7),
                                  ),
                                ),
                                const SizedBox(width: 8),
                                Text(
                                  '${task.completedSubtasks}/${task.subtasks.length}',
                                  style: const TextStyle(
                                    fontSize: 11,
                                    color: Color(0xFF8E95A5),
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ],
                      ),
                    ),
                    const SizedBox(width: 8),

                    // Priority / Status Badge Pill
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 11,
                        vertical: 5,
                      ),
                      decoration: BoxDecoration(
                        color: badgeBg,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: badgeColor.withValues(alpha: 0.2),
                          width: 1,
                        ),
                      ),
                      child: Text(
                        badgeLabel,
                        style: TextStyle(
                          color: badgeColor,
                          fontSize: 11.5,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                    const SizedBox(width: 2),

                    // 3-dots Menu Button
                    PopupMenuButton<String>(
                      icon: const Icon(
                        Icons.more_vert_rounded,
                        size: 20,
                        color: Color(0xFF94A3B8),
                      ),
                      padding: EdgeInsets.zero,
                      constraints: const BoxConstraints(minWidth: 120),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                      onSelected: (value) {
                        if (value == 'edit') {
                          onTap();
                        } else if (value == 'toggle') {
                          onToggle();
                        } else if (value == 'delete' && onDelete != null) {
                          onDelete!();
                        }
                      },
                      itemBuilder: (context) => [
                        PopupMenuItem(
                          value: 'edit',
                          child: Row(
                            children: const [
                              Icon(Icons.edit_outlined, size: 18),
                              SizedBox(width: 10),
                              Text('Edit'),
                            ],
                          ),
                        ),
                        PopupMenuItem(
                          value: 'toggle',
                          child: Row(
                            children: [
                              Icon(
                                isDone
                                    ? Icons.restart_alt_rounded
                                    : Icons.check_circle_outline_rounded,
                                size: 18,
                              ),
                              const SizedBox(width: 10),
                              Text(isDone ? 'Mark to do' : 'Mark done'),
                            ],
                          ),
                        ),
                        if (onDelete != null)
                          PopupMenuItem(
                            value: 'delete',
                            child: Row(
                              children: const [
                                Icon(
                                  Icons.delete_outline_rounded,
                                  size: 18,
                                  color: Color(0xFFEF4444),
                                ),
                                SizedBox(width: 10),
                                Text(
                                  'Delete',
                                  style: TextStyle(color: Color(0xFFEF4444)),
                                ),
                              ],
                            ),
                          ),
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
