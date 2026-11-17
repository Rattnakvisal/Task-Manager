import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../app.dart';
import '../widgets/motion.dart';

const _ink = Color(0xFF202031);
const _muted = Color(0xFF707082);
const _paper = Color(0xFFFAFAFD);

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
  final _pages = PageController();
  int _page = 0;

  @override
  void dispose() {
    _pages.dispose();
    super.dispose();
  }

  void _goTo(int page) {
    if (page == _page) return;
    HapticFeedback.selectionClick();
    if (MediaQuery.disableAnimationsOf(context)) {
      _pages.jumpToPage(page);
    } else {
      _pages.animateToPage(
        page,
        duration: const Duration(milliseconds: 360),
        curve: Curves.easeInOutCubic,
      );
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: _paper,
    body: SafeArea(
      child: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 520),
          child: Column(
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(12, 4, 16, 0),
                child: Row(
                  children: [
                    Visibility(
                      visible: _page > 0,
                      maintainSize: true,
                      maintainState: true,
                      maintainAnimation: true,
                      child: IconButton(
                        onPressed: _page > 0 ? () => _goTo(_page - 1) : null,
                        tooltip: 'Previous page',
                        icon: const Icon(Icons.arrow_back_rounded),
                        color: _ink,
                      ),
                    ),
                    const Spacer(),
                    TextButton(
                      onPressed: widget.onGetStarted,
                      style: TextButton.styleFrom(foregroundColor: _muted),
                      child: const Text('Skip'),
                    ),
                  ],
                ),
              ),
              Expanded(
                child: PageView(
                  controller: _pages,
                  onPageChanged: (page) => setState(() => _page = page),
                  children: const [
                    _IntroPage(
                      index: 0,
                      eyebrow: 'A LITTLE MORE CLARITY',
                      title: 'Less chaos.\n',
                      highlight: 'More done.',
                      description:
                          'Give your ideas, plans, and everyday to-dos a place '
                          'to land. Make space for what matters.',
                      features: [
                        (Icons.checklist_rounded, 'Tasks & checklists'),
                        (Icons.sell_outlined, 'Your own categories'),
                      ],
                      illustration: _PlanIllustration(),
                    ),
                    _IntroPage(
                      index: 1,
                      eyebrow: 'KNOW WHAT COMES NEXT',
                      title: 'Your priorities.\n',
                      highlight: 'In clear view.',
                      description:
                          'Put the important things first. Keep dates, '
                          'priorities, and your next step together.',
                      features: [
                        (Icons.flag_outlined, 'Priority levels'),
                        (Icons.calendar_today_outlined, 'Calendar & due dates'),
                      ],
                      illustration: _PriorityIllustration(),
                    ),
                    _IntroPage(
                      index: 2,
                      eyebrow: 'EVERY SMALL WIN COUNTS',
                      title: 'Small steps.\n',
                      highlight: 'Real progress.',
                      description:
                          'See how far you have come, celebrate completed '
                          'tasks, and keep your momentum going.',
                      features: [
                        (Icons.donut_large_rounded, 'Progress at a glance'),
                        (Icons.insights_rounded, 'Task insights'),
                      ],
                      illustration: _ProgressIllustration(),
                    ),
                  ],
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(24, 8, 24, 12),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Semantics(
                      label: 'Page ${_page + 1} of 3',
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: List.generate(3, (index) {
                          final selected = index == _page;
                          return Semantics(
                            button: true,
                            selected: selected,
                            label: 'Go to page ${index + 1}',
                            child: InkResponse(
                              onTap: () => _goTo(index),
                              radius: 22,
                              child: SizedBox(
                                width: 44,
                                height: 32,
                                child: Center(
                                  child: AnimatedContainer(
                                    duration: motionDuration(context),
                                    width: selected ? 26 : 7,
                                    height: 7,
                                    decoration: BoxDecoration(
                                      color: selected
                                          ? brandPurple
                                          : const Color(0xFFDDDDE7),
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                  ),
                                ),
                              ),
                            ),
                          );
                        }),
                      ),
                    ),
                    const SizedBox(height: 12),
                    SizedBox(
                      width: double.infinity,
                      child: FilledButton(
                        onPressed: _page == 2
                            ? widget.onGetStarted
                            : () => _goTo(_page + 1),
                        style: FilledButton.styleFrom(
                          minimumSize: const Size(48, 56),
                          padding: const EdgeInsets.symmetric(
                            horizontal: 20,
                            vertical: 16,
                          ),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(18),
                          ),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Flexible(
                              child: Text(
                                _page == 2 ? 'Get Started' : 'Continue',
                                textAlign: TextAlign.center,
                                style: const TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ),
                            const SizedBox(width: 12),
                            const Icon(Icons.arrow_forward_rounded, size: 20),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),
                    TextButton(
                      onPressed: widget.onSignIn,
                      style: TextButton.styleFrom(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 8,
                          vertical: 12,
                        ),
                      ),
                      child: const Text.rich(
                        TextSpan(
                          style: TextStyle(fontSize: 13, color: _muted),
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
                        textAlign: TextAlign.center,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    ),
  );
}

class _IntroPage extends StatelessWidget {
  const _IntroPage({
    required this.index,
    required this.eyebrow,
    required this.title,
    required this.highlight,
    required this.description,
    required this.features,
    required this.illustration,
  });

  final int index;
  final String eyebrow, title, highlight, description;
  final List<(IconData, String)> features;
  final Widget illustration;

  @override
  Widget build(BuildContext context) => LayoutBuilder(
    builder: (context, constraints) {
      final visualHeight = (constraints.maxHeight * .53).clamp(208.0, 340.0);
      return SingleChildScrollView(
        key: PageStorageKey('onboarding-$index'),
        padding: const EdgeInsets.fromLTRB(28, 0, 28, 16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ExcludeSemantics(
              child: MediaQuery.withNoTextScaling(
                child: SizedBox(
                  height: visualHeight,
                  width: double.infinity,
                  child: Center(
                    child: FittedBox(
                      fit: BoxFit.scaleDown,
                      child: SizedBox(
                        width: 320,
                        height: 292,
                        child: illustration,
                      ),
                    ),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 18),
            Text(
              eyebrow,
              style: const TextStyle(
                color: _muted,
                fontSize: 10,
                fontWeight: FontWeight.w700,
                letterSpacing: 1.7,
              ),
            ),
            const SizedBox(height: 12),
            Semantics(
              header: true,
              child: Text.rich(
                TextSpan(
                  children: [
                    TextSpan(text: title),
                    TextSpan(
                      text: highlight,
                      style: const TextStyle(color: brandPurple),
                    ),
                  ],
                ),
                style: TextStyle(
                  color: _ink,
                  fontSize: constraints.maxWidth < 360 ? 32 : 38,
                  fontWeight: FontWeight.w800,
                  letterSpacing: -1.5,
                  height: 1.12,
                ),
              ),
            ),
            const SizedBox(height: 16),
            Text(
              description,
              style: const TextStyle(color: _muted, fontSize: 15, height: 1.65),
            ),
            const SizedBox(height: 20),
            Wrap(
              spacing: 16,
              runSpacing: 12,
              children: [
                for (final (icon, label) in features)
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(icon, size: 15, color: brandPurple),
                      const SizedBox(width: 6),
                      Flexible(
                        child: Text(
                          label,
                          style: const TextStyle(
                            color: _muted,
                            fontSize: 11,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ),
                    ],
                  ),
              ],
            ),
          ],
        ),
      );
    },
  );
}

// ---------------------------------------------------------------------------
// Twin iPhone Mockup & iOS-Style Interactive Animations
// ---------------------------------------------------------------------------

class _TwinIPhoneStage extends StatefulWidget {
  const _TwinIPhoneStage({
    required this.ambientColor,
    required this.backPhone,
    required this.frontPhone,
    required this.floatingBadge,
  });

  final Color ambientColor;
  final Widget backPhone;
  final Widget frontPhone;
  final Widget floatingBadge;

  @override
  State<_TwinIPhoneStage> createState() => _TwinIPhoneStageState();
}

class _TwinIPhoneStageState extends State<_TwinIPhoneStage>
    with SingleTickerProviderStateMixin {
  late final AnimationController _floatController;

  @override
  void initState() {
    super.initState();
    _floatController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 3400),
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

    return AnimatedBuilder(
      animation: _floatController,
      builder: (context, _) {
        final t = disableMotion ? 0.0 : _floatController.value;
        // Parallax sine waves with phase shift between the two phones
        final backFloat = disableMotion ? 0.0 : math.sin(t * math.pi) * 4.5;
        final frontFloat = disableMotion
            ? 0.0
            : math.sin((t + 0.3) * math.pi) * 6.5;

        return Stack(
          clipBehavior: Clip.none,
          alignment: Alignment.center,
          children: [
            // Ambient Backdrop Glow Ring
            Container(
              width: 266,
              height: 266,
              decoration: BoxDecoration(
                color: widget.ambientColor.withValues(alpha: 0.85),
                shape: BoxShape.circle,
              ),
            ),
            Container(
              width: 292,
              height: 292,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(
                  color: widget.ambientColor.withValues(alpha: 0.65),
                ),
              ),
            ),

            // Back Phone (Twin companion phone - angled left with depth)
            Positioned(
              top: 14,
              left: 14,
              child: Transform.translate(
                offset: Offset(0, backFloat),
                child: Transform.rotate(
                  angle: -0.075,
                  child: widget.backPhone,
                ),
              ),
            ),

            // Front Phone (Main iPhone 16 Pro - angled right, overlapping in 3D)
            Positioned(
              top: 22,
              left: 98,
              child: Transform.translate(
                offset: Offset(0, frontFloat),
                child: Transform.rotate(
                  angle: 0.045,
                  child: widget.frontPhone,
                ),
              ),
            ),

            // Floating iOS Live Badge / Chip
            Positioned(
              bottom: 0,
              right: 0,
              child: widget.floatingBadge,
            ),
          ],
        );
      },
    );
  }
}

/// A sleek, hyper-realistic iPhone 16 Pro device frame
class _IPhoneDevice extends StatelessWidget {
  const _IPhoneDevice({
    required this.width,
    required this.height,
    required this.child,
    this.isDark = false,
    this.dynamicIslandLabel,
    this.dynamicIslandDotColor = const Color(0xFF34C759),
    this.elevation = 20,
    this.accentGlow,
  });

  final double width;
  final double height;
  final Widget child;
  final bool isDark;
  final String? dynamicIslandLabel;
  final Color dynamicIslandDotColor;
  final double elevation;
  final Color? accentGlow;

  @override
  Widget build(BuildContext context) {
    final textColor = isDark ? Colors.white : const Color(0xFF1C1C28);

    return Container(
      width: width,
      height: height,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(28),
        // Natural Titanium Edge Bezel
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: isDark
              ? const [
                  Color(0xFF5A586E),
                  Color(0xFF312F40),
                  Color(0xFF1E1D28),
                ]
              : const [
                  Color(0xFFE8E8EE),
                  Color(0xFFBCBCC6),
                  Color(0xFF888796),
                ],
        ),
        boxShadow: [
          if (accentGlow != null)
            BoxShadow(
              color: accentGlow!.withValues(alpha: 0.24),
              blurRadius: 32,
              spreadRadius: 2,
              offset: const Offset(0, 12),
            ),
          BoxShadow(
            color: const Color(0xFF181728).withValues(alpha: isDark ? 0.28 : 0.16),
            blurRadius: elevation,
            offset: Offset(0, elevation * 0.45),
          ),
        ],
      ),
      padding: const EdgeInsets.all(4), // Bezel border width
      child: MediaQuery.withNoTextScaling(
        child: Container(
          decoration: BoxDecoration(
            color: isDark ? const Color(0xFF1A1926) : Colors.white,
            borderRadius: BorderRadius.circular(24),
            border: Border.all(
              color: isDark ? const Color(0xFF100F18) : const Color(0xFF14141E),
              width: 1.5,
            ),
          ),
          clipBehavior: Clip.antiAlias,
          child: Stack(
            children: [
              // Main Screen Contents
              Positioned.fill(
                top: 26,
                bottom: 8,
                child: SingleChildScrollView(
                  physics: const NeverScrollableScrollPhysics(),
                  child: child,
                ),
              ),

              // iOS Top Status Bar & Dynamic Island
              Positioned(
                top: 0,
                left: 0,
                right: 0,
                height: 26,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      Text(
                        '9:41',
                        style: TextStyle(
                          fontSize: 8.5,
                          fontWeight: FontWeight.w700,
                          color: textColor,
                          letterSpacing: -0.2,
                        ),
                      ),
                      // Dynamic Island Pill
                      Flexible(
                        child: Container(
                          height: 14,
                          margin: const EdgeInsets.symmetric(horizontal: 3),
                          padding: const EdgeInsets.symmetric(horizontal: 5),
                          decoration: BoxDecoration(
                            color: const Color(0xFF0A0A10),
                            borderRadius: BorderRadius.circular(9),
                            border: Border.all(
                              color: Colors.white.withValues(alpha: 0.08),
                              width: 0.5,
                            ),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Container(
                                width: 3.5,
                                height: 3.5,
                                decoration: BoxDecoration(
                                  color: dynamicIslandDotColor,
                                  shape: BoxShape.circle,
                                ),
                              ),
                              if (dynamicIslandLabel != null) ...[
                                const SizedBox(width: 3),
                                Flexible(
                                  child: Text(
                                    dynamicIslandLabel!,
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(
                                      color: Colors.white,
                                      fontSize: 6.5,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ),
                      ),
                      // Wi-Fi & Battery indicator
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            Icons.wifi,
                            size: 8.5,
                            color: textColor.withValues(alpha: 0.8),
                          ),
                          const SizedBox(width: 3),
                          Container(
                            width: 13,
                            height: 7,
                            padding: const EdgeInsets.all(1),
                            decoration: BoxDecoration(
                              borderRadius: BorderRadius.circular(2.5),
                              border: Border.all(
                                color: textColor.withValues(alpha: 0.7),
                                width: 0.8,
                              ),
                            ),
                            child: Container(
                              decoration: BoxDecoration(
                                color: const Color(0xFF34C759),
                                borderRadius: BorderRadius.circular(1.2),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),

              // Diagonal Specular Glass Reflection
              Positioned(
                top: -40,
                left: -40,
                width: width * 1.8,
                height: 100,
                child: IgnorePointer(
                  child: Transform.rotate(
                    angle: -0.55,
                    child: Container(
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topCenter,
                          end: Alignment.bottomCenter,
                          colors: [
                            Colors.white.withValues(alpha: isDark ? 0.09 : 0.18),
                            Colors.white.withValues(alpha: 0.0),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
              ),

              // iOS Home Indicator Line
              Positioned(
                bottom: 3,
                left: 0,
                right: 0,
                child: Center(
                  child: Container(
                    width: width * 0.32,
                    height: 2.5,
                    decoration: BoxDecoration(
                      color: isDark
                          ? Colors.white.withValues(alpha: 0.35)
                          : const Color(0xFF1C1C28).withValues(alpha: 0.65),
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Slide 1: Daily Plan (Twin iPhone with Dynamic Island & Live Task Check)
// ---------------------------------------------------------------------------

class _PlanIllustration extends StatelessWidget {
  const _PlanIllustration();

  @override
  Widget build(BuildContext context) => _TwinIPhoneStage(
    ambientColor: const Color(0xFFECE9FF),
    // Back Phone: Calendar & Scheduled Timeline
    backPhone: _IPhoneDevice(
      width: 154,
      height: 236,
      isDark: true,
      dynamicIslandLabel: 'Oct 24',
      dynamicIslandDotColor: const Color(0xFF5B4DFF),
      elevation: 14,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'TODAY',
              style: TextStyle(
                color: Color(0xFF9F9DB2),
                fontSize: 8,
                fontWeight: FontWeight.w800,
                letterSpacing: 0.8,
              ),
            ),
            const SizedBox(height: 6),
            _miniScheduleBlock(
              time: '10:00 AM',
              title: 'Project review',
              color: const Color(0xFF5B4DFF),
            ),
            const SizedBox(height: 5),
            _miniScheduleBlock(
              time: '12:30 PM',
              title: 'Recharge break',
              color: const Color(0xFF3864ED),
            ),
            const SizedBox(height: 5),
            _miniScheduleBlock(
              time: '05:00 PM',
              title: 'Learn & focus',
              color: const Color(0xFF168268),
            ),
          ],
        ),
      ),
    ),
    // Front Phone: Daily Plan with interactive checked task
    frontPhone: _IPhoneDevice(
      width: 178,
      height: 254,
      dynamicIslandLabel: 'Focus \u00b7 25m',
      dynamicIslandDotColor: const Color(0xFF34C759),
      accentGlow: brandPurple,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(10, 4, 10, 6),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(6),
                  child: Image.asset(
                    'assets/logo/app_logo.png',
                    width: 20,
                    height: 20,
                  ),
                ),
                const SizedBox(width: 6),
                const Expanded(
                  child: Text(
                    'Daily Plan',
                    style: TextStyle(
                      fontSize: 12.5,
                      fontWeight: FontWeight.w800,
                      color: _ink,
                    ),
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2),
                  decoration: BoxDecoration(
                    color: const Color(0xFFEFEFF8),
                    borderRadius: BorderRadius.circular(5),
                  ),
                  child: const Text(
                    '3 tasks',
                    style: TextStyle(
                      fontSize: 7.5,
                      fontWeight: FontWeight.w700,
                      color: _muted,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            const _SampleTask(
              title: 'Finish project plan',
              subtitle: 'Work \u00b7 10:00 AM',
              done: true,
            ),
            const SizedBox(height: 8),
            const _SampleTask(
              title: 'A moment to recharge',
              subtitle: 'Personal \u00b7 12:30 PM',
            ),
            const SizedBox(height: 8),
            const _SampleTask(
              title: 'Learn something new',
              subtitle: 'Personal \u00b7 5:00 PM',
            ),
          ],
        ),
      ),
    ),
    floatingBadge: Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
      decoration: BoxDecoration(
        color: brandPurple,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: brandPurple.withValues(alpha: 0.28),
            blurRadius: 18,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: const Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.check_circle_rounded, color: Colors.white, size: 20),
          SizedBox(width: 8),
          Text(
            'One less thing\non your mind.',
            style: TextStyle(
              color: Colors.white,
              fontSize: 11,
              height: 1.35,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    ),
  );

  static Widget _miniScheduleBlock({
    required String time,
    required String title,
    required Color color,
  }) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 5),
    decoration: BoxDecoration(
      color: color.withValues(alpha: 0.16),
      borderRadius: BorderRadius.circular(7),
      border: Border.all(color: color.withValues(alpha: 0.35), width: 0.8),
    ),
    child: Row(
      children: [
        Container(
          width: 3,
          height: 18,
          decoration: BoxDecoration(
            color: color,
            borderRadius: BorderRadius.circular(2),
          ),
        ),
        const SizedBox(width: 6),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 8.5,
                  fontWeight: FontWeight.w700,
                ),
              ),
              Text(
                time,
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.6),
                  fontSize: 7,
                ),
              ),
            ],
          ),
        ),
      ],
    ),
  );
}

class _SampleTask extends StatelessWidget {
  const _SampleTask({
    required this.title,
    required this.subtitle,
    this.done = false,
  });

  final String title, subtitle;
  final bool done;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 5),
    decoration: BoxDecoration(
      color: done ? const Color(0xFFF6F5FD) : Colors.white,
      borderRadius: BorderRadius.circular(9),
      border: Border.all(
        color: done ? const Color(0xFFE4E1FA) : const Color(0xFFF0F0F5),
      ),
    ),
    child: Row(
      children: [
        Icon(
          done
              ? Icons.check_circle_rounded
              : Icons.radio_button_unchecked_rounded,
          size: 18,
          color: done ? brandPurple : const Color(0xFFD4D3E2),
        ),
        const SizedBox(width: 7),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: done ? _muted : _ink,
                  fontSize: 10,
                  fontWeight: FontWeight.w700,
                  decoration: done ? TextDecoration.lineThrough : null,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                subtitle,
                style: const TextStyle(color: _muted, fontSize: 8),
              ),
            ],
          ),
        ),
      ],
    ),
  );
}

// ---------------------------------------------------------------------------
// Slide 2: Priorities (Twin iPhone with Priority Stacking & Alert Badges)
// ---------------------------------------------------------------------------

class _PriorityIllustration extends StatelessWidget {
  const _PriorityIllustration();

  @override
  Widget build(BuildContext context) => _TwinIPhoneStage(
    ambientColor: const Color(0xFFFBEADC),
    // Back Phone: Matrix & Priority distribution
    backPhone: _IPhoneDevice(
      width: 154,
      height: 236,
      isDark: true,
      dynamicIslandLabel: 'Matrix',
      dynamicIslandDotColor: const Color(0xFFE65100),
      elevation: 14,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'RADAR',
              style: TextStyle(
                color: Color(0xFF9F9DB2),
                fontSize: 8,
                fontWeight: FontWeight.w800,
                letterSpacing: 0.8,
              ),
            ),
            const SizedBox(height: 8),
            _priorityBar('HIGH', '1 urgent', const Color(0xFFCE5354), 0.8),
            const SizedBox(height: 6),
            _priorityBar('MED', '2 scheduled', const Color(0xFF9F6815), 0.5),
            const SizedBox(height: 6),
            _priorityBar('LOW', '4 flexible', const Color(0xFF168268), 0.25),
          ],
        ),
      ),
    ),
    // Front Phone: Priority Cards Stack with glowing badges
    frontPhone: _IPhoneDevice(
      width: 178,
      height: 254,
      dynamicIslandLabel: 'High Priority',
      dynamicIslandDotColor: const Color(0xFFFF3B30),
      accentGlow: const Color(0xFFCE5354),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(8, 4, 8, 6),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: const [
            Text(
              'PRIORITIES',
              style: TextStyle(
                fontSize: 8.5,
                fontWeight: FontWeight.w800,
                color: _muted,
                letterSpacing: 0.8,
              ),
            ),
            SizedBox(height: 6),
            _PriorityCard(
              title: 'Send project proposal',
              label: 'HIGH',
              detail: 'Due today \u00b7 9:00 AM',
              color: Color(0xFFCE5354),
              background: Color(0xFFFFE8E6),
            ),
            SizedBox(height: 6),
            _PriorityCard(
              title: 'Plan the week',
              label: 'MEDIUM',
              detail: 'See the bigger picture',
              color: Color(0xFF9F6815),
              background: Color(0xFFFFF0D5),
            ),
            SizedBox(height: 6),
            _PriorityCard(
              title: 'Read a chapter',
              label: 'LOW',
              detail: 'A little personal time',
              color: Color(0xFF168268),
              background: Color(0xFFE3F4EB),
            ),
          ],
        ),
      ),
    ),
    floatingBadge: Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFECEBF3)),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF31304F).withValues(alpha: 0.1),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: const Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.flag_rounded, color: Color(0xFFCE5354), size: 18),
          SizedBox(width: 8),
          Text(
            'Keep what matters\nfront & center.',
            style: TextStyle(
              color: _ink,
              fontSize: 10.5,
              height: 1.35,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    ),
  );

  static Widget _priorityBar(
    String label,
    String count,
    Color color,
    double fraction,
  ) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: TextStyle(
              color: color,
              fontSize: 7.5,
              fontWeight: FontWeight.w800,
            ),
          ),
          Text(
            count,
            style: const TextStyle(
              color: Color(0xFFB9B5CD),
              fontSize: 7,
            ),
          ),
        ],
      ),
      const SizedBox(height: 2.5),
      Container(
        height: 3.5,
        width: double.infinity,
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.1),
          borderRadius: BorderRadius.circular(2),
        ),
        child: FractionallySizedBox(
          alignment: Alignment.centerLeft,
          widthFactor: fraction,
          child: Container(
            decoration: BoxDecoration(
              color: color,
              borderRadius: BorderRadius.circular(2),
            ),
          ),
        ),
      ),
    ],
  );
}

