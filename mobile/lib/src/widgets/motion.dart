import 'dart:math' as math;
import 'package:flutter/material.dart';

Duration motionDuration(BuildContext context, [int milliseconds = 220]) =>
    MediaQuery.disableAnimationsOf(context)
    ? Duration.zero
    : Duration(milliseconds: milliseconds);

class BouncingInteractive extends StatefulWidget {
  const BouncingInteractive({
    super.key,
    required this.child,
    this.onTap,
    this.scaleFactor = 0.96,
  });

  final Widget child;
  final VoidCallback? onTap;
  final double scaleFactor;

  @override
  State<BouncingInteractive> createState() => _BouncingInteractiveState();
}

class _BouncingInteractiveState extends State<BouncingInteractive>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;
  late final Animation<double> _scale;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 100),
      reverseDuration: const Duration(milliseconds: 180),
    );
    _scale = Tween<double>(begin: 1.0, end: widget.scaleFactor).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeOutCubic),
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (MediaQuery.disableAnimationsOf(context)) {
      return GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTap: widget.onTap,
        child: widget.child,
      );
    }
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTapDown: (_) => _controller.forward(),
      onTapUp: (_) => _controller.reverse(),
      onTapCancel: () => _controller.reverse(),
      onTap: widget.onTap,
      child: ScaleTransition(
        scale: _scale,
        child: widget.child,
      ),
    );
  }
}

class MotionSwitcher extends StatelessWidget {
  const MotionSwitcher({super.key, required this.child});
  final Widget child;
  @override
  Widget build(BuildContext context) => AnimatedSwitcher(
    duration: motionDuration(context),
    reverseDuration: motionDuration(context, 140),
    switchInCurve: Curves.easeOutCubic,
    switchOutCurve: Curves.easeInCubic,
    layoutBuilder: (current, previous) => Stack(
      alignment: Alignment.topCenter,
      children: [
        for (final child in previous)
          ExcludeSemantics(child: IgnorePointer(child: child)),
        ?current,
      ],
    ),
    transitionBuilder: (child, animation) => FadeTransition(
      opacity: animation,
      child: SlideTransition(
        position: Tween(
          begin: const Offset(0, .025),
          end: Offset.zero,
        ).animate(animation),
        child: child,
      ),
    ),
    child: child,
  );
}

class TaskEntrance extends StatelessWidget {
  const TaskEntrance({super.key, required this.child, this.index = 0});
  final Widget child;
  final int index;

  @override
  Widget build(BuildContext context) {
    if (MediaQuery.disableAnimationsOf(context)) return child;
    return TweenAnimationBuilder<double>(
      tween: Tween(begin: 0.0, end: 1.0),
      duration: Duration(milliseconds: 260 + index.clamp(0, 6) * 30),
      curve: Curves.easeOutCubic,
      child: child,
      builder: (context, value, child) => Opacity(
        opacity: value.clamp(0.0, 1.0),
        child: Transform.translate(
          offset: Offset(0, 14 * (1.0 - value)),
          child: Transform.scale(
            scale: 0.96 + (0.04 * value),
            child: child,
          ),
        ),
      ),
    );
  }
}

class TaskSparkleBurst extends StatefulWidget {
  const TaskSparkleBurst({
    super.key,
    required this.trigger,
    required this.child,
    this.color = const Color(0xFF10B981),
  });

  final bool trigger;
  final Widget child;
  final Color color;

  @override
  State<TaskSparkleBurst> createState() => _TaskSparkleBurstState();
}

class _TaskSparkleBurstState extends State<TaskSparkleBurst>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 480),
    );
    if (widget.trigger) _controller.forward();
  }

  @override
  void didUpdateWidget(covariant TaskSparkleBurst oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (!oldWidget.trigger && widget.trigger) {
      _controller.forward(from: 0.0);
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (MediaQuery.disableAnimationsOf(context)) {
      return widget.child;
    }
    return AnimatedBuilder(
      animation: _controller,
      builder: (context, child) {
        final progress = _controller.value;
        return Stack(
          alignment: Alignment.center,
          clipBehavior: Clip.none,
          children: [
            child!,
            if (progress > 0.0 && progress < 1.0)
              Positioned.fill(
                child: CustomPaint(
                  painter: _SparkleParticlesPainter(
                    progress: progress,
                    color: widget.color,
                  ),
                ),
              ),
          ],
        );
      },
      child: widget.child,
    );
  }
}

class _SparkleParticlesPainter extends CustomPainter {
  const _SparkleParticlesPainter({
    required this.progress,
    required this.color,
  });

  final double progress;
  final Color color;

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    const particleCount = 8;
    final distance = 16.0 * Curves.easeOutBack.transform(progress.clamp(0.0, 1.0));
    final particleRadius = (2.2 * (1.0 - progress)).clamp(0.0, 2.5);
    final alpha = ((1.0 - progress) * 255).round().clamp(0, 255);

    final colors = [
      color.withAlpha(alpha),
      const Color(0xFFFFCA28).withAlpha(alpha), // gold
      const Color(0xFF5B4DFF).withAlpha(alpha), // purple
      color.withAlpha(alpha),
      const Color(0xFF06D6A0).withAlpha(alpha), // mint
      const Color(0xFFFF6B6B).withAlpha(alpha), // coral
      color.withAlpha(alpha),
      const Color(0xFFFFD166).withAlpha(alpha),
    ];

    for (var i = 0; i < particleCount; i++) {
      final angle = (i * 2 * math.pi / particleCount) + (progress * 0.35);
      final offset = Offset(
        center.dx + distance * math.cos(angle),
        center.dy + distance * math.sin(angle),
      );
      final paint = Paint()
        ..color = colors[i % colors.length]
        ..style = PaintingStyle.fill;
      canvas.drawCircle(offset, particleRadius, paint);
    }
  }

  @override
  bool shouldRepaint(covariant _SparkleParticlesPainter old) =>
      old.progress != progress;
}

