import 'dart:async';
import 'package:flutter/material.dart';
import '../state/streak_manager.dart';
import 'motion.dart';
import 'streak_widgets.dart';
import 'task_clipboard_graphic.dart';

/// Interactive and auto-playing Hero Slideshow / Carousel on Home Dashboard
class HomeHeroSlideshow extends StatefulWidget {
  const HomeHeroSlideshow({
    super.key,
    required this.total,
    required this.completed,
    required this.streak,
    this.onOpenTasks,
    this.onOpenFocus,
    this.onOpenStreak,
  });

  final int total;
  final int completed;
  final StreakManager streak;
  final VoidCallback? onOpenTasks;
  final VoidCallback? onOpenFocus;
  final VoidCallback? onOpenStreak;

  @override
  State<HomeHeroSlideshow> createState() => _HomeHeroSlideshowState();
}

class _HomeHeroSlideshowState extends State<HomeHeroSlideshow> {
  late final PageController _pageController;
  int _currentPage = 0;
  Timer? _timer;

  static const int _slideCount = 3;

  @override
  void initState() {
    super.initState();
    _pageController = PageController();
    _startTimer();
  }

  void _startTimer() {
    _timer?.cancel();
    if (isRunningInTest) return;
    _timer = Timer.periodic(const Duration(milliseconds: 4800), (_) {
      if (!mounted || !_pageController.hasClients) return;
      final next = (_currentPage + 1) % _slideCount;
      _pageController.animateToPage(
        next,
        duration: const Duration(milliseconds: 650),
        curve: Curves.easeInOutCubic,
      );
    });
  }

  void _pauseTimer() => _timer?.cancel();
  void _resumeTimer() => _startTimer();

