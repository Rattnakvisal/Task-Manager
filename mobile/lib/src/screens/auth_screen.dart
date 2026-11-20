import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../app.dart';
import '../services/api_exception.dart';
import '../state/app_state.dart';
import '../widgets/motion.dart';

class AuthScreen extends StatefulWidget {
  const AuthScreen({super.key, required this.state, this.initialRegister = false});
  final AppState state;
  final bool initialRegister;
  @override
  State<AuthScreen> createState() => _AuthScreenState();
}

class _AuthScreenState extends State<AuthScreen> {
  final _form = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _password = TextEditingController();
  final _confirmation = TextEditingController();
  late bool _register = widget.initialRegister;
  bool _busy = false, _obscure = true;
  bool _shakeTrigger = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _password.addListener(_onPasswordChanged);
  }

  void _onPasswordChanged() {
    if (_register && mounted) {
      setState(() {});
    }
  }

  @override
  void dispose() {
    _password.removeListener(_onPasswordChanged);
    for (final controller in [_name, _email, _password, _confirmation]) {
      controller.dispose();
    }
    super.dispose();
  }

  Future<void> _submit() async {
    if (_busy) return;
    if (!_form.currentState!.validate()) {
      HapticFeedback.heavyImpact();
      setState(() => _shakeTrigger = !_shakeTrigger);
      return;
    }
    FocusManager.instance.primaryFocus?.unfocus();
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await widget.state.authenticate(
        email: _email.text,
        password: _password.text,
        name: _register ? _name.text : null,
        confirmation: _confirmation.text,
      );
    } catch (error) {
      if (mounted) {
        HapticFeedback.heavyImpact();
        setState(() {
          _shakeTrigger = !_shakeTrigger;
          _error = error is ApiException
              ? error.message
              : 'Could not sign in. Please try again.';
        });
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  void _toggleMode(bool register) {
    if (_busy || _register == register) return;
    setState(() {
      _register = register;
      _error = null;
      _form.currentState?.reset();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: surfaceBg,
      body: Stack(
        children: [
          // Ambient Mesh Glow Orbs in background
          Positioned(
            top: -70,
            right: -60,
            child: Container(
              width: 260,
              height: 260,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(
                  colors: [
                    brandPurple.withValues(alpha: 0.14),
                    brandPurple.withValues(alpha: 0.0),
                  ],
                ),
              ),
            ),
          ),
          Positioned(
            bottom: -80,
            left: -60,
            child: Container(
              width: 260,
              height: 260,
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
            child: Center(
              child: SingleChildScrollView(
                physics: const BouncingScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(24, 16, 24, 32),
                child: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 440),
                  child: ShakeWidget(
                    shakeTrigger: _shakeTrigger,
                    child: Form(
                      key: _form,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // Floating 3D Logo Header with levitation
                          FloatingLevitation(
                            distance: 4.0,
                            duration: const Duration(milliseconds: 2400),
                            child: Container(
                              width: 72,
                              height: 72,
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(22),
                                boxShadow: [
                                  BoxShadow(
                                    color: brandPurple.withValues(alpha: 0.28),
                                    blurRadius: 18,
                                    offset: const Offset(0, 6),
                                  ),
                                ],
                              ),
                              child: ClipRRect(
                                borderRadius: BorderRadius.circular(22),
                                child: Image.asset(
                                  'assets/logo/app_logo.png',
                                  width: 72,
                                  height: 72,
                                  fit: BoxFit.contain,
                                  errorBuilder: (_, _, _) => Container(
                                    color: brandPurple,
                                    child: const Icon(
                                      Icons.task_alt_rounded,
                                      color: Colors.white,
                                      size: 38,
                                    ),
                                  ),
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(height: 24),

                          // Sliding Pill Segmented Selector: [ Sign In | Create Account ]
                          _AuthSegmentedSelector(
                            isRegister: _register,
                            onChanged: _toggleMode,
                          ),
                          const SizedBox(height: 24),

                          // Animated Header with smooth cross-fade & slide
                          AnimatedSwitcher(
                            duration: motionDuration(context, 260),
                            switchInCurve: Curves.easeOutCubic,
                            switchOutCurve: Curves.easeInCubic,
                            transitionBuilder: (child, anim) => FadeTransition(
                              opacity: anim,
                              child: SlideTransition(
                                position: Tween<Offset>(
                                  begin: const Offset(0, 0.08),
                                  end: Offset.zero,
                                ).animate(anim),
                                child: child,
                              ),
                            ),
                            child: KeyedSubtree(
                              key: ValueKey(_register),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    _register
                                        ? 'Make room for\nwhat matters.'
                                        : 'A little focus.\nA lot of progress.',
                                    style: const TextStyle(
                                      fontSize: 32,
                                      fontWeight: FontWeight.w900,
                                      height: 1.15,
                                      color: Color(0xFF1B1D28),
                                      letterSpacing: -0.8,
                                    ),
                                  ),
                                  const SizedBox(height: 8),
                                  Text(
                                    _register
                                        ? 'Create your account and organize your daily work.'
                                        : 'Welcome back. Let’s plan a productive day.',
                                    style: const TextStyle(
                                      fontSize: 15,
                                      color: Color(0xFF7E8494),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                          const SizedBox(height: 24),

                          // Full Name Field (Animated Expansion for Register)
                          AnimatedCrossFade(
                            firstChild: Padding(
                              padding: const EdgeInsets.only(bottom: 16),
                              child: TextFormField(
                                controller: _name,
                                enabled: !_busy,
                                textCapitalization: TextCapitalization.words,
                                autofillHints: const [AutofillHints.name],
                                decoration: const InputDecoration(
                                  labelText: 'Full name',
                                  prefixIcon: Icon(Icons.person_outline),
                                ),
                                validator: (v) => _register && (v == null || v.trim().isEmpty)
                                    ? 'Enter your name.'
                                    : null,
                              ),
                            ),
                            secondChild: const SizedBox.shrink(),
                            crossFadeState: _register
                                ? CrossFadeState.showFirst
                                : CrossFadeState.showSecond,
                            duration: motionDuration(context, 260),
                          ),

                          // Email Address Field
                          TextFormField(
                            controller: _email,
                            enabled: !_busy,
                            keyboardType: TextInputType.emailAddress,
                            autofillHints: const [AutofillHints.email],
                            autocorrect: false,
                            decoration: const InputDecoration(
                              labelText: 'Email address',
                              prefixIcon: Icon(Icons.mail_outline),
                            ),
                            validator: (v) =>
                                v == null ||
                                    !RegExp(r'^[^\s@]+@[^\s@]+\.[^\s@]+$')
                                        .hasMatch(v.trim())
                                ? 'Enter a valid email address.'
                                : null,
                          ),
                          const SizedBox(height: 16),

                          // Password Field
                          TextFormField(
                            controller: _password,
                            enabled: !_busy,
                            obscureText: _obscure,
                            autofillHints: [
                              _register
                                  ? AutofillHints.newPassword
                                  : AutofillHints.password,
                            ],
                            decoration: InputDecoration(
                              labelText: 'Password',
                              prefixIcon: const Icon(Icons.lock_outline),
                              suffixIcon: IconButton(
                                onPressed: () => setState(() => _obscure = !_obscure),
                                tooltip: _obscure ? 'Show password' : 'Hide password',
                                icon: AnimatedRotation(
                                  turns: _obscure ? 0.0 : 0.5,
                                  duration: const Duration(milliseconds: 200),
                                  child: Icon(
                                    _obscure
                                        ? Icons.visibility_outlined
                                        : Icons.visibility_off_outlined,
                                  ),
                                ),
                              ),
                            ),
                            validator: (v) => v == null || v.isEmpty
                                ? 'Enter your password.'
                                : null,
                            onFieldSubmitted: (_) {
                              if (!_register && !_busy) _submit();
                            },
                          ),

                          // Password Strength Meter (Animated during registration)
                          if (_register)
                            _PasswordStrengthMeter(password: _password.text),

                          // Confirm Password Field (Animated Expansion)
                          AnimatedCrossFade(
                            firstChild: Padding(
                              padding: const EdgeInsets.only(top: 16),
                              child: TextFormField(
                                controller: _confirmation,
                                enabled: !_busy,
                                obscureText: _obscure,
                                decoration: const InputDecoration(
                                  labelText: 'Confirm password',
                                  prefixIcon: Icon(Icons.lock_outline),
                                ),
                                validator: (v) => _register && (v != _password.text)
                                    ? 'Passwords do not match.'
                                    : null,
                                onFieldSubmitted: (_) {
                                  if (!_busy) _submit();
                                },
                              ),
                            ),
                            secondChild: const SizedBox.shrink(),
                            crossFadeState: _register
                                ? CrossFadeState.showFirst
                                : CrossFadeState.showSecond,
                            duration: motionDuration(context, 260),
                          ),

                          // Animated Error Box
                          AnimatedSize(
                            duration: motionDuration(context, 220),
                            child: _error == null
                                ? const SizedBox.shrink()
                                : Container(
                                    margin: const EdgeInsets.only(top: 16),
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 16,
                                      vertical: 12,
                                    ),
                                    decoration: BoxDecoration(
                                      color: const Color(0xFFFEE2E2),
                                      borderRadius: BorderRadius.circular(14),
                                      border: Border.all(
                                        color: const Color(0xFFFCA5A5),
                                      ),
                                    ),
                                    child: Row(
                                      children: [
                                        const Icon(
                                          Icons.error_outline_rounded,
                                          color: Color(0xFFEF4444),
                                          size: 20,
                                        ),
                                        const SizedBox(width: 10),
                                        Expanded(
                                          child: Text(
                                            _error!,
                                            style: const TextStyle(
                                              color: Color(0xFFDC2626),
                                              fontSize: 13,
                                              fontWeight: FontWeight.w600,
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                          ),
                          const SizedBox(height: 24),

                          // Submit Button with Bouncing Interactive & Gradient Glow
                          BouncingInteractive(
                            onTap: _busy ? null : _submit,
                            scaleFactor: 0.96,
                            child: Container(
                              width: double.infinity,
                              height: 54,
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(16),
                                gradient: LinearGradient(
                                  colors: _busy
                                      ? [
                                          brandPurple.withValues(alpha: 0.6),
                                          brandPurple.withValues(alpha: 0.6),
                                        ]
                                      : [
                                          const Color(0xFF6B5CFF),
                                          brandPurple,
                                        ],
                                  begin: Alignment.topLeft,
                                  end: Alignment.bottomRight,
                                ),
                                boxShadow: [
                                  BoxShadow(
                                    color: brandPurple.withValues(alpha: 0.35),
                                    blurRadius: 16,
                                    offset: const Offset(0, 5),
                                  ),
                                ],
                              ),
                              child: Center(
                                child: _busy
                                    ? const SizedBox(
                                        width: 22,
                                        height: 22,
                                        child: CircularProgressIndicator(
                                          strokeWidth: 2.2,
                                          valueColor:
                                              AlwaysStoppedAnimation(Colors.white),
                                        ),
                                      )
                                    : Text(
                                        _register ? 'Create account' : 'Sign in',
                                        style: const TextStyle(
                                          color: Colors.white,
                                          fontSize: 16,
                                          fontWeight: FontWeight.w700,
                                          letterSpacing: -0.2,
                                        ),
                                      ),
                              ),
                            ),
                          ),
                          const SizedBox(height: 16),

                          // Switch Mode Text Button
                          Center(
                            child: BouncingInteractive(
                              onTap: _busy
                                  ? null
                                  : () => _toggleMode(!_register),
                              scaleFactor: 0.95,
                              child: Padding(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 16,
                                  vertical: 8,
                                ),
                                child: Text(
                                  _register
                                      ? 'Already have an account? Sign in'
                                      : 'New here? Create an account',
                                  style: const TextStyle(
                                    fontSize: 14,
                                    fontWeight: FontWeight.w600,
                                    color: brandPurple,
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
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

// Sliding Segmented Tab Pill Selector
class _AuthSegmentedSelector extends StatelessWidget {
  const _AuthSegmentedSelector({
    required this.isRegister,
    required this.onChanged,
  });

  final bool isRegister;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 48,
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: const Color(0xFFEBEFF8),
        borderRadius: BorderRadius.circular(16),
      ),
      child: LayoutBuilder(
        builder: (context, constraints) {
          final itemWidth = constraints.maxWidth / 2;
          return Stack(
            children: [
              // Gliding White Pill Indicator
              AnimatedPositioned(
                duration: motionDuration(context, 260),
                curve: Curves.easeOutCubic,
                left: isRegister ? itemWidth : 0,
                top: 0,
                bottom: 0,
                width: itemWidth,
                child: Container(
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(12),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.08),
                        blurRadius: 8,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                ),
              ),

              // Two Text Options: Sign In | Create Account
              Row(
                children: [
                  Expanded(
                    child: GestureDetector(
                      behavior: HitTestBehavior.opaque,
                      onTap: () {
                        HapticFeedback.selectionClick();
                        onChanged(false);
                      },
                      child: Center(
                        child: AnimatedDefaultTextStyle(
                          duration: motionDuration(context, 200),
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight:
                                !isRegister ? FontWeight.w800 : FontWeight.w600,
                            color: !isRegister
                                ? brandPurple
                                : const Color(0xFF6B7280),
                          ),
                          child: const Text('Sign In'),
                        ),
                      ),
                    ),
                  ),
                  Expanded(
                    child: GestureDetector(
                      behavior: HitTestBehavior.opaque,
                      onTap: () {
                        HapticFeedback.selectionClick();
                        onChanged(true);
                      },
                      child: Center(
                        child: AnimatedDefaultTextStyle(
                          duration: motionDuration(context, 200),
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight:
                                isRegister ? FontWeight.w800 : FontWeight.w600,
                            color: isRegister
                                ? brandPurple
                                : const Color(0xFF6B7280),
                          ),
                          child: const Text('Create Account'),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          );
        },
      ),
    );
  }
}

// Live Password Strength Meter
class _PasswordStrengthMeter extends StatelessWidget {
  const _PasswordStrengthMeter({required this.password});
  final String password;

  @override
  Widget build(BuildContext context) {
    if (password.isEmpty) return const SizedBox.shrink();

    final hasLength = password.length >= 8;
    final hasNumber = RegExp(r'[0-9]').hasMatch(password);
    final hasUpper = RegExp(r'[A-Z]').hasMatch(password);
    final hasSpecial = RegExp(r'[!@#$%^&*(),.?":{}|<>]').hasMatch(password);

    int score = 0;
    if (hasLength) score++;
    if (hasNumber) score++;
    if (hasUpper) score++;
    if (hasSpecial) score++;

    final Color barColor = score <= 1
        ? const Color(0xFFEF4444)
        : score == 2
            ? const Color(0xFFF59E0B)
            : score == 3
                ? const Color(0xFF3B82F6)
                : const Color(0xFF10B981);

    final String label = score <= 1
        ? 'Weak'
        : score == 2
            ? 'Fair'
            : score == 3
                ? 'Good'
                : 'Strong 💪';

    return Padding(
      padding: const EdgeInsets.only(top: 10, bottom: 4),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(4),
                  child: Container(
                    height: 5,
                    color: const Color(0xFFE2E8F0),
                    alignment: Alignment.centerLeft,
                    child: AnimatedFractionallySizedBox(
                      duration: const Duration(milliseconds: 300),
                      curve: Curves.easeOutCubic,
                      widthFactor: (score / 4).clamp(0.08, 1.0),
                      child: Container(color: barColor),
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 10),
              AnimatedDefaultTextStyle(
                duration: const Duration(milliseconds: 250),
                style: TextStyle(
                  fontSize: 11.5,
                  fontWeight: FontWeight.w700,
                  color: barColor,
                ),
                child: Text(label),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 6,
            runSpacing: 4,
            children: [
              _RequirementChip(label: '8+ chars', met: hasLength),
              _RequirementChip(label: 'Number', met: hasNumber),
              _RequirementChip(label: 'Uppercase', met: hasUpper),
              _RequirementChip(label: 'Symbol', met: hasSpecial),
            ],
          ),
        ],
      ),
    );
  }
}

class _RequirementChip extends StatelessWidget {
  const _RequirementChip({required this.label, required this.met});
  final String label;
  final bool met;

  @override
  Widget build(BuildContext context) {
    return AnimatedContainer(
      duration: const Duration(milliseconds: 250),
      curve: Curves.easeOutCubic,
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: met ? const Color(0xFFD1FAE5) : const Color(0xFFF1F5F9),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            met
                ? Icons.check_circle_rounded
                : Icons.radio_button_unchecked_rounded,
            size: 11,
            color: met ? const Color(0xFF10B981) : const Color(0xFF94A3B8),
          ),
          const SizedBox(width: 4),
          Text(
            label,
            style: TextStyle(
              fontSize: 10.5,
              fontWeight: FontWeight.w600,
              color: met ? const Color(0xFF065F46) : const Color(0xFF64748B),
            ),
          ),
        ],
      ),
    );
  }
}
