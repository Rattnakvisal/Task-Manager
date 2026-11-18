import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'motion.dart';

enum SweetAlertType {
  success,
  confirm,
  danger,
  warning,
  info,
}

class SweetAlert extends StatefulWidget {
  const SweetAlert({
    super.key,
    required this.type,
    required this.title,
    required this.message,
    this.confirmText,
    this.cancelText,
    this.confirmColor,
    this.icon,
    this.autoCloseDuration,
    this.showTimerProgress = true,
  });

  final SweetAlertType type;
  final String title;
  final String message;
  final String? confirmText;
  final String? cancelText;
  final Color? confirmColor;
  final IconData? icon;
  final Duration? autoCloseDuration;
  final bool showTimerProgress;

  // 1. Success Celebration Alert
  static Future<void> success(
    BuildContext context, {
    required String title,
    required String message,
    String confirmText = 'OK',
    Duration? autoCloseDuration = const Duration(milliseconds: 2200),
    bool showTimerProgress = true,
  }) async {
    if (!context.mounted || isRunningInTest) return;
    HapticFeedback.mediumImpact();
    await showGeneralDialog<void>(
      context: context,
      barrierDismissible: true,
      barrierLabel: 'SweetAlert',
      barrierColor: Colors.black.withValues(alpha: 0.55),
      transitionDuration: const Duration(milliseconds: 280),
      pageBuilder: (ctx, anim1, anim2) => SweetAlert(
        type: SweetAlertType.success,
        title: title,
        message: message,
        confirmText: confirmText,
        autoCloseDuration: autoCloseDuration,
        showTimerProgress: showTimerProgress,
      ),
      transitionBuilder: _buildTransition,
    );
  }

  // 2. Confirm Question Alert
  static Future<bool> confirm(
    BuildContext context, {
    required String title,
    required String message,
    String confirmText = 'Yes, Confirm',
    String cancelText = 'Cancel',
    Color? confirmColor,
    IconData? icon,
    bool isDanger = false,
  }) async {
    if (!context.mounted) return false;
    if (isRunningInTest) return true;
    HapticFeedback.selectionClick();
    final result = await showGeneralDialog<bool>(
      context: context,
      barrierDismissible: true,
      barrierLabel: 'SweetAlert',
      barrierColor: Colors.black.withValues(alpha: 0.55),
      transitionDuration: const Duration(milliseconds: 280),
      pageBuilder: (ctx, anim1, anim2) => SweetAlert(
        type: isDanger ? SweetAlertType.danger : SweetAlertType.confirm,
        title: title,
        message: message,
        confirmText: confirmText,
        cancelText: cancelText,
        confirmColor: confirmColor,
        icon: icon,
      ),
      transitionBuilder: _buildTransition,
    );
    return result ?? false;
  }

  // 3. Danger / Delete Alert
  static Future<bool> danger(
    BuildContext context, {
    required String title,
    required String message,
    String confirmText = 'Yes, Delete',
    String cancelText = 'Cancel',
  }) async {
    return confirm(
      context,
      title: title,
      message: message,
      confirmText: confirmText,
      cancelText: cancelText,
      confirmColor: const Color(0xFFEF4444),
      icon: Icons.delete_forever_rounded,
      isDanger: true,
    );
  }

  // 4. Warning Alert
  static Future<bool> warning(
    BuildContext context, {
    required String title,
    required String message,
    String confirmText = 'Continue',
    String cancelText = 'Cancel',
  }) async {
    return confirm(
      context,
      title: title,
      message: message,
      confirmText: confirmText,
      cancelText: cancelText,
      confirmColor: const Color(0xFFF59E0B),
      icon: Icons.warning_amber_rounded,
      isDanger: false,
    );
  }

  // 5. Info Alert
  static Future<void> info(
    BuildContext context, {
    required String title,
    required String message,
    String confirmText = 'Got It',
    Duration? autoCloseDuration,
  }) async {
    if (!context.mounted) return;
    HapticFeedback.lightImpact();
    await showGeneralDialog<void>(
      context: context,
      barrierDismissible: true,
      barrierLabel: 'SweetAlert',
      barrierColor: Colors.black.withValues(alpha: 0.55),
      transitionDuration: const Duration(milliseconds: 280),
      pageBuilder: (ctx, anim1, anim2) => SweetAlert(
        type: SweetAlertType.info,
        title: title,
        message: message,
        confirmText: confirmText,
        autoCloseDuration: autoCloseDuration,
      ),
      transitionBuilder: _buildTransition,
    );
  }

