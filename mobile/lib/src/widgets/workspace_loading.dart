import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';

import '../app.dart';
import 'motion.dart';

/// Clean Apple-inspired Workspace Loading & Reload screen.
/// Replaces the bare spinner with a polished, branded loading state.
class WorkspaceLoading extends StatefulWidget {
  const WorkspaceLoading({super.key});

  @override
  State<WorkspaceLoading> createState() => _WorkspaceLoadingState();
}

class _WorkspaceLoadingState extends State<WorkspaceLoading>
    with SingleTickerProviderStateMixin {
  late final AnimationController _pulseController;

  @override
  void initState() {
    super.initState();
    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1800),
    )..repeat(reverse: true);
  }

  @override
  void dispose() {
    _pulseController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final disableAnimations = MediaQuery.disableAnimationsOf(context);

    return Scaffold(
      backgroundColor: surfaceBg,
      body: Stack(
        children: [
          // Ambient soft glowing orbs at top-right and bottom-left
          Positioned(
            top: -50,
            right: -40,
            child: IgnorePointer(
              child: Container(
                width: 260,
                height: 260,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: RadialGradient(
                    colors: [
                      brandPurple.withValues(alpha: 0.12),
                      Colors.transparent,
                    ],
                  ),
                ),
              ),
            ),
          ),
          Positioned(
            bottom: -60,
            left: -40,
            child: IgnorePointer(
              child: Container(
                width: 280,
                height: 280,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: RadialGradient(
                    colors: [
                      const Color(0xFF3864ED).withValues(alpha: 0.08),
                      Colors.transparent,
                    ],
                  ),
                ),
              ),
            ),
          ),

          SafeArea(
            child: Center(
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 480),
                child: SingleChildScrollView(
                  padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
                  child: Semantics(
                    label: 'Syncing workspace. Loading your tasks and schedule.',
                    liveRegion: true,
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      crossAxisAlignment: CrossAxisAlignment.center,
                      children: [
                        const SizedBox(height: 12),

                        // Centered App Logo with breathing glow aura
                        AnimatedBuilder(
                          animation: _pulseController,
                          builder: (context, child) {
                            final pulse = disableAnimations
                                ? 1.0
                                : 0.95 + (_pulseController.value * 0.1);
                            final glowAlpha = disableAnimations
                                ? 0.2
                                : 0.15 + (_pulseController.value * 0.2);

                            return Stack(
                              alignment: Alignment.center,
                              children: [
                                // Glowing background aura
                                Transform.scale(
                                  scale: pulse * 1.35,
                                  child: Container(
                                    width: 88,
                                    height: 88,
                                    decoration: BoxDecoration(
                                      shape: BoxShape.circle,
                                      gradient: RadialGradient(
                                        colors: [
                                          brandPurple.withValues(alpha: glowAlpha),
                                          Colors.transparent,
                                        ],
                                      ),
                                    ),
                                  ),
                                ),

                                // Squircle Card with App Logo
                                Container(
                                  width: 78,
                                  height: 78,
                                  decoration: BoxDecoration(
                                    color: Colors.white,
                                    borderRadius: BorderRadius.circular(22),
                                    border: Border.all(
                                      color: const Color(0xFFE8EAF2),
                                      width: 1,
                                    ),
                                    boxShadow: [
                                      BoxShadow(
                                        color: brandPurple.withValues(alpha: 0.22),
                                        blurRadius: 24,
                                        offset: const Offset(0, 8),
                                      ),
                                    ],
                                  ),
                                  padding: const EdgeInsets.all(10),
                                  child: ClipRRect(
                                    borderRadius: BorderRadius.circular(14),
                                    child: Image.asset(
                                      'assets/logo/app_logo.png',
                                      fit: BoxFit.cover,
                                    ),
                                  ),
                                ),
                              ],
                            );
                          },
                        ),

                        const SizedBox(height: 24),

                        // Clean Status Pill
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 4,
                          ),
                          decoration: BoxDecoration(
                            color: brandPurple.withValues(alpha: 0.08),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Container(
                                width: 6,
                                height: 6,
                                decoration: const BoxDecoration(
                                  color: brandPurple,
                                  shape: BoxShape.circle,
                                ),
                              ),
                              const SizedBox(width: 6),
                              const Text(
                                'SYNCING WORKSPACE',
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.w700,
                                  letterSpacing: 0.8,
                                  color: brandPurple,
                                ),
                              ),
                            ],
                          ),
                        ),

                        const SizedBox(height: 12),

                        // Title & Subtitle
                        const Text(
                          'Opening Task Manager',
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.w800,
                            letterSpacing: -0.5,
                            color: Color(0xFF1B1D28),
                          ),
                        ),
                        const SizedBox(height: 6),
                        const Text(
                          'Connecting to your tasks, deadlines & focus sessions…',
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 13.5,
                            height: 1.4,
                            fontWeight: FontWeight.w400,
                            color: Color(0xFF8E95A5),
                          ),
                        ),

                        const SizedBox(height: 20),

                        // Smooth Apple-style activity spinner
                        const CupertinoActivityIndicator(
                          radius: 12,
                          color: brandPurple,
                        ),

                        const SizedBox(height: 28),

                        // Shimmering Preview Skeleton Cards
                        const TaskLoadingPlaceholder(),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
