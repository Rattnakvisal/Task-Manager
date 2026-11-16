import 'package:flutter/material.dart';

import 'app_state.dart';
import 'screens/auth_screen.dart';
import 'screens/home_shell.dart';
import 'screens/onboarding_screen.dart';
import 'widgets/workspace_loading.dart';

const brandBlue = Color(0xFF5B4DFF);
const brandPurple = Color(0xFF5B4DFF);
const surfaceBg = Color(0xFFF2F2F7); // Authentic Apple iOS System Grouped Background

class TaskManagerApp extends StatefulWidget {
  const TaskManagerApp({super.key, required this.state});
  final AppState state;
  @override
  State<TaskManagerApp> createState() => _TaskManagerAppState();
}

class _TaskManagerAppState extends State<TaskManagerApp> {
  @override
  void initState() {
    super.initState();
    widget.state.initialize();
  }

  @override
  Widget build(BuildContext context) => MaterialApp(
    title: 'Task Manager',
    debugShowCheckedModeBanner: false,
    theme: ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: brandPurple,
        primary: brandPurple,
      ),
      scaffoldBackgroundColor: surfaceBg,
      appBarTheme: const AppBarTheme(
        backgroundColor: surfaceBg,
        scrolledUnderElevation: 0,
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size(48, 52),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
        ),
      ),
      cardTheme: CardThemeData(
        elevation: 0,
        color: Colors.white,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
          side: const BorderSide(color: Color(0xFFF0F2F8)),
        ),
      ),
    ),
    home: _SessionGate(state: widget.state),
  );
}

// Task updates rebuild the task views, without rebuilding the session screen.
class _SessionGate extends StatefulWidget {
  const _SessionGate({required this.state});
  final AppState state;
  @override
  State<_SessionGate> createState() => _SessionGateState();
}

class _SessionGateState extends State<_SessionGate> {
  late Object _snapshot;
  bool _showAuthentication = false;
  bool _initialRegister = false;

  Object get _current =>
      (widget.state.starting, widget.state.startupError, widget.state.user);

  @override
  void initState() {
    super.initState();
    _snapshot = _current;
    widget.state.addListener(_update);
  }

  void _openAuthentication({bool register = false}) {
    setState(() {
      _showAuthentication = true;
      _initialRegister = register;
    });
  }

  void _update() {
    final next = _current;
    if (next != _snapshot) {
      setState(() {
        _snapshot = next;
        // A later sign-out starts the guest flow at onboarding again.
        if (widget.state.user != null) {
          _showAuthentication = false;
          _initialRegister = false;
        }
      });
    }
  }

  @override
  void didUpdateWidget(covariant _SessionGate oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.state != widget.state) {
      oldWidget.state.removeListener(_update);
      widget.state.addListener(_update);
      _snapshot = _current;
      _showAuthentication = false;
      _initialRegister = false;
    }
  }

  @override
  void dispose() {
    widget.state.removeListener(_update);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final state = widget.state;
    if (state.starting) {
      return const WorkspaceLoading();
    }
    if (state.startupError != null) {
      return Scaffold(
        body: SafeArea(
          child: Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.cloud_off_rounded, size: 52),
                  const SizedBox(height: 16),
                  Text(state.startupError!, textAlign: TextAlign.center),
                  const SizedBox(height: 24),
                  FilledButton(
                    onPressed: state.initialize,
                    child: const Text('Try again'),
                  ),
                  TextButton(
                    onPressed: state.clearSession,
                    child: const Text('Back to sign in'),
                  ),
                ],
              ),
            ),
          ),
        ),
      );
    }
    if (state.user == null) {
      if (!_showAuthentication) {
        return OnboardingScreen(
          onGetStarted: () => _openAuthentication(register: true),
          onSignIn: () => _openAuthentication(register: false),
        );
      }
      return PopScope(
        canPop: false,
        onPopInvokedWithResult: (didPop, result) {
          if (!didPop) {
            setState(() => _showAuthentication = false);
          }
        },
        child: AuthScreen(state: state, initialRegister: _initialRegister),
      );
    }
    return HomeShell(state: state);
  }
}