class _PriorityCard extends StatelessWidget {
  const _PriorityCard({
    required this.title,
    required this.label,
    required this.detail,
    required this.color,
    required this.background,
  });

  final String title, label, detail;
  final Color color, background;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(7),
    decoration: BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(10),
      border: Border.all(color: const Color(0xFFECEBF3)),
      boxShadow: [
        BoxShadow(
          color: const Color(0xFF31304F).withValues(alpha: 0.04),
          blurRadius: 8,
          offset: const Offset(0, 3),
        ),
      ],
    ),
    child: Row(
      children: [
        Container(
          width: 3,
          height: 28,
          decoration: BoxDecoration(
            color: color,
            borderRadius: BorderRadius.circular(2),
          ),
        ),
        const SizedBox(width: 7),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  color: _ink,
                  fontSize: 9.5,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                detail,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(color: _muted, fontSize: 7.5),
              ),
            ],
          ),
        ),
        const SizedBox(width: 4),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2.5),
          decoration: BoxDecoration(
            color: background,
            borderRadius: BorderRadius.circular(5),
          ),
          child: Text(
            label,
            style: TextStyle(
              color: color,
              fontSize: 6.5,
              fontWeight: FontWeight.w800,
              letterSpacing: 0.2,
            ),
          ),
        ),
      ],
    ),
  );
}