  @override
  void dispose() {
    _timer?.cancel();
    _pageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        SizedBox(
          height: 180,
          child: GestureDetector(
            onPanDown: (_) => _pauseTimer(),
            onPanCancel: () => _resumeTimer(),
            onPanEnd: (_) => _resumeTimer(),
            child: PageView(
              controller: _pageController,
              onPageChanged: (idx) {
                setState(() => _currentPage = idx);
              },
              children: [
                // Slide 0: Organization & Progress (Original Mockup)
                _buildSlide(
                  gradient: const [
                    Color(0xFF2563EB),
                    Color(0xFF4F46E5),
                    Color(0xFF7C3AED),
                  ],
                  title: 'Stay Organized,\nAchieve More',
                  subtitle:
                      'Manage your tasks, projects, and goals in one place.',
                  badgeText: 'TASKFLOW PRO',
                  graphic: TaskClipboardGraphic(
                    completedCount: widget.completed,
                    totalCount: widget.total,
                  ),
                  onTap: widget.onOpenTasks,
                ),

                // Slide 1: Focus Timer & Deep Work
                _buildSlide(
                  gradient: const [
                    Color(0xFF4F46E5),
                    Color(0xFF7C3AED),
                    Color(0xFF9333EA),
                  ],
                  title: 'Deep Focus,\nZero Distraction',
                  subtitle:
                      'Power up your sessions with the smart focus timer.',
                  badgeText: 'FOCUS TIMER',
                  graphic: _buildFocusTimerGraphic(),
                  onTap: widget.onOpenFocus,
                ),

                // Slide 2: Daily Streak & Consistency
                _buildSlide(
                  gradient: const [
                    Color(0xFFB45309),
                    Color(0xFFD97706),
                    Color(0xFFEA580C),
                  ],
                  title: 'Keep The Fire,\nNever Break Streak',
                  subtitle:
                      '${widget.streak.streakCount} days active! Consistency drives real success.',
                  badgeText: 'STREAK ACTIVE 🔥',
                  graphic: _buildStreakHeroGraphic(),
                  onTap: widget.onOpenStreak,
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildSlide({
    required List<Color> gradient,
    required String title,
    required String subtitle,
    required String badgeText,
    required Widget graphic,
    VoidCallback? onTap,
  }) {
    return BouncingInteractive(
      onTap: onTap,
      scaleFactor: 0.98,
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 2),
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: gradient,
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          borderRadius: BorderRadius.circular(26),
          boxShadow: [
            BoxShadow(
              color: gradient[1].withValues(alpha: 0.35),
              blurRadius: 20,
              offset: const Offset(0, 8),
            ),
          ],
        ),
        child: Stack(
          children: [
            // Background ambient shapes
            Positioned.fill(
              child: ClipRRect(
                borderRadius: BorderRadius.circular(26),
                child: CustomPaint(
                  painter: _WavySlideBackgroundPainter(),
                ),
              ),
            ),

            // Content
            Padding(
              padding: const EdgeInsets.fromLTRB(18, 14, 12, 12),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  // Left copy
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        // Mini category pill
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 7,
                            vertical: 2,
                          ),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.2),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            badgeText,
                            style: const TextStyle(
                              fontSize: 9,
                              fontWeight: FontWeight.w800,
                              color: Colors.white,
                              letterSpacing: 0.4,
                            ),
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          title,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 18.5,
                            fontWeight: FontWeight.w900,
                            letterSpacing: -0.3,
                            height: 1.2,
                          ),
                        ),
                        const SizedBox(height: 3),
                        Text(
                          subtitle,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            color: Color(0xFFE0E7FF),
                            fontSize: 11,
                            fontWeight: FontWeight.w400,
                            height: 1.3,
                          ),
                        ),
                        const SizedBox(height: 8),

                        // Interactive Pagination Dots
                        Row(
                          children: List.generate(_slideCount, (index) {
                            final isActive = _currentPage == index;
                            return GestureDetector(
                              onTap: () {
                                _pageController.animateToPage(
                                  index,
                                  duration: const Duration(milliseconds: 400),
                                  curve: Curves.easeOutCubic,
                                );
                              },
                              child: AnimatedContainer(
                                duration: const Duration(milliseconds: 300),
                                curve: Curves.easeOutCubic,
                                margin: const EdgeInsets.only(right: 5),
                                width: isActive ? 20 : 6,
                                height: 5,
                                decoration: BoxDecoration(
                                  color: isActive
                                      ? Colors.white
                                      : Colors.white.withValues(alpha: 0.35),
                                  borderRadius: BorderRadius.circular(3),
                                ),
                              ),
                            );
                          }),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),

                  // Right graphic
                  graphic,
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFocusTimerGraphic() {
    return Container(
      width: 86,
      height: 86,
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.15),
        shape: BoxShape.circle,
        border: Border.all(
          color: Colors.white.withValues(alpha: 0.28),
          width: 2,
        ),
      ),
      child: Stack(
        alignment: Alignment.center,
        children: [
          Container(
            width: 68,
            height: 68,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.2),
              shape: BoxShape.circle,
            ),
          ),
          const Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                Icons.hourglass_top_rounded,
                color: Colors.white,
                size: 28,
              ),
              SizedBox(height: 2),
              Text(
                '25:00',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 11,
                  fontWeight: FontWeight.w900,
                  letterSpacing: -0.2,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStreakHeroGraphic() {
    return SizedBox(
      width: 90,
      height: 90,
      child: Center(
        child: StreakFlameLottie(
          width: 78,
          height: 82,
          fit: BoxFit.contain,
        ),
      ),
    );
  }
}

/// Ambient subtle wavy lines for slide background
class _WavySlideBackgroundPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = Colors.white.withValues(alpha: 0.08)
      ..style = PaintingStyle.fill;

    final path = Path()
      ..moveTo(size.width * 0.45, 0)
      ..quadraticBezierTo(
        size.width * 0.75,
        size.height * 0.45,
        size.width,
        size.height * 0.2,
      )
      ..lineTo(size.width, 0)
      ..close();

    canvas.drawPath(path, paint);

    final path2 = Path()
      ..moveTo(size.width * 0.35, size.height)
      ..quadraticBezierTo(
        size.width * 0.65,
        size.height * 0.55,
        size.width,
        size.height,
      )
      ..close();

    canvas.drawPath(path2, paint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