  static Widget _buildTransition(
    BuildContext context,
    Animation<double> animation,
    Animation<double> secondaryAnimation,
    Widget child,
  ) {
    final curved = CurvedAnimation(
      parent: animation,
      curve: Curves.easeOutBack,
      reverseCurve: Curves.easeInBack,
    );
    return ScaleTransition(
      scale: Tween<double>(begin: 0.82, end: 1.0).animate(curved),
      child: FadeTransition(
        opacity: CurvedAnimation(parent: animation, curve: Curves.easeOut),
        child: child,
      ),
    );
  }

  @override
  State<SweetAlert> createState() => _SweetAlertState();
}

class _SweetAlertState extends State<SweetAlert>
    with SingleTickerProviderStateMixin {
  late final AnimationController _animController;
  late final Animation<double> _iconScale;
  Timer? _autoCloseTimer;
  double _timerProgress = 1.0;
  Timer? _progressTicker;

  @override
  void initState() {
    super.initState();
    _animController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 400),
    );
    _iconScale = Tween<double>(begin: 0.2, end: 1.0).animate(
      CurvedAnimation(
        parent: _animController,
        curve: Curves.elasticOut,
      ),
    );
    _animController.forward();

    if (widget.autoCloseDuration != null) {
      final totalMs = widget.autoCloseDuration!.inMilliseconds;
      final stepMs = 30;
      final decrement = stepMs / totalMs;

      _progressTicker = Timer.periodic(Duration(milliseconds: stepMs), (timer) {
        if (!mounted) {
          timer.cancel();
          return;
        }
        setState(() {
          _timerProgress = (_timerProgress - decrement).clamp(0.0, 1.0);
        });
      });

      _autoCloseTimer = Timer(widget.autoCloseDuration!, () {
        if (mounted) {
          Navigator.of(context).pop(true);
        }
      });
    }
  }

  @override
  void dispose() {
    _animController.dispose();
    _autoCloseTimer?.cancel();
    _progressTicker?.cancel();
    super.dispose();
  }

  Color get _themeColor {
    if (widget.confirmColor != null) return widget.confirmColor!;
    switch (widget.type) {
      case SweetAlertType.success:
        return const Color(0xFF10B981);
      case SweetAlertType.confirm:
        return const Color(0xFF6366F1);
      case SweetAlertType.danger:
        return const Color(0xFFEF4444);
      case SweetAlertType.warning:
        return const Color(0xFFF59E0B);
      case SweetAlertType.info:
        return const Color(0xFF3B82F6);
    }
  }

  Color get _bgCircleColor {
    switch (widget.type) {
      case SweetAlertType.success:
        return const Color(0xFFECFDF5);
      case SweetAlertType.confirm:
        return const Color(0xFFEEF2FF);
      case SweetAlertType.danger:
        return const Color(0xFFFEE2E2);
      case SweetAlertType.warning:
        return const Color(0xFFFEF3C7);
      case SweetAlertType.info:
        return const Color(0xFFEFF6FF);
    }
  }

  Color get _borderColor {
    switch (widget.type) {
      case SweetAlertType.success:
        return const Color(0xFFA7F3D0);
      case SweetAlertType.confirm:
        return const Color(0xFFC7D2FE);
      case SweetAlertType.danger:
        return const Color(0xFFFECACA);
      case SweetAlertType.warning:
        return const Color(0xFFFDE68A);
      case SweetAlertType.info:
        return const Color(0xFFBFDBFE);
    }
  }

  IconData get _defaultIcon {
    if (widget.icon != null) return widget.icon!;
    switch (widget.type) {
      case SweetAlertType.success:
        return Icons.check_rounded;
      case SweetAlertType.confirm:
        return Icons.help_outline_rounded;
      case SweetAlertType.danger:
        return Icons.delete_outline_rounded;
      case SweetAlertType.warning:
        return Icons.warning_amber_rounded;
      case SweetAlertType.info:
        return Icons.info_outline_rounded;
    }
  }

  @override
  Widget build(BuildContext context) {
    final themeColor = _themeColor;
    final isTwoButton = widget.type == SweetAlertType.confirm ||
        widget.type == SweetAlertType.danger ||
        widget.type == SweetAlertType.warning;

    return Center(
      child: Material(
        color: Colors.transparent,
        child: Container(
          width: 330,
          margin: const EdgeInsets.symmetric(horizontal: 24),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(26),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.14),
                blurRadius: 28,
                offset: const Offset(0, 12),
              ),
              BoxShadow(
                color: themeColor.withValues(alpha: 0.08),
                blurRadius: 36,
                offset: const Offset(0, 16),
              ),
            ],
          ),
          clipBehavior: Clip.antiAlias,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(24, 28, 24, 20),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    // Icon Circle with animation and soft glow
                    ScaleTransition(
                      scale: _iconScale,
                      child: Stack(
                        alignment: Alignment.center,
                        children: [
                          // Outer glow ring
                          Container(
                            width: 68,
                            height: 68,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              color: _bgCircleColor,
                              border: Border.all(
                                color: _borderColor,
                                width: 2,
                              ),
                              boxShadow: [
                                BoxShadow(
                                  color: themeColor.withValues(alpha: 0.22),
                                  blurRadius: 16,
                                  offset: const Offset(0, 4),
                                ),
                              ],
                            ),
                            child: Center(
                              child: Icon(
                                _defaultIcon,
                                color: themeColor,
                                size: 34,
                              ),
                            ),
                          ),
                          // Subtle celebration sparkle decoration for success
                          if (widget.type == SweetAlertType.success) ...[
                            Positioned(
                              top: 2,
                              right: 2,
                              child: Container(
                                width: 14,
                                height: 14,
                                decoration: const BoxDecoration(
                                  color: Color(0xFFFBBF24),
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(
                                  Icons.star_rounded,
                                  size: 10,
                                  color: Colors.white,
                                ),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                    const SizedBox(height: 18),

                    // Title
                    Text(
                      widget.title,
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontSize: 18.5,
                        fontWeight: FontWeight.w800,
                        color: Color(0xFF0F172A),
                        letterSpacing: -0.3,
                        height: 1.3,
                      ),
                    ),
                    const SizedBox(height: 10),

                    // Message
                    Text(
                      widget.message,
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontSize: 13.5,
                        color: Color(0xFF64748B),
                        height: 1.5,
                      ),
                    ),
                    const SizedBox(height: 24),

                    // Actions
                    if (isTwoButton)
                      Row(
                        children: [
                          // Cancel Button
                          Expanded(
                            child: OutlinedButton(
                              style: OutlinedButton.styleFrom(
                                padding: const EdgeInsets.symmetric(vertical: 12),
                                side: const BorderSide(color: Color(0xFFE2E8F0)),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(14),
                                ),
                                backgroundColor: const Color(0xFFF8FAFC),
                              ),
                              onPressed: () {
                                HapticFeedback.selectionClick();
                                Navigator.of(context).pop(false);
                              },
                              child: Text(
                                widget.cancelText ?? 'Cancel',
                                textAlign: TextAlign.center,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  fontSize: 13.5,
                                  fontWeight: FontWeight.w600,
                                  color: Color(0xFF64748B),
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(width: 10),
                          // Confirm Button
                          Expanded(
                            child: FilledButton(
                              style: FilledButton.styleFrom(
                                padding: const EdgeInsets.symmetric(vertical: 12),
                                backgroundColor: themeColor,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(14),
                                ),
                                elevation: 0,
                              ),
                              onPressed: () {
                                HapticFeedback.mediumImpact();
                                Navigator.of(context).pop(true);
                              },
                              child: Text(
                                widget.confirmText ?? 'Confirm',
                                textAlign: TextAlign.center,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  fontSize: 13.5,
                                  fontWeight: FontWeight.w700,
                                  color: Colors.white,
                                ),
                              ),
                            ),
                          ),
                        ],
                      )
                    else
                      // Single Button (Success / Info)
                      SizedBox(
                        width: double.infinity,
                        child: FilledButton(
                          style: FilledButton.styleFrom(
                            padding: const EdgeInsets.symmetric(vertical: 13),
                            backgroundColor: themeColor,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(14),
                            ),
                            elevation: 0,
                          ),
                          onPressed: () {
                            HapticFeedback.selectionClick();
                            Navigator.of(context).pop(true);
                          },
                          child: Text(
                            widget.confirmText ?? 'OK',
                            style: const TextStyle(
                              fontSize: 14.5,
                              fontWeight: FontWeight.w700,
                              color: Colors.white,
                            ),
                          ),
                        ),
                      ),
                  ],
                ),
              ),

              // Auto-close progress bar at the bottom
              if (widget.autoCloseDuration != null && widget.showTimerProgress)
                LinearProgressIndicator(
                  value: _timerProgress,
                  backgroundColor: themeColor.withValues(alpha: 0.12),
                  valueColor: AlwaysStoppedAnimation<Color>(themeColor),
                  minHeight: 3.5,
                ),
            ],
          ),
        ),
      ),
    );
  }
}
