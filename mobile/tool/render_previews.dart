// Run with: flutter test tool/render_previews.dart --dart-define=FLUTTER_SDK=C:/flutter
// These screenshots use sample tasks. The application itself uses live API data.
import 'dart:convert';
import 'dart:io';
import 'dart:ui' as ui;

import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:task_manager_mobile/src/app.dart';
import 'package:task_manager_mobile/src/services/api_client.dart';
import 'package:task_manager_mobile/src/state/app_state.dart';
import 'package:task_manager_mobile/src/utils/task_helpers.dart';

import '../test/app_test.dart' show MemoryStore;

void main() {
  testWidgets('render phone previews with sample tasks', (tester) async {
    tester.view.physicalSize = const Size(390, 844);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    final sdk = const String.fromEnvironment('FLUTTER_SDK');
    final font = FontLoader('Roboto');
    font.addFont(
      Future.value(
        ByteData.sublistView(
          File('$sdk/bin/cache/artifacts/material_fonts/roboto-regular.ttf')
              .readAsBytesSync(),
        ),
      ),
    );
    await font.load();
    final icons = FontLoader('MaterialIcons');
    icons.addFont(
      Future.value(
        ByteData.sublistView(
          File(
            '$sdk/bin/cache/artifacts/material_fonts/materialicons-regular.otf',
          ).readAsBytesSync(),
        ),
      ),
    );
    await icons.load();
    debugDisableShadows = false;
    addTearDown(() => debugDisableShadows = true);
    final today = apiDate(DateTime.now());
    final tasks = [
      {
        'id': 1,
        'title': 'Design task management UI',
        'category': 'UI/UX',
        'priority': 'high',
        'status': 'completed',
        'due_date': today,
      },
      {
        'id': 2,
        'title': 'Implement core features',
        'category': 'Development',
        'priority': 'medium',
        'status': 'pending',
        'due_date': today,
      },
      {
        'id': 3,
        'title': 'Write project documentation',
        'category': 'Documentation',
        'priority': 'low',
        'status': 'pending',
        'due_date': today,
      },
      {
        'id': 4,
        'title': 'Create task list flow',
        'category': 'Planning',
        'priority': 'high',
        'status': 'completed',
        'due_date': today,
      },
    ];
    final store = MemoryStore()..values['access_token'] = 'preview-only';
    final state = AppState(
      ApiClient(
        client: MockClient(
          (r) async => http.Response(
            jsonEncode(
              r.url.path.endsWith('/me')
                  ? {
                      'user': {
                        'id': 1,
                        'name': 'Chun',
                        'email': 'chun@example.com',
                      },
                    }
                  : {'tasks': tasks},
            ),
            200,
          ),
        ),
      ),
      store,
    );
    final boundary = GlobalKey();
    await tester.pumpWidget(
      RepaintBoundary(
        key: boundary,
        child: TaskManagerApp(state: state),
      ),
    );
    await tester.pumpAndSettle();
    Future<void> capture(String name) async {
      await tester.pumpAndSettle();
      expect(tester.takeException(), isNull);
      final render =
          boundary.currentContext!.findRenderObject()! as RenderRepaintBoundary;
      await tester.runAsync(() async {
        final image = await render.toImage(pixelRatio: 2);
        final bytes = await image.toByteData(format: ui.ImageByteFormat.png);
        await Directory('previews').create(recursive: true);
        await File('previews/$name.png')
            .writeAsBytes(bytes!.buffer.asUint8List());
        image.dispose();
      });
    }

    if (!const bool.fromEnvironment('PROFILE_ONLY')) {
      await capture('home');
      await tester.tap(find.text('Tasks'));
      await capture('tasks');
      await tester.tap(find.text('Calendar'));
      await capture('calendar');
    }
    await tester.tap(find.text('Profile'));
    await capture('profile');
    await tester.pumpWidget(const SizedBox());
    state.dispose();
    debugDisableShadows = true;
  });
}