class PulsingGlowFAB extends StatefulWidget {
  const PulsingGlowFAB({super.key, required this.child});
  final Widget child;

  @override
  State<PulsingGlowFAB> createState() => _PulsingGlowFABState();
}

class _PulsingGlowFABState extends State<PulsingGlowFAB>
    with SingleTickerProviderStateMixin {
  late final AnimationController _pulseController;

  @override
  void initState() {
    super.initState();
    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2400),
    )..repeat(reverse: true);
  }

  @override
  void dispose() {
    _pulseController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (MediaQuery.disableAnimationsOf(context)) return widget.child;
    return AnimatedBuilder(
      animation: _pulseController,
      builder: (context, child) {
        final glowScale = 1.0 + (_pulseController.value * 0.04);
        return Transform.scale(
          scale: glowScale,
          child: child,
        );
      },
      child: widget.child,
    );
  }
}

class AnimatedTaskProgress extends StatelessWidget {
  const AnimatedTaskProgress({
    super.key,
    required this.value,
    this.color,
    this.backgroundColor,
    this.height = 4,
  });
  final double value, height;
  final Color? color, backgroundColor;
  @override
  Widget build(BuildContext context) => TweenAnimationBuilder<double>(
    tween: Tween(begin: 0, end: value.clamp(0, 1)),
    duration: motionDuration(context, 360),
    curve: Curves.easeOutCubic,
    builder: (context, progress, _) => LinearProgressIndicator(
      value: progress,
      color: color,
      backgroundColor: backgroundColor,
      minHeight: height,
      borderRadius: BorderRadius.circular(8),
      semanticsLabel: 'Task progress',
      semanticsValue: '${(value * 100).round()}%',
    ),
  );
}

class TaskLoadingPlaceholder extends StatelessWidget {
  const TaskLoadingPlaceholder({super.key});
  @override
  Widget build(BuildContext context) => Semantics(
    label: 'Loading your tasks',
    liveRegion: true,
    child: ExcludeSemantics(
      child: Column(
        children: [
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 20),
            child: Text(
              'Getting your day ready…',
              style: TextStyle(color: Colors.blueGrey),
            ),
          ),
          for (var i = 0; i < 3; i++)
            Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: Card(
                child: Padding(
                  padding: const EdgeInsets.all(20),
                  child: Row(
                    children: [
                      Container(
                        width: 28,
                        height: 28,
                        decoration: const BoxDecoration(
                          shape: BoxShape.circle,
                          color: Color(0xFFEDF1F8),
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            _bar(double.infinity),
                            const SizedBox(height: 12),
                            _bar(100),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
        ],
      ),
    ),
  );
  Widget _bar(double width) => Container(
    width: width,
    height: 12,
    decoration: BoxDecoration(
      color: const Color(0xFFEDF1F8),
      borderRadius: BorderRadius.circular(6),
    ),
  );
}

class ShakeWidget extends StatefulWidget {
  const ShakeWidget({
    super.key,
    required this.child,
    required this.shakeTrigger,
  });

  final Widget child;
  final bool shakeTrigger;

  @override
  State<ShakeWidget> createState() => _ShakeWidgetState();
}

class _ShakeWidgetState extends State<ShakeWidget>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;
  late final Animation<double> _offsetAnimation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 380),
    );
    _offsetAnimation = TweenSequence<double>([
      TweenSequenceItem(tween: Tween(begin: 0.0, end: -9.0), weight: 1),
      TweenSequenceItem(tween: Tween(begin: -9.0, end: 9.0), weight: 2),
      TweenSequenceItem(tween: Tween(begin: 9.0, end: -5.0), weight: 2),
      TweenSequenceItem(tween: Tween(begin: -5.0, end: 5.0), weight: 2),
      TweenSequenceItem(tween: Tween(begin: 5.0, end: 0.0), weight: 1),
    ]).animate(CurvedAnimation(parent: _controller, curve: Curves.easeInOut));
  }

  @override
  void didUpdateWidget(covariant ShakeWidget oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (!oldWidget.shakeTrigger && widget.shakeTrigger) {
      _controller.forward(from: 0.0);
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (MediaQuery.disableAnimationsOf(context)) return widget.child;
    return AnimatedBuilder(
      animation: _offsetAnimation,
      builder: (context, child) => Transform.translate(
        offset: Offset(_offsetAnimation.value, 0),
        child: child,
      ),
      child: widget.child,
    );
  }
}

class FloatingLevitation extends StatefulWidget {
  const FloatingLevitation({
    super.key,
    required this.child,
    this.distance = 6.0,
    this.duration = const Duration(milliseconds: 2600),
    this.delay = Duration.zero,
  });

  final Widget child;
  final double distance;
  final Duration duration;
  final Duration delay;

  @override
  State<FloatingLevitation> createState() => _FloatingLevitationState();
}

class _FloatingLevitationState extends State<FloatingLevitation>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;
  late final Animation<double> _animation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: widget.duration,
    );
    _animation = Tween<double>(begin: -widget.distance, end: widget.distance)
        .animate(CurvedAnimation(parent: _controller, curve: Curves.easeInOutSine));

    if (widget.delay == Duration.zero) {
      _controller.repeat(reverse: true);
    } else {
      Future.delayed(widget.delay, () {
        if (mounted) _controller.repeat(reverse: true);
      });
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (MediaQuery.disableAnimationsOf(context)) return widget.child;
    return AnimatedBuilder(
      animation: _animation,
      builder: (context, child) => Transform.translate(
        offset: Offset(0, _animation.value),
        child: child,
      ),
      child: widget.child,
    );
  }
}

