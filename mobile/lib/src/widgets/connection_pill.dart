import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../app_state.dart';
import '../app.dart';
import 'motion.dart';

class AnimatedConnectionPill extends StatefulWidget {
  const AnimatedConnectionPill({
    super.key,
    required this.state,
    this.compactOnly = false,
  });

  final AppState state;
  final bool compactOnly;

  @override
  State<AnimatedConnectionPill> createState() => _AnimatedConnectionPillState();
}

class _AnimatedConnectionPillState extends State<AnimatedConnectionPill>
    with SingleTickerProviderStateMixin {
  late final AnimationController _pulseController;
  bool _expanded = false;

  @override
  void initState() {
    super.initState();
    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 900),
    );
  }

  void _pulse() {
    if (!mounted) return;
    _pulseController.forward(from: 0);
  }

  @override
  void didUpdateWidget(covariant AnimatedConnectionPill oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.state.isConnected != oldWidget.state.isConnected ||
        widget.state.isCheckingConnection != oldWidget.state.isCheckingConnection) {
      _pulse();
    }
  }

  @override
  void dispose() {
    _pulseController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final state = widget.state;
    final isOnline = state.isConnected;
    final isChecking = state.isCheckingConnection;

    final primaryColor = isChecking
        ? const Color(0xFFF59E0B) // Amber
        : isOnline
            ? const Color(0xFF10B981) // Emerald Green
            : const Color(0xFFEF4444); // Red

    final statusText = isChecking
        ? 'Syncing server…'
        : isOnline
            ? (state.lastPingLatencyMs != null
                ? 'Server Live · ${state.lastPingLatencyMs}ms'
                : 'Server Connected')
            : 'Server Offline · Tap to retry';

    final connectionType = state.connectionType;

    return AnimatedSize(
      duration: motionDuration(context, 320),
      curve: Curves.easeOutBack,
      alignment: Alignment.topCenter,
      child: GestureDetector(
        onTap: () {
          if (widget.compactOnly) {
            HapticFeedback.selectionClick();
            state.checkConnection();
            return;
          }
          HapticFeedback.selectionClick();
          setState(() => _expanded = !_expanded);
        },
        child: Container(
          margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
          padding: EdgeInsets.symmetric(
            horizontal: 14,
            vertical: _expanded ? 14 : 7,
          ),
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.96),
            borderRadius: BorderRadius.circular(_expanded ? 20 : 30),
            border: Border.all(
              color: primaryColor.withValues(alpha: _expanded ? 0.35 : 0.22),
              width: 1.2,
            ),
            boxShadow: [
              BoxShadow(
                color: primaryColor.withValues(alpha: 0.08),
                blurRadius: 16,
                offset: const Offset(0, 4),
              ),
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.04),
                blurRadius: 10,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header Row (Always visible)
              Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  // Animated Pulsing Halo Dot
                  CustomPaint(
                    painter: _PulsingHaloPainter(
                      color: primaryColor,
                      animation: _pulseController,
                      isOnline: isOnline,
                      isChecking: isChecking,
                    ),
                    child: SizedBox(
                      width: 14,
                      height: 14,
                      child: Center(
                        child: Container(
                          width: 7,
                          height: 7,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            color: primaryColor,
                          ),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),

                  // Connection Status Text
                  Flexible(
                    child: Text(
                      statusText,
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        color: isOnline
                            ? const Color(0xFF1E293B)
                            : const Color(0xFFDC2626),
                        letterSpacing: -0.1,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),

                  const SizedBox(width: 6),
                  Icon(
                    _expanded
                        ? Icons.keyboard_arrow_up_rounded
                        : Icons.keyboard_arrow_down_rounded,
                    size: 16,
                    color: const Color(0xFF94A3B8),
                  ),
                ],
              ),

              // Expanded Details Card
              if (_expanded) ...[
                const SizedBox(height: 12),
                const Divider(height: 1, color: Color(0xFFF1F5F9)),
                const SizedBox(height: 12),

                // Active Endpoint URL
                _detailRow(
                  icon: Icons.link_rounded,
                  label: 'Endpoint',
                  value: state.api.baseUrl,
                ),
                const SizedBox(height: 8),

                // Connection Channel (USB / Wi-Fi)
                _detailRow(
                  icon: connectionType.contains('USB')
                      ? Icons.usb_rounded
                      : Icons.wifi_rounded,
                  label: 'Channel',
                  value: connectionType,
                ),
                const SizedBox(height: 8),

                // Latency Badge
                _detailRow(
                  icon: Icons.speed_rounded,
                  label: 'Status',
                  value: isOnline
                      ? 'Optimal · Real-time'
                      : 'Disconnected · Unreachable',
                ),

                const SizedBox(height: 14),

                // Action Re-sync Button
                SizedBox(
                  width: double.infinity,
                  height: 38,
                  child: FilledButton.tonalIcon(
                    onPressed: isChecking
                        ? null
                        : () async {
                            HapticFeedback.lightImpact();
                            await state.checkConnection();
                          },
                    icon: isChecking
                        ? const SizedBox(
                            width: 14,
                            height: 14,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.refresh_rounded, size: 16),
                    label: Text(
                      isChecking ? 'Re-probing…' : 'Ping & Re-check Connection',
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    style: FilledButton.styleFrom(
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      backgroundColor: brandBlue.withValues(alpha: 0.08),
                      foregroundColor: brandBlue,
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _detailRow({
    required IconData icon,
    required String label,
    required String value,
  }) =>
      Row(
        children: [
          Icon(icon, size: 15, color: const Color(0xFF64748B)),
          const SizedBox(width: 8),
          Text(
            '$label: ',
            style: const TextStyle(
              fontSize: 11.5,
              fontWeight: FontWeight.w500,
              color: Color(0xFF64748B),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(
                fontSize: 11.5,
                fontWeight: FontWeight.w600,
                color: Color(0xFF0F172A),
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      );
}

class _PulsingHaloPainter extends CustomPainter {
  _PulsingHaloPainter({
    required this.color,
    required this.animation,
    required this.isOnline,
    required this.isChecking,
  }) : super(repaint: animation);

  final Color color;
  final Animation<double> animation;
  final bool isOnline;
  final bool isChecking;

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final value = animation.value;

    if (animation.isAnimating) {
      // Expanding Ripple Ring
      final radius = 3.5 + (value * 8);
      final opacity = (1.0 - value).clamp(0.0, 0.7);
      final paint = Paint()
        ..color = color.withValues(alpha: opacity)
        ..style = PaintingStyle.stroke
        ..strokeWidth = 1.4;

      canvas.drawCircle(center, radius, paint);

      // Second smaller wave
      final secondValue = (value + 0.5) % 1.0;
      final secondRadius = 3.5 + (secondValue * 8);
      final secondOpacity = (1.0 - secondValue).clamp(0.0, 0.4);
      final secondPaint = Paint()
        ..color = color.withValues(alpha: secondOpacity)
        ..style = PaintingStyle.stroke
        ..strokeWidth = 1.0;

      canvas.drawCircle(center, secondRadius, secondPaint);
    } else if (isOnline) {
      final paint = Paint()
        ..color = color.withValues(alpha: 0.25)
        ..style = PaintingStyle.stroke
        ..strokeWidth = 1.2;
      canvas.drawCircle(center, 5.5, paint);
    }
  }

  @override
  bool shouldRepaint(covariant _PulsingHaloPainter oldDelegate) => true;
}
