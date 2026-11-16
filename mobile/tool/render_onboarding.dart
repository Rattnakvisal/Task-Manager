// flutter test tool/render_onboarding.dart --dart-define=FLUTTER_SDK=C:/flutter
import 'dart:io';
import 'dart:ui' as ui;

import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:task_manager_mobile/src/screens/onboarding_screen.dart';

void main() {
  testWidgets('render all three onboarding screens', (tester) async {
    tester.view.physicalSize = const Size(390, 844);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    const sdk = String.fromEnvironment('FLUTTER_SDK');
    for (final (family, file) in [
      ('Roboto', 'roboto-regular.ttf'),
      ('MaterialIcons', 'materialicons-regular.otf'),
    ]) {
      final loader = FontLoader(family);
      loader.addFont(
        Future.value(
          ByteData.sublistView(
            File('$sdk/bin/cache/artifacts/material_fonts/$file')
                .readAsBytesSync(),
          ),
        ),
      );
      await loader.load();
    }
    final boundary = GlobalKey();
    await tester.pumpWidget(
      RepaintBoundary(
        key: boundary,
        child: MaterialApp(
          debugShowCheckedModeBanner: false,
          theme: ThemeData(
            useMaterial3: true,
            fontFamily: 'Roboto',
            colorScheme: ColorScheme.fromSeed(
              seedColor: const Color(0xFF5B4DFF),
              primary: const Color(0xFF5B4DFF),
            ),
          ),
          home: OnboardingScreen(onGetStarted: () {}, onSignIn: () {}),
        ),
      ),
    );
    await tester.runAsync(
      () => precacheImage(
        const AssetImage('assets/logo/app_logo.png'),
        tester.element(find.byType(OnboardingScreen)),
      ),
    );
    for (var page = 0; page < 3; page++) {
      await tester.pumpAndSettle();
      expect(tester.takeException(), isNull);
      final render =
          boundary.currentContext!.findRenderObject()! as RenderRepaintBoundary;
      await tester.runAsync(() async {
        final image = await render.toImage(pixelRatio: 2);
        final bytes = await image.toByteData(format: ui.ImageByteFormat.png);
        await Directory('previews').create(recursive: true);
        await File('previews/onboarding-${page + 1}.png')
            .writeAsBytes(bytes!.buffer.asUint8List());
        image.dispose();
      });
      if (page < 2) await tester.tap(find.text('Continue'));
    }
    await tester.pumpWidget(const SizedBox());
  });
}
