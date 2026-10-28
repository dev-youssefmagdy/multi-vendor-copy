import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:application/app/connectivity_banner.dart';
import 'package:application/app/providers.dart';
import 'package:application/core/network/connectivity_service.dart';

void main() {
  testWidgets('banner appears when offline and disappears when back online', (t) async {
    final c = FakeConnectivityService(true);
    await t.pumpWidget(ProviderScope(
        overrides: [connectivityProvider.overrideWithValue(c)],
        child: const MaterialApp(home: Scaffold(body: ConnectivityBanner(child: Text('content'))))));
    await t.pump();
    expect(find.textContaining("You're offline"), findsNothing);
    c.set(false);
    await t.pump();
    await t.pump();
    expect(find.textContaining("You're offline"), findsOneWidget);
    c.set(true);
    await t.pump();
    await t.pump();
    expect(find.textContaining("You're offline"), findsNothing);
  });
}
