import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:application/design/design.dart';

void main() {
  test('contrast ensureAA lifts low contrast colors', () {
    final c = Contrast.ensureAA(const Color(0xFFADADAD), Colors.white);
    expect(Contrast.ratio(c, Colors.white), greaterThanOrEqualTo(4.5));
  });
  test('grid columns scale with width', () {
    expect(Breakpoints.gridColumns(360), 2);
    expect(Breakpoints.gridColumns(800), 4);
  });
  testWidgets('skeleton, empty state and MinTouch render at 2.0 text scale', (t) async {
    for (final w in [320.0, 375.0, 430.0]) {
      await t.binding.setSurfaceSize(Size(w, 700));
      await t.pumpWidget(MaterialApp(
          home: MediaQuery(
              data: MediaQueryData(size: Size(w, 700), textScaler: const TextScaler.linear(2)),
              child: Scaffold(
                  body: Column(children: [
                const SectionSkeleton(),
                const Expanded(
                    child: EmptyState(icon: Icons.inbox, title: 'Nothing here yet', message: 'Come back later', actionLabel: 'Shop')),
                MinTouch(onTap: () {}, child: const Icon(Icons.add, size: 16)),
              ])))));
      await t.pump();
      expect(t.takeException(), isNull);
      expect(t.getSize(find.byType(MinTouch)).height, greaterThanOrEqualTo(kMinTouch));
    }
    await t.binding.setSurfaceSize(null);
  });
}
