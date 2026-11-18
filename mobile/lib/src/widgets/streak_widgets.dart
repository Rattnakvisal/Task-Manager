import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:lottie/lottie.dart';
import '../state/streak_manager.dart';
import 'motion.dart';

/// Renders the Flame - Streak.json Lottie animation with safe asset fallback.
class StreakFlameLottie extends StatelessWidget {
  const StreakFlameLottie({
    super.key,
    this.width = 32,
    this.height = 32,
    this.fit = BoxFit.contain,
  });

  final double width;
  final double height;
  final BoxFit fit;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: width,
      height: height,
      child: Lottie.asset(
        'assets/animations/Flame - Streak.json',
        fit: fit,
        repeat: true,
        animate: true,
        errorBuilder: (context, error, stackTrace) {
          return Lottie.asset(
            'assets/animation/Flame - Streak.json',
            fit: fit,
            repeat: true,
            animate: true,
            errorBuilder: (ctx, err, st) => const Icon(
              Icons.local_fire_department_rounded,
              color: Color(0xFFEA580C),
            ),
          );
        },
      ),
    );
  }
}

/// Interactive Animated Streak Card displayed on the Home Dashboard.
class AnimatedStreakCard extends StatefulWidget {
  const AnimatedStreakCard({
    super.key,
    required this.streak,
    this.onTap,
  });

  final StreakManager streak;
  final VoidCallback? onTap;

  @override
  State<AnimatedStreakCard> createState() => _AnimatedStreakCardState();
}

