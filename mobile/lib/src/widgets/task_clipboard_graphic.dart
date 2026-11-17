import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'motion.dart';

class TaskClipboardGraphic extends StatefulWidget {
  const TaskClipboardGraphic({
    super.key,
    this.completedCount = 2,
    this.totalCount = 4,
    this.size = 125,
  });

  final int completedCount;
  final int totalCount;
  final double size;

  @override
  State<TaskClipboardGraphic> createState() => _TaskClipboardGraphicState();
}

class _TaskClipboardGraphicState extends State<TaskClipboardGraphic>
    with SingleTickerProviderStateMixin {
  late final AnimationController _floatController;
  late final Animation<double> _floatAnim;
  late final Animation<double> _twinkleAnim;

  @override
  void initState() {
    super.initState();
    _floatController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2800),
    );

    _floatAnim = Tween<double>(begin: -3.5, end: 3.5).animate(
      CurvedAnimation(
        parent: _floatController,
        curve: Curves.easeInOutSine,
      ),
    );

    _twinkleAnim = Tween<double>(begin: 0.85, end: 1.15).animate(
      CurvedAnimation(
        parent: _floatController,
        curve: Curves.easeInOutCubic,
      ),
    );
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (MediaQuery.disableAnimationsOf(context) || isRunningInTest) {
      _floatController.stop();
    } else if (!_floatController.isAnimating) {
      _floatController.repeat(reverse: true);
    }
  }

  @override
  void dispose() {
    _floatController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final disableMotion = MediaQuery.disableAnimationsOf(context);

    return SizedBox(
      width: widget.size,
      height: widget.size,
      child: FittedBox(
        fit: BoxFit.contain,
        child: AnimatedBuilder(
          animation: _floatController,
          builder: (context, child) {
            final floatOffset = disableMotion ? 0.0 : _floatAnim.value;
            final twinkleScale = disableMotion ? 1.0 : _twinkleAnim.value;

            return Transform.translate(
              offset: Offset(0, floatOffset),
              child: SizedBox(
                width: 125,
                height: 125,
                child: Stack(
                  clipBehavior: Clip.none,
                  alignment: Alignment.center,
                  children: [
                // Soft 3D ambient shadow below clipboard
                Positioned(
                  bottom: 6 - floatOffset * 0.5,
                  right: 8,
                  child: Container(
                    width: 86,
                    height: 100,
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(22),
                      boxShadow: [
                        BoxShadow(
                          color: const Color(0xFF1E2875)
                              .withValues(alpha: 0.35 + (floatOffset + 3.5) * 0.02),
                          blurRadius: 18 - floatOffset,
                          offset: Offset(4, 10 - floatOffset * 0.5),
                        ),
                      ],
                    ),
                  ),
                ),

                // Main 3D Clipboard Body with slight perspective tilt
                Transform.rotate(
                  angle: 0.05,
                  child: Container(
                    width: 90,
                    height: 106,
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [
                          Color(0xFFEBF2FF),
                          Color(0xFFD2E2FF),
                          Color(0xFFBAD2FD),
                        ],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(
                        color: Colors.white.withValues(alpha: 0.9),
                        width: 2.2,
                      ),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.12),
                          blurRadius: 12,
                          offset: const Offset(2, 6),
                        ),
                      ],
                    ),
                    child: Stack(
                      alignment: Alignment.topCenter,
                      children: [
                        // Diagonal Gloss Highlight
                        Positioned.fill(
                          child: ClipRRect(
                            borderRadius: BorderRadius.circular(18),
                            child: CustomPaint(
                              painter: _GlossPainter(),
                            ),
                          ),
                        ),

                        // Checklist Paper Sheet
                        Positioned(
                          top: 14,
                          bottom: 8,
                          left: 7,
                          right: 7,
                          child: Container(
                            padding: const EdgeInsets.fromLTRB(7, 12, 7, 6),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(12),
                              boxShadow: [
                                BoxShadow(
                                  color: const Color(0xFF3B5EDB)
                                      .withValues(alpha: 0.08),
                                  blurRadius: 6,
                                  offset: const Offset(0, 2),
                                ),
                              ],
                            ),
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                              children: [
                                _buildCheckItem(isDone: true),
                                _buildCheckItem(isDone: true),
                                _buildCheckItem(isDone: false),
                              ],
                            ),
                          ),
                        ),

                        // Metallic Golden Clamp with Pin Hole
                        Positioned(
                          top: -6,
                          child: Container(
                            width: 38,
                            height: 18,
                            decoration: BoxDecoration(
                              gradient: const LinearGradient(
                                colors: [
                                  Color(0xFFFFE082),
                                  Color(0xFFFBBF24),
                                  Color(0xFFD97706),
                                ],
                                begin: Alignment.topCenter,
                                end: Alignment.bottomCenter,
                              ),
                              borderRadius: BorderRadius.circular(7),
                              border: Border.all(
                                color: const Color(0xFFFFFBEB),
                                width: 0.8,
                              ),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withValues(alpha: 0.18),
                                  blurRadius: 4,
                                  offset: const Offset(0, 2),
                                ),
                              ],
                            ),
                            child: Center(
                              child: Container(
                                width: 12,
                                height: 5,
                                decoration: BoxDecoration(
                                  color: const Color(0xFFB45309),
                                  borderRadius: BorderRadius.circular(3),
                                ),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),

                // Sparkling celebration rays with scale twinkle
                Positioned(
                  top: 0,
                  right: 0,
                  child: Transform.scale(
                    scale: twinkleScale,
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Transform.rotate(
                          angle: -math.pi / 4,
                          child: Container(
                            width: 4,
                            height: 10,
                            decoration: BoxDecoration(
                              color: const Color(0xFFFFD54F),
                              borderRadius: BorderRadius.circular(2),
                              boxShadow: [
                                BoxShadow(
                                  color: const Color(0xFFFFD54F)
                                      .withValues(alpha: 0.6),
                                  blurRadius: 4,
                                ),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(width: 2),
                        Transform.rotate(
                          angle: math.pi / 8,
                          child: Container(
                            width: 3.5,
                            height: 14,
                            decoration: BoxDecoration(
                              color: const Color(0xFFFFCA28),
                              borderRadius: BorderRadius.circular(2),
                              boxShadow: [
                                BoxShadow(
                                  color: const Color(0xFFFFCA28)
                                      .withValues(alpha: 0.6),
                                  blurRadius: 4,
                                ),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(width: 2),
                        Transform.rotate(
                          angle: math.pi / 3,
                          child: Container(
                            width: 3.5,
                            height: 9,
                            decoration: BoxDecoration(
                              color: const Color(0xFFFFD54F),
                              borderRadius: BorderRadius.circular(2),
                              boxShadow: [
                                BoxShadow(
                                  color: const Color(0xFFFFD54F)
                                      .withValues(alpha: 0.6),
                                  blurRadius: 4,
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    ),
  ),
);
  }

  Widget _buildCheckItem({required bool isDone}) {
    return Row(
      children: [
        Container(
          width: 15,
          height: 15,
          decoration: BoxDecoration(
            color: isDone ? const Color(0xFF10B981) : const Color(0xFFE2E8F0),
            borderRadius: BorderRadius.circular(4.5),
            boxShadow: isDone
                ? [
                    BoxShadow(
                      color: const Color(0xFF10B981).withValues(alpha: 0.35),
                      blurRadius: 4,
                      offset: const Offset(0, 1.5),
                    ),
                  ]
                : null,
          ),
          child: isDone
              ? const Icon(
                  Icons.check_rounded,
                  size: 11,
                  color: Colors.white,
                )
              : null,
        ),
        const SizedBox(width: 6),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                height: 3.5,
                width: double.infinity,
                decoration: BoxDecoration(
                  color: isDone
                      ? const Color(0xFF93C5FD)
                      : const Color(0xFFCBD5E1),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const SizedBox(height: 2.5),
              Container(
                height: 3,
                width: 28,
                decoration: BoxDecoration(
                  color: isDone
                      ? const Color(0xFFBFDBFE)
                      : const Color(0xFFE2E8F0),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _GlossPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..shader = LinearGradient(
        begin: Alignment.topRight,
        end: Alignment.bottomLeft,
        colors: [
          Colors.white.withValues(alpha: 0.4),
          Colors.white.withValues(alpha: 0.0),
        ],
        stops: const [0.0, 0.55],
      ).createShader(Rect.fromLTWH(0, 0, size.width, size.height));

    final path = Path()
      ..moveTo(size.width * 0.3, 0)
      ..lineTo(size.width, 0)
      ..lineTo(size.width, size.height * 0.7)
      ..close();

    canvas.drawPath(path, paint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
