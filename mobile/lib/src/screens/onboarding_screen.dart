import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../app.dart';
import '../widgets/motion.dart';
import '../widgets/task_clipboard_graphic.dart';

class OnboardingScreen extends StatefulWidget {
  const OnboardingScreen({
    super.key,
    required this.onGetStarted,
    required this.onSignIn,
  });

  final VoidCallback onGetStarted;
  final VoidCallback onSignIn;

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  final PageController _pageController = PageController();
  int _currentPage = 0;

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  void _nextPage() {
    if (_currentPage < 2) {
      HapticFeedback.selectionClick();
      _pageController.nextPage(
        duration: const Duration(milliseconds: 380),
        curve: Curves.easeOutCubic,
      );
    } else {
      HapticFeedback.mediumImpact();
      widget.onGetStarted();
    }
  }

  void _skip() {
    HapticFeedback.selectionClick();
    _pageController.animateToPage(
      2,
      duration: const Duration(milliseconds: 320),
      curve: Curves.easeOutCubic,
    );
  }

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.sizeOf(context);

    return Scaffold(
      backgroundColor: surfaceBg,
      body: Stack(
        children: [
          // Ambient Mesh Glow 1 (Top Left)
          Positioned(
            top: -90,
            left: -80,
            child: Container(
              width: 280,
              height: 280,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(
                  colors: [
                    brandPurple.withValues(alpha: 0.16),
                    brandPurple.withValues(alpha: 0.0),
                  ],
                ),
              ),
            ),
          ),

          // Ambient Mesh Glow 2 (Bottom Right)
          Positioned(
            bottom: -80,
            right: -70,
            child: Container(
              width: 300,
              height: 300,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(
                  colors: [
                    const Color(0xFF3864ED).withValues(alpha: 0.12),
                    const Color(0xFF3864ED).withValues(alpha: 0.0),
                  ],
                ),
              ),
            ),
          ),

          SafeArea(
            child: Column(
              children: [
                // Top Navigation Bar
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 12),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      // Brand Logo & Title
                      Row(
                        children: [
                          FloatingLevitation(
                            distance: 3.0,
                            duration: const Duration(milliseconds: 2200),
                            child: Container(
                              width: 38,
                              height: 38,
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(12),
                                boxShadow: [
                                  BoxShadow(
                                    color: brandPurple.withValues(alpha: 0.22),
                                    blurRadius: 10,
                                    offset: const Offset(0, 3),
                                  ),
                                ],
                              ),
                              child: ClipRRect(
                                borderRadius: BorderRadius.circular(12),
                                child: Image.asset(
                                  'assets/logo/app_logo.png',
                                  width: 38,
                                  height: 38,
                                  fit: BoxFit.contain,
                                  errorBuilder: (_, _, _) => Container(
                                    color: brandPurple,
                                    child: const Icon(
                                      Icons.check_rounded,
                                      color: Colors.white,
                                      size: 22,
                                    ),
                                  ),
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(width: 10),
                          const Text(
                            'Task Manager',
                            style: TextStyle(
                              fontSize: 19,
                              fontWeight: FontWeight.w800,
                              color: Color(0xFF1B1D28),
                              letterSpacing: -0.3,
                            ),
                          ),
                        ],
                      ),

                      // Skip Button with Animated Opacity
                      AnimatedOpacity(
                        opacity: _currentPage < 2 ? 1.0 : 0.0,
                        duration: const Duration(milliseconds: 200),
                        child: TextButton(
                          onPressed: _currentPage < 2 ? _skip : null,
                          style: TextButton.styleFrom(
                            foregroundColor: const Color(0xFF8E95A5),
                            textStyle: const TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                          child: const Text('Skip'),
                        ),
                      ),
                    ],
                  ),
                ),

                // Main Carousel (PageView)
                Expanded(
                  child: PageView(
                    controller: _pageController,
                    onPageChanged: (index) => setState(() => _currentPage = index),
                    physics: const BouncingScrollPhysics(),
                    children: [
                      _buildSlide(
                        badge: '⚡ EFFORTLESS PRODUCTIVITY',
                        title: 'Plan, Focus &\nAccomplish More',
                        subtitle:
                            'Capture tasks in seconds, organize priorities with ease, and turn daily goals into tangible accomplishments.',
                        visual: _buildHeroVisual(size),
                      ),
                      _buildSlide(
                        badge: '🎯 COLOR-CODED CLARITY',
                        title: 'Priority First,\nZero Distractions',
                        subtitle:
                            'Smart sections for High Priority, In Progress, and Todo ensure you always know what demands your attention next.',
                        visual: _buildPriorityVisual(),
                      ),
                      _buildSlide(
                        badge: '📈 VISUAL PROGRESS & FOCUS',
                        title: 'Celebrate Every\nMilestone',
                        subtitle:
                            'Real-time completion gauges, swipe-to-complete shortcuts, and deep focus timers keep your momentum thriving.',
                        visual: _buildProgressVisual(),
                      ),
                    ],
                  ),
                ),

                // Bottom Controls: Indicators and Action Buttons
                Padding(
                  padding: const EdgeInsets.fromLTRB(24, 8, 24, 24),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      // Smooth Expanding Page Dots Indicator
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: List.generate(3, (index) {
                          final isActive = index == _currentPage;
                          return AnimatedContainer(
                            duration: const Duration(milliseconds: 280),
                            curve: Curves.easeOutBack,
                            margin: const EdgeInsets.symmetric(horizontal: 4),
                            width: isActive ? 28 : 8,
                            height: 8,
                            decoration: BoxDecoration(
                              color: isActive
                                  ? brandPurple
                                  : const Color(0xFFD8DCE8),
                              borderRadius: BorderRadius.circular(4),
                              boxShadow: isActive
                                  ? [
                                      BoxShadow(
                                        color: brandPurple.withValues(alpha: 0.35),
                                        blurRadius: 6,
                                        offset: const Offset(0, 2),
                                      ),
                                    ]
                                  : null,
                            ),
                          );
                        }),
                      ),
                      const SizedBox(height: 24),

                      // Primary Button ("Continue" or "Get Started")
                      BouncingInteractive(
                        onTap: _nextPage,
                        scaleFactor: 0.96,
                        child: Container(
                          width: double.infinity,
                          height: 56,
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(18),
                            gradient: const LinearGradient(
                              colors: [
                                Color(0xFF6B5CFF),
                                Color(0xFF5B4DFF),
                              ],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                            boxShadow: [
                              BoxShadow(
                                color: brandPurple.withValues(alpha: 0.38),
                                blurRadius: 18,
                                offset: const Offset(0, 6),
                              ),
                            ],
                          ),
                          child: Center(
                            child: AnimatedSwitcher(
                              duration: const Duration(milliseconds: 200),
                              transitionBuilder: (child, anim) => FadeTransition(
                                opacity: anim,
                                child: ScaleTransition(scale: anim, child: child),
                              ),
                              child: Row(
                                key: ValueKey(_currentPage == 2),
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Text(
                                    _currentPage == 2 ? 'Get Started' : 'Continue',
                                    style: const TextStyle(
                                      color: Colors.white,
                                      fontSize: 16,
                                      fontWeight: FontWeight.w700,
                                      letterSpacing: -0.2,
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  Icon(
                                    _currentPage == 2
                                        ? Icons.rocket_launch_rounded
                                        : Icons.arrow_forward_rounded,
                                    color: Colors.white,
                                    size: 20,
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(height: 14),

                      // Secondary Sign In link
                      BouncingInteractive(
                        onTap: () {
                          HapticFeedback.selectionClick();
                          widget.onSignIn();
                        },
                        scaleFactor: 0.96,
                        child: Padding(
                          padding: const EdgeInsets.symmetric(vertical: 4),
                          child: RichText(
                            text: const TextSpan(
                              style: TextStyle(
                                fontSize: 13.5,
                                color: Color(0xFF7E8494),
                                fontWeight: FontWeight.w500,
                              ),
                              children: [
                                TextSpan(text: 'Already have an account? '),
                                TextSpan(
                                  text: 'Sign In',
                                  style: TextStyle(
                                    color: brandPurple,
                                    fontWeight: FontWeight.w700,
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
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSlide({
    required String badge,
    required String title,
    required String subtitle,
    required Widget visual,
  }) {
    return SingleChildScrollView(
      physics: const BouncingScrollPhysics(),
      padding: const EdgeInsets.symmetric(horizontal: 24),
      child: Column(
        children: [
          const SizedBox(height: 10),
          // Illustration / Visual Showcase
          SizedBox(
            height: 270,
            child: Center(child: visual),
          ),
          const SizedBox(height: 18),

          // Pill Badge
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
            decoration: BoxDecoration(
              color: const Color(0xFFECE9FF),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(
              badge,
              style: const TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w800,
                color: brandPurple,
                letterSpacing: 0.6,
              ),
            ),
          ),
          const SizedBox(height: 14),

          // Title
          Text(
            title,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 28,
              fontWeight: FontWeight.w900,
              color: Color(0xFF1B1D28),
              height: 1.18,
              letterSpacing: -0.6,
            ),
          ),
          const SizedBox(height: 12),

          // Subtitle
          Text(
            subtitle,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 14.5,
              fontWeight: FontWeight.w400,
              color: Color(0xFF7E8494),
              height: 1.45,
            ),
          ),
          const SizedBox(height: 16),
        ],
      ),
    );
  }

  // Visual 1: Hero Graphic with Floating Levitation Parallax
  Widget _buildHeroVisual(Size size) {
    return Stack(
      alignment: Alignment.center,
      clipBehavior: Clip.none,
      children: [
        // Decorative Soft Gradient Circle
        Container(
          width: 220,
          height: 220,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            gradient: LinearGradient(
              colors: [
                brandPurple.withValues(alpha: 0.12),
                const Color(0xFF4361EE).withValues(alpha: 0.05),
              ],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
          ),
        ),

        // Main 3D Artwork with Floating Levitation
        FloatingLevitation(
          distance: 6.0,
          duration: const Duration(milliseconds: 2600),
          child: Image.asset(
            'assets/images/task_hero.png',
            width: 210,
            height: 210,
            fit: BoxFit.contain,
            errorBuilder: (_, _, _) => const TaskClipboardGraphic(size: 150),
          ),
        ),

        // Floating Micro Card 1: "3 of 5 Done"
        Positioned(
          bottom: 12,
          right: 4,
          child: FloatingLevitation(
            distance: 4.0,
            duration: const Duration(milliseconds: 2300),
            delay: const Duration(milliseconds: 250),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.08),
                    blurRadius: 14,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 22,
                    height: 22,
                    decoration: const BoxDecoration(
                      color: Color(0xFF10B981),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.check_rounded,
                      color: Colors.white,
                      size: 14,
                    ),
                  ),
                  const SizedBox(width: 8),
                  const Text(
                    '3 of 5 Tasks Done',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: Color(0xFF1B1D28),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),

        // Floating Micro Card 2: "⚡ Daily Goal Active"
        Positioned(
          top: 14,
          left: 4,
          child: FloatingLevitation(
            distance: 4.0,
            duration: const Duration(milliseconds: 2500),
            delay: const Duration(milliseconds: 600),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
                boxShadow: [
                  BoxShadow(
                    color: brandPurple.withValues(alpha: 0.14),
                    blurRadius: 10,
                    offset: const Offset(0, 3),
                  ),
                ],
              ),
              child: const Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text('⚡', style: TextStyle(fontSize: 13)),
                  SizedBox(width: 6),
                  Text(
                    'Daily Goal Active',
                    style: TextStyle(
                      fontSize: 11.5,
                      fontWeight: FontWeight.w700,
                      color: brandPurple,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }

  // Visual 2: Staggered Cascade Task Cards with Sparkle Burst
  Widget _buildPriorityVisual() {
    return Container(
      width: 290,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 20,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          _buildMiniTask(
            index: 0,
            accentColor: const Color(0xFFEF4444),
            badgeColor: const Color(0xFFEF4444),
            badgeBg: const Color(0xFFFEE2E2),
            badgeText: 'High',
            title: 'Design task management UI',
            date: 'Today, Nov 14',
            isDone: false,
          ),
          const SizedBox(height: 10),
          _buildMiniTask(
            index: 1,
            accentColor: brandPurple,
            badgeColor: const Color(0xFFD97706),
            badgeBg: const Color(0xFFFEF3C7),
            badgeText: 'Medium',
            title: 'Implement core features',
            date: 'Nov 14 - Nov 16',
            isDone: false,
          ),
          const SizedBox(height: 10),
          _buildMiniTask(
            index: 2,
            accentColor: const Color(0xFF10B981),
            badgeColor: const Color(0xFF10B981),
            badgeBg: const Color(0xFFD1FAE5),
            badgeText: 'Done',
            title: 'Create task list flow',
            date: 'Nov 13',
            isDone: true,
          ),
        ],
      ),
    );
  }

  Widget _buildMiniTask({
    required int index,
    required Color accentColor,
    required Color badgeColor,
    required Color badgeBg,
    required String badgeText,
    required String title,
    required String date,
    required bool isDone,
  }) {
    return TweenAnimationBuilder<double>(
      tween: Tween(begin: 0.0, end: 1.0),
      duration: Duration(milliseconds: 320 + index * 100),
      curve: Curves.easeOutCubic,
      builder: (context, value, child) {
        return Transform.translate(
          offset: Offset(16 * (1.0 - value), 0),
          child: Opacity(opacity: value.clamp(0.0, 1.0), child: child),
        );
      },
      child: Container(
        height: 64,
        decoration: BoxDecoration(
          color: const Color(0xFFF9FAFD),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: const Color(0xFFEFF1F7)),
        ),
        child: Stack(
          children: [
            // Left Accent Strip
            Positioned(
              left: 0,
              top: 0,
              bottom: 0,
              child: Container(
                width: 4,
                decoration: BoxDecoration(
                  color: accentColor,
                  borderRadius: const BorderRadius.horizontal(
                    left: Radius.circular(14),
                  ),
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              child: Row(
                children: [
                  const SizedBox(width: 4),
                  // Checkbox with Sparkle Burst when done
                  TaskSparkleBurst(
                    trigger: isDone,
                    child: Container(
                      width: 20,
                      height: 20,
                      decoration: BoxDecoration(
                        color: isDone ? const Color(0xFF10B981) : Colors.white,
                        borderRadius: BorderRadius.circular(6),
                        border: Border.all(
                          color: isDone
                              ? const Color(0xFF10B981)
                              : const Color(0xFFCBD5E1),
                          width: 1.5,
                        ),
                      ),
                      child: isDone
                          ? const Icon(
                              Icons.check_rounded,
                              size: 14,
                              color: Colors.white,
                            )
                          : null,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          title,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            fontSize: 12.5,
                            fontWeight: FontWeight.w700,
                            color: isDone
                                ? const Color(0xFF94A3B8)
                                : const Color(0xFF1B1D28),
                            decoration: isDone ? TextDecoration.lineThrough : null,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          date,
                          style: const TextStyle(
                            fontSize: 10.5,
                            color: Color(0xFF8E95A5),
                          ),
                        ),
                      ],
                    ),
                  ),
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: badgeBg,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      badgeText,
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w700,
                        color: badgeColor,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  // Visual 3: Smooth Count-up Donut & Pop-in Metrics
  Widget _buildProgressVisual() {
    return Container(
      width: 290,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
        gradient: const LinearGradient(
          colors: [
            Color(0xFF5345FF),
            Color(0xFF6B5CFF),
            Color(0xFF7E72FF),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        boxShadow: [
          BoxShadow(
            color: brandPurple.withValues(alpha: 0.35),
            blurRadius: 22,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            children: [
              // Circular Donut with Count-up animation
              SizedBox(
                width: 68,
                height: 68,
                child: TweenAnimationBuilder<double>(
                  tween: Tween(begin: 0.0, end: 0.85),
                  duration: const Duration(milliseconds: 800),
                  curve: Curves.easeOutCubic,
                  builder: (context, value, _) {
                    return Stack(
                      alignment: Alignment.center,
                      children: [
                        CustomPaint(
                          size: const Size(68, 68),
                          painter: _OnboardingDonutPainter(
                            progress: value,
                            backgroundColor:
                                Colors.white.withValues(alpha: 0.22),
                            progressColor: Colors.white,
                            strokeWidth: 7,
                          ),
                        ),
                        Text(
                          '${(value * 100).round()}%',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 17,
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                      ],
                    );
                  },
                ),
              ),
              const SizedBox(width: 14),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Your Tasks',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    SizedBox(height: 3),
                    Text(
                      '6 of 7 completed today',
                      style: TextStyle(
                        color: Colors.white70,
                        fontSize: 12,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 18),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(14),
            ),
            child: const Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _MiniMetric(index: 0, icon: '🔥', value: '5 Days', label: 'Streak'),
                _MiniMetric(index: 1, icon: '⏱️', value: '45m', label: 'Focus'),
                _MiniMetric(index: 2, icon: '🎯', value: '100%', label: 'Quality'),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _MiniMetric extends StatelessWidget {
  const _MiniMetric({
    required this.index,
    required this.icon,
    required this.value,
    required this.label,
  });

  final int index;
  final String icon;
  final String value;
  final String label;

  @override
  Widget build(BuildContext context) {
    return TweenAnimationBuilder<double>(
      tween: Tween(begin: 0.0, end: 1.0),
      duration: Duration(milliseconds: 360 + index * 90),
      curve: Curves.easeOutBack,
      builder: (context, val, child) => Transform.scale(
        scale: val,
        child: child,
      ),
      child: Column(
        children: [
          Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(icon, style: const TextStyle(fontSize: 12)),
              const SizedBox(width: 4),
              Text(
                value,
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w800,
                  fontSize: 12.5,
                ),
              ),
            ],
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: const TextStyle(
              color: Colors.white70,
              fontSize: 10,
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }
}

class _OnboardingDonutPainter extends CustomPainter {
  const _OnboardingDonutPainter({
    required this.progress,
    required this.backgroundColor,
    required this.progressColor,
    required this.strokeWidth,
  });

  final double progress;
  final Color backgroundColor;
  final Color progressColor;
  final double strokeWidth;

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final radius = (size.width - strokeWidth) / 2;

    final bgPaint = Paint()
      ..color = backgroundColor
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth;
    canvas.drawCircle(center, radius, bgPaint);

    final fgPaint = Paint()
      ..color = progressColor
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth
      ..strokeCap = StrokeCap.round;

    const startAngle = -math.pi / 2;
    final sweepAngle = 2 * math.pi * progress;
    canvas.drawArc(
      Rect.fromCircle(center: center, radius: radius),
      startAngle,
      sweepAngle,
      false,
      fgPaint,
    );
  }

  @override
  bool shouldRepaint(covariant _OnboardingDonutPainter old) =>
      old.progress != progress;
}