class _AnimatedStreakCardState extends State<AnimatedStreakCard>
    with SingleTickerProviderStateMixin {
  late final AnimationController _pulseController;
  late final Animation<double> _glowAnimation;
  late final Animation<double> _scaleAnimation;

  @override
  void initState() {
    super.initState();
    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2400),
    )..repeat(reverse: true);

    _glowAnimation = Tween<double>(begin: 0.15, end: 0.42).animate(
      CurvedAnimation(parent: _pulseController, curve: Curves.easeInOutSine),
    );

    _scaleAnimation = Tween<double>(begin: 0.98, end: 1.04).animate(
      CurvedAnimation(parent: _pulseController, curve: Curves.easeInOutSine),
    );
  }

  @override
  void dispose() {
    _pulseController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final count = widget.streak.streakCount;
    final increasedToday = widget.streak.streakIncreasedToday;

    return BouncingInteractive(
      onTap: () {
        widget.onTap?.call();
        showStreakCelebrationSheet(context, widget.streak);
      },
      scaleFactor: 0.96,
      child: AnimatedBuilder(
        animation: _pulseController,
        builder: (context, child) {
          return Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(22),
              border: Border.all(
                color: const Color(0xFFFDE68A).withValues(
                  alpha: 0.5 + 0.5 * _pulseController.value,
                ),
                width: 1.4,
              ),
              boxShadow: [
                BoxShadow(
                  color: const Color(0xFFF59E0B).withValues(
                    alpha: _glowAnimation.value * 0.4,
                  ),
                  blurRadius: 16,
                  offset: const Offset(0, 4),
                ),
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.025),
                  blurRadius: 10,
                  offset: const Offset(0, 3),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Header with live breathing flame
                Row(
                  children: [
                    Container(
                      width: 28,
                      height: 28,
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                          colors: [Color(0xFFFEF3C7), Color(0xFFFDE68A)],
                        ),
                        borderRadius: BorderRadius.circular(8),
                        boxShadow: [
                          BoxShadow(
                            color: const Color(0xFFF59E0B).withValues(
                              alpha: _glowAnimation.value,
                            ),
                            blurRadius: 8,
                          ),
                        ],
                      ),
                      child: Center(
                        child: Transform.scale(
                          scale: _scaleAnimation.value,
                          child: const StreakFlameLottie(
                            width: 20,
                            height: 20,
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Streak',
                            style: TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w800,
                              color: Color(0xFF0F172A),
                              letterSpacing: -0.2,
                            ),
                          ),
                          Text(
                            count == 0
                                ? 'Start today! 🔥'
                                : increasedToday
                                    ? 'Active Today! 🔥'
                                    : 'Keep it up!',
                            style: TextStyle(
                              fontSize: 10.5,
                              fontWeight: increasedToday || count == 0
                                  ? FontWeight.w700
                                  : FontWeight.w500,
                              color: increasedToday || count == 0
                                  ? const Color(0xFFD97706)
                                  : const Color(0xFF64748B),
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),

                // Center animated Flame - Streak.json & Count (completely borderless)
                Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Transform.scale(
                        scale: _scaleAnimation.value,
                        child: const StreakFlameLottie(
                          width: 62,
                          height: 66,
                          fit: BoxFit.contain,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        crossAxisAlignment: CrossAxisAlignment.baseline,
                        textBaseline: TextBaseline.alphabetic,
                        children: [
                          Text(
                            '$count',
                            style: const TextStyle(
                              fontSize: 24,
                              fontWeight: FontWeight.w900,
                              color: Color(0xFFEA580C),
                              letterSpacing: -0.5,
                              height: 1.0,
                            ),
                          ),
                          const SizedBox(width: 4),
                          Text(
                            count == 1 ? 'Day' : 'Days',
                            style: const TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w800,
                              color: Color(0xFFD97706),
                              height: 1.0,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}

/// Particle model for realistic fire ember floating animation
class _FireEmber {
  _FireEmber({
    required this.x,
    required this.y,
    required this.vx,
    required this.vy,
    required this.size,
    required this.color,
    required this.life,
    required this.maxLife,
  });

  double x;
  double y;
  double vx;
  double vy;
  double size;
  Color color;
  double life;
  double maxLife;

  void update() {
    x += vx;
    y += vy;
    life -= 0.018;
  }
}

/// Custom painter for animated particles & flames in celebration modal
class _EmberParticlesPainter extends CustomPainter {
  _EmberParticlesPainter({
    required this.embers,
    required this.flamePulse,
  });

  final List<_FireEmber> embers;
  final double flamePulse;

  @override
  void paint(Canvas canvas, Size size) {
    for (final ember in embers) {
      if (ember.life <= 0) continue;
      final progress = (ember.life / ember.maxLife).clamp(0.0, 1.0);
      final paint = Paint()
        ..color = ember.color.withValues(alpha: progress * 0.85)
        ..style = PaintingStyle.fill;

      canvas.drawCircle(
        Offset(ember.x, ember.y),
        ember.size * progress,
        paint,
      );
    }
  }

  @override
  bool shouldRepaint(covariant _EmberParticlesPainter old) => true;
}

/// Displays the full animated Daily Streak Experience Modal Sheet
void showStreakCelebrationSheet(BuildContext context, StreakManager streak) {
  showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (ctx) => _StreakCelebrationSheetContent(streak: streak),
  );
}

class _StreakCelebrationSheetContent extends StatefulWidget {
  const _StreakCelebrationSheetContent({required this.streak});

  final StreakManager streak;

  @override
  State<_StreakCelebrationSheetContent> createState() =>
      _StreakCelebrationSheetContentState();
}

class _StreakCelebrationSheetContentState
    extends State<_StreakCelebrationSheetContent>
    with TickerProviderStateMixin {
  late final AnimationController _flameController;
  late final AnimationController _entryController;
  late final Animation<double> _scaleAnimation;
  late final Animation<double> _fadeAnimation;

  final List<_FireEmber> _embers = [];
  final math.Random _rng = math.Random();

  @override
  void initState() {
    super.initState();

    _flameController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1800),
    )..repeat();

    _flameController.addListener(_updateEmbers);

    _entryController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 700),
    );

    _scaleAnimation = CurvedAnimation(
      parent: _entryController,
      curve: Curves.easeOutBack,
    );

    _fadeAnimation = CurvedAnimation(
      parent: _entryController,
      curve: Curves.easeOut,
    );

    _entryController.forward();
    _spawnEmbers();
  }

  void _spawnEmbers() {
    const emberColors = [
      Color(0xFFFDE047), // bright gold
      Color(0xFFF59E0B), // amber
      Color(0xFFEA580C), // orange
      Color(0xFFEF4444), // fire red
    ];

    for (int i = 0; i < 28; i++) {
      _embers.add(
        _FireEmber(
          x: 140 + (_rng.nextDouble() - 0.5) * 90,
          y: 90 + _rng.nextDouble() * 50,
          vx: (_rng.nextDouble() - 0.5) * 1.5,
          vy: -1.0 - _rng.nextDouble() * 2.2,
          size: 3.0 + _rng.nextDouble() * 4.0,
          color: emberColors[_rng.nextInt(emberColors.length)],
          life: 0.2 + _rng.nextDouble() * 0.8,
          maxLife: 1.0,
        ),
      );
    }
  }

  void _updateEmbers() {
    const emberColors = [
      Color(0xFFFDE047),
      Color(0xFFF59E0B),
      Color(0xFFEA580C),
      Color(0xFFEF4444),
    ];

    for (final e in _embers) {
      e.update();
      if (e.life <= 0) {
        e.x = 140 + (_rng.nextDouble() - 0.5) * 80;
        e.y = 110 + _rng.nextDouble() * 30;
        e.vx = (_rng.nextDouble() - 0.5) * 1.4;
        e.vy = -1.0 - _rng.nextDouble() * 2.2;
        e.life = 1.0;
        e.size = 3.0 + _rng.nextDouble() * 4.0;
        e.color = emberColors[_rng.nextInt(emberColors.length)];
      }
    }
    setState(() {});
  }

  @override
  void dispose() {
    _flameController.removeListener(_updateEmbers);
    _flameController.dispose();
    _entryController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final count = widget.streak.streakCount;
    final weeklyDays = widget.streak.getWeeklyStatus();
    final nextMilestone = widget.streak.nextMilestone;
    final progress = widget.streak.milestoneProgress;

    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(36)),
        boxShadow: [
          BoxShadow(
            color: Colors.black26,
            blurRadius: 30,
            offset: Offset(0, -5),
          ),
        ],
      ),
      padding: EdgeInsets.fromLTRB(
        24,
        14,
        24,
        MediaQuery.of(context).padding.bottom + 24,
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Drag Handle Indicator
            Center(
              child: Container(
                width: 44,
                height: 5,
                decoration: BoxDecoration(
                  color: const Color(0xFFE2E8F0),
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
            ),
            const SizedBox(height: 18),

            // Animated Hero Flame with rising ember particles
            SizedBox(
              height: 160,
              width: 280,
              child: Stack(
                alignment: Alignment.center,
                children: [
                  // Ember particles
                  Positioned.fill(
                    child: CustomPaint(
                      painter: _EmberParticlesPainter(
                        embers: _embers,
                        flamePulse: _flameController.value,
                      ),
                    ),
                  ),

                  // Giant Glowing Flame Icon with Radial Aura
                  AnimatedBuilder(
                    animation: _flameController,
                    builder: (context, child) {
                      final breath =
                          math.sin(_flameController.value * 2 * math.pi);
                      final scale = 1.0 + 0.08 * breath;
                      final glow = 0.4 + 0.25 * breath;

                      return Stack(
                        alignment: Alignment.center,
                        children: [
                          // Outer glow halo
                          Container(
                            width: 130 * scale,
                            height: 130 * scale,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              gradient: RadialGradient(
                                colors: [
                                  const Color(0xFFF59E0B)
                                      .withValues(alpha: glow * 0.5),
                                  const Color(0xFFEA580C)
                                      .withValues(alpha: glow * 0.25),
                                  Colors.transparent,
                                ],
                              ),
                            ),
                          ),

                          // Hero Lottie Flame (borderless & natural)
                          Transform.scale(
                            scale: scale,
                            child: const StreakFlameLottie(
                              width: 100,
                              height: 120,
                              fit: BoxFit.contain,
                            ),
                          ),
                        ],
                      );
                    },
                  ),
                ],
              ),
            ),

            // Headline with Animated Spring Scale
            ScaleTransition(
              scale: _scaleAnimation,
              child: FadeTransition(
                opacity: _fadeAnimation,
                child: Column(
                  children: [
                    Text(
                      count == 0
                          ? 'Start Your Streak!'
                          : '$count ${count == 1 ? "Day" : "Day"} Streak!',
                      style: const TextStyle(
                        fontSize: 28,
                        fontWeight: FontWeight.w900,
                        color: Color(0xFF0F172A),
                        letterSpacing: -0.6,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      count == 0
                          ? 'Complete a task today to ignite your daily fire! 🔥'
                          : widget.streak.streakIncreasedToday
                              ? "You're on fire! 🔥 Keep it going tomorrow!"
                              : "Today's activity recorded! Keep up the momentum.",
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w500,
                        color: Color(0xFF64748B),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),

            // 7-Day Activity Week Strip
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 16),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(22),
                border: Border.all(color: const Color(0xFFE2E8F0), width: 1.2),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'Weekly Activity',
                        style: TextStyle(
                          fontSize: 13.5,
                          fontWeight: FontWeight.w800,
                          color: Color(0xFF1E293B),
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFEF3C7),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Text(
                          '$count Active Days',
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: Color(0xFFB45309),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),

                  // 7 Days Circles Row
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: weeklyDays.map((day) {
                      return _buildWeekDayCircle(day);
                    }).toList(),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 18),

            // Milestone Banner with Progress
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFFFFFBEB), Color(0xFFFEF3C7)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: const Color(0xFFFDE68A), width: 1.2),
              ),
              child: Column(
                children: [
                  Row(
                    children: [
                      Text(
                        nextMilestone.icon,
                        style: const TextStyle(fontSize: 24),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Next Goal: ${nextMilestone.title}',
                              style: const TextStyle(
                                fontSize: 13.5,
                                fontWeight: FontWeight.w800,
                                color: Color(0xFF92400E),
                              ),
                            ),
                            Text(
                              count >= nextMilestone.targetDays
                                  ? 'Milestone unlocked! Congratulations!'
                                  : '${nextMilestone.targetDays - count} days left to achieve milestone',
                              style: const TextStyle(
                                fontSize: 11.5,
                                color: Color(0xFFB45309),
                              ),
                            ),
                          ],
                        ),
                      ),
                      Text(
                        '${(progress * 100).toInt()}%',
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w800,
                          color: Color(0xFF92400E),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(8),
                    child: LinearProgressIndicator(
                      value: progress,
                      minHeight: 7,
                      backgroundColor: const Color(0xFFFDE68A),
                      valueColor: const AlwaysStoppedAnimation<Color>(
                        Color(0xFFEA580C),
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 18),

            // Stats 3-Grid
            Row(
              children: [
                _buildStatPill(
                  label: 'Current',
                  value: '$count Days',
                  icon: Icons.local_fire_department_rounded,
                  iconColor: const Color(0xFFEA580C),
                ),
                const SizedBox(width: 10),
                _buildStatPill(
                  label: 'Longest',
                  value: '${widget.streak.longestStreak} Days',
                  icon: Icons.emoji_events_rounded,
                  iconColor: const Color(0xFFF59E0B),
                ),
                const SizedBox(width: 10),
                _buildStatPill(
                  label: 'Total',
                  value: '${widget.streak.totalActiveDays} Days',
                  icon: Icons.calendar_today_rounded,
                  iconColor: const Color(0xFF6366F1),
                ),
              ],
            ),
            const SizedBox(height: 22),

            // Celebratory "Keep It Burning" Action Button
            BouncingInteractive(
              onTap: () => Navigator.pop(context),
              scaleFactor: 0.95,
              child: Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(vertical: 16),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFFEA580C), Color(0xFFF59E0B)],
                    begin: Alignment.centerLeft,
                    end: Alignment.centerRight,
                  ),
                  borderRadius: BorderRadius.circular(20),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFFEA580C).withValues(alpha: 0.35),
                      blurRadius: 14,
                      offset: const Offset(0, 5),
                    ),
                  ],
                ),
                child: const Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    StreakFlameLottie(
                      width: 22,
                      height: 22,
                    ),
                    SizedBox(width: 8),
                    Text(
                      'Keep It Burning 🔥',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                        color: Colors.white,
                        letterSpacing: -0.2,
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
  }

  Widget _buildWeekDayCircle(DayStreakInfo day) {
    if (day.isToday) {
      return Column(
        children: [
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              gradient: const LinearGradient(
                colors: [Color(0xFFEA580C), Color(0xFFF59E0B)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              boxShadow: [
                BoxShadow(
                  color: const Color(0xFFEA580C).withValues(alpha: 0.4),
                  blurRadius: 8,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: const Center(
              child: StreakFlameLottie(
                width: 24,
                height: 24,
              ),
            ),
          ),
          const SizedBox(height: 6),
          Text(
            day.dayLabel,
            style: const TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w800,
              color: Color(0xFFEA580C),
            ),
          ),
        ],
      );
    }

    if (day.isCompleted) {
      return Column(
        children: [
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: const Color(0xFFFEF3C7),
              border: Border.all(color: const Color(0xFFFDE68A), width: 1.5),
            ),
            child: const Center(
              child: Icon(
                Icons.check_rounded,
                color: Color(0xFFB45309),
                size: 20,
              ),
            ),
          ),
          const SizedBox(height: 6),
          Text(
            day.dayLabel,
            style: const TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w700,
              color: Color(0xFFB45309),
            ),
          ),
        ],
      );
    }

    return Column(
      children: [
        Container(
          width: 38,
          height: 38,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: const Color(0xFFF1F5F9),
            border: Border.all(color: const Color(0xFFE2E8F0), width: 1.2),
          ),
          child: Center(
            child: Text(
              '${day.dayNumber}',
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: Color(0xFF94A3B8),
              ),
            ),
          ),
        ),
        const SizedBox(height: 6),
        Text(
          day.dayLabel,
          style: const TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w500,
            color: Color(0xFF94A3B8),
          ),
        ),
      ],
    );
  }

  Widget _buildStatPill({
    required String label,
    required String value,
    required IconData icon,
    required Color iconColor,
  }) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
        decoration: BoxDecoration(
          color: const Color(0xFFF8FAFC),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: const Color(0xFFE2E8F0)),
        ),
        child: Column(
          children: [
            Icon(icon, color: iconColor, size: 18),
            const SizedBox(height: 4),
            Text(
              value,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w800,
                color: Color(0xFF0F172A),
              ),
            ),
            Text(
              label,
              style: const TextStyle(
                fontSize: 10.5,
                color: Color(0xFF64748B),
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
