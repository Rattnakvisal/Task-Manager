import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../app.dart';
import 'motion.dart';

class TaskbarItem {
  const TaskbarItem({
    required this.icon,
    required this.selectedIcon,
    required this.label,
    this.badge,
  });

  final IconData icon;
  final IconData selectedIcon;
  final String label;
  final String? badge;
}

class AnimatedTaskbar extends StatelessWidget {
  const AnimatedTaskbar({
    super.key,
    required this.selectedIndex,
    required this.onItemSelected,
    required this.items,
  });

  final int selectedIndex;
  final ValueChanged<int> onItemSelected;
  final List<TaskbarItem> items;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.85),
        border: const Border(
          top: BorderSide(
            color: Color(0x22000000), // iOS 0.5px hairline top border
            width: 0.5,
          ),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 14,
            offset: const Offset(0, -2),
          ),
        ],
      ),
      child: ClipRect(
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 25, sigmaY: 25),
          child: SafeArea(
            top: false,
            child: NavigationBarTheme(
              data: NavigationBarThemeData(
                height: 72,
                backgroundColor: Colors.transparent,
                indicatorColor: const Color(0xFFECE9FF),
                indicatorShape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                ),
                labelTextStyle: WidgetStateProperty.resolveWith((states) {
                  final isSelected = states.contains(WidgetState.selected);
                  return TextStyle(
                    fontSize: 11.5,
                    fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
                    color: isSelected ? brandPurple : const Color(0xFF8E95A5),
                    letterSpacing: -0.2,
                  );
                }),
                iconTheme: WidgetStateProperty.resolveWith((states) {
                  final isSelected = states.contains(WidgetState.selected);
                  return IconThemeData(
                    size: 24,
                    color: isSelected ? brandPurple : const Color(0xFF8E95A5),
                  );
                }),
              ),
              child: NavigationBar(
                selectedIndex: selectedIndex,
                onDestinationSelected: (index) {
                  if (index != selectedIndex) {
                    HapticFeedback.selectionClick();
                    onItemSelected(index);
                  }
                },
                animationDuration: motionDuration(context, 300),
                destinations: [
                  for (final item in items)
                    NavigationDestination(
                      icon: item.badge != null
                          ? Badge(
                              label: Text(item.badge!),
                              backgroundColor: const Color(0xFFEF4444),
                              child: Icon(item.icon),
                            )
                          : Icon(item.icon),
                      selectedIcon: item.badge != null
                          ? Badge(
                              label: Text(item.badge!),
                              backgroundColor: const Color(0xFFEF4444),
                              child: Icon(item.selectedIcon),
                            )
                          : Icon(item.selectedIcon),
                      label: item.label,
                    ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
