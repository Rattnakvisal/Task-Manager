import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/testing.dart';
import 'package:task_manager_mobile/src/api.dart';
import 'package:task_manager_mobile/src/app.dart';
import 'package:task_manager_mobile/src/app_state.dart';
import 'package:task_manager_mobile/src/screens/auth_screen.dart';
import 'package:task_manager_mobile/src/screens/home_shell.dart';
import 'package:task_manager_mobile/src/screens/onboarding_screen.dart';

import 'app_test.dart' show MemoryStore, jsonResponse, user;

void main() {
  Future<AppState> openApp(WidgetTester tester, {bool signedIn = false}) async {
    tester.view.physicalSize = const Size(500, 960);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);

    final store = MemoryStore()..values['has_seen_onboarding'] = 'true';
    if (signedIn) store.values['access_token'] = 'saved';
    final state = AppState(
      ApiClient(
        client: MockClient((request) async {
          if (request.url.path.endsWith('/me')) {
            return jsonResponse({'user': user});
          }
          if (request.url.path.endsWith('/login')) {
            return jsonResponse({'token': 'saved', 'user': user});
          }
          return jsonResponse({'tasks': []});
        }),
      ),
      store,
    );
    addTearDown(() async {
      await tester.pumpWidget(const SizedBox());
      await tester.pump(const Duration(seconds: 1));
      state.dispose();
    });
    await tester.pumpWidget(TaskManagerApp(state: state));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 600));
    return state;
  }

  Future<void> openSignIn(WidgetTester tester) async {
    await tester.tap(
      find.text('Already have an account? Sign In', findRichText: true),
    );
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 600));
  }

  testWidgets('guests see onboarding even after a previous visit', (
    tester,
  ) async {
    await openApp(tester);

    expect(find.byType(OnboardingScreen), findsOneWidget);
    expect(find.byType(AuthScreen), findsNothing);

    await openSignIn(tester);
    expect(find.byType(AuthScreen), findsOneWidget);
    expect(
      tester.widget<AuthScreen>(find.byType(AuthScreen)).initialRegister,
      isFalse,
    );

    await tester.binding.handlePopRoute();
    await tester.pump();
    expect(find.byType(OnboardingScreen), findsOneWidget);
    expect(find.byType(AuthScreen), findsNothing);
    await tester.pump(const Duration(seconds: 1));
  });

  testWidgets('finishing onboarding opens registration', (tester) async {
    await openApp(tester);

    for (var page = 0; page < 2; page++) {
      await tester.tap(find.text('Continue'));
      await tester.pumpAndSettle();
    }
    await tester.tap(find.text('Get Started'));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 600));

    expect(find.byType(AuthScreen), findsOneWidget);
    expect(
      tester.widget<AuthScreen>(find.byType(AuthScreen)).initialRegister,
      isTrue,
    );
    expect(find.widgetWithText(TextFormField, 'Full name'), findsOneWidget);
  });

  testWidgets('skip opens registration directly', (tester) async {
    await openApp(tester);
    await tester.tap(find.text('Skip'));
    await tester.pump();

    expect(find.byType(AuthScreen), findsOneWidget);
    expect(
      tester.widget<AuthScreen>(find.byType(AuthScreen)).initialRegister,
      isTrue,
    );
  });

  testWidgets('onboarding remains usable on a small screen with large text', (
    tester,
  ) async {
    tester.view.physicalSize = const Size(320, 568);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    var started = false;
    var signedIn = false;
    await tester.pumpWidget(
      MaterialApp(
        home: MediaQuery(
          data: const MediaQueryData(
            size: Size(320, 568),
            textScaler: TextScaler.linear(1.6),
            disableAnimations: true,
          ),
          child: OnboardingScreen(
            onGetStarted: () => started = true,
            onSignIn: () => signedIn = true,
          ),
        ),
      ),
    );
    for (var page = 0; page < 2; page++) {
      await tester.tap(find.text('Continue'));
      await tester.pumpAndSettle();
      expect(tester.takeException(), isNull);
    }
    await tester.tap(find.text('Get Started'));
    expect(started, isTrue);
    await tester.tap(
      find.text('Already have an account? Sign In', findRichText: true),
    );
    expect(signedIn, isTrue);
    await tester.pumpWidget(const SizedBox());
  });

  testWidgets('saved sessions open home and signing out restores onboarding', (
    tester,
  ) async {
    final state = await openApp(tester, signedIn: true);
    expect(find.byType(HomeShell), findsOneWidget);
    expect(find.byType(OnboardingScreen), findsNothing);

    await state.clearSession();
    await tester.pump();
    expect(find.byType(OnboardingScreen), findsOneWidget);

    await openSignIn(tester);
    await state.authenticate(
      email: 'visal@example.com',
      password: 'SecurePass1!',
    );
    await tester.pump();
    expect(find.byType(HomeShell), findsOneWidget);

    await state.clearSession();
    await tester.pump();
    expect(find.byType(OnboardingScreen), findsOneWidget);
    expect(find.byType(AuthScreen), findsNothing);
    await tester.pump(const Duration(seconds: 1));
  });
}