// ---------------------------------------------------------------------------
// Slide 3: Progress & Wins (Twin iPhone with Progress Ring & Streak Badges)
// ---------------------------------------------------------------------------

class _ProgressIllustration extends StatelessWidget {
  const _ProgressIllustration();

  @override
  Widget build(BuildContext context) => _TwinIPhoneStage(
    ambientColor: const Color(0xFFE0F2EB),
    // Back Phone: 7-Day Streak & Insights
    backPhone: _IPhoneDevice(
      width: 154,
      height: 236,
      dynamicIslandLabel: 'Streak \u00b7 7d',
      dynamicIslandDotColor: const Color(0xFFFF9500),
      elevation: 14,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'THIS WEEK',
              style: TextStyle(
                color: _muted,
                fontSize: 8,
                fontWeight: FontWeight.w800,
                letterSpacing: 0.8,
              ),
            ),
            const SizedBox(height: 7),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                for (final day in ['M', 'T', 'W', 'T', 'F', 'S', 'S'])
                  Column(
                    children: [
                      Container(
                        width: 15,
                        height: 15,
                        decoration: BoxDecoration(
                          color: const Color(0xFF168268),
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(
                          Icons.check,
                          color: Colors.white,
                          size: 9,
                        ),
                      ),
                      const SizedBox(height: 3),
                      Text(
                        day,
                        style: const TextStyle(
                          color: _muted,
                          fontSize: 6.5,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
              ],
            ),
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(7),
              decoration: BoxDecoration(
                color: const Color(0xFFF4F8F6),
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Row(
                children: [
                  Icon(Icons.local_fire_department_rounded,
                      color: Color(0xFFFF9500), size: 16),
                  SizedBox(width: 5),
                  Expanded(
                    child: Text(
                      '7-day streak active!',
                      style: TextStyle(
                        color: _ink,
                        fontSize: 8,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    ),
    // Front Phone: Midnight Edition with Animated 75% Progress Ring
    frontPhone: _IPhoneDevice(
      width: 178,
      height: 254,
      isDark: true,
      dynamicIslandLabel: 'Goal 75%',
      dynamicIslandDotColor: const Color(0xFF34C759),
      accentGlow: const Color(0xFF168268),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(10, 4, 10, 6),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Look at you go.',
              style: TextStyle(
                color: Colors.white,
                fontSize: 13,
                fontWeight: FontWeight.w800,
                letterSpacing: -0.4,
              ),
            ),
            const SizedBox(height: 2),
            const Text(
              'Daily completion',
              style: TextStyle(color: Color(0xFFB9B5CD), fontSize: 8),
            ),
            const SizedBox(height: 12),
            Center(
              child: SizedBox(
                width: 72,
                height: 72,
                child: TweenAnimationBuilder<double>(
                  tween: Tween(begin: 0, end: 0.75),
                  duration: motionDuration(context, 750),
                  curve: Curves.easeOutCubic,
                  builder: (context, progress, _) => Stack(
                    alignment: Alignment.center,
                    children: [
                      Positioned.fill(
                        child: CustomPaint(
                          painter: _ProgressRing(progress),
                        ),
                      ),
                      Text(
                        '${(progress * 100).round()}%',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                          letterSpacing: -0.5,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
            const SizedBox(height: 10),
            Center(
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: const Text(
                  '3 of 4 tasks done',
                  style: TextStyle(
                    color: Color(0xFF9DE5C8),
                    fontSize: 8.5,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    ),
    floatingBadge: Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFECEBF3)),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF31304F).withValues(alpha: 0.1),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: const Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            Icons.trending_up_rounded,
            color: Color(0xFF168268),
            size: 20,
          ),
          SizedBox(width: 8),
          Text(
            'That is progress.',
            style: TextStyle(
              color: _ink,
              fontSize: 11,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    ),
  );
}

class _ProgressRing extends CustomPainter {
  const _ProgressRing(this.progress);
  final double progress;

  @override
  void paint(Canvas canvas, Size size) {
    final rect = (Offset.zero & size).deflate(4.5);
    final paint = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 6.5
      ..strokeCap = StrokeCap.round
      ..color = const Color(0xFF38354E);
    canvas.drawArc(rect, -math.pi / 2, math.pi * 2, false, paint);
    paint.color = const Color(0xFF34C759);
    canvas.drawArc(rect, -math.pi / 2, math.pi * 2 * progress, false, paint);
  }

  @override
  bool shouldRepaint(covariant _ProgressRing oldDelegate) =>
      oldDelegate.progress != progress;
}
