import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../features/shop/commerce_providers.dart';
import '../features/startup/tenant_state.dart';
import 'providers.dart';
import 'router.dart';

class MobileApp extends ConsumerStatefulWidget {
  const MobileApp({super.key});
  @override
  ConsumerState<MobileApp> createState() => _MobileAppState();
}

class _MobileAppState extends ConsumerState<MobileApp> {
  StreamSubscription<dynamic>? _sub;

  @override
  void initState() {
    super.initState();
    Future.microtask(() async {
      await ref.read(tenantControllerProvider.notifier).start();
      if (!mounted) return;
      _sub = ref
          .read(deepLinkServiceProvider)
          .targets
          .listen(ref.read(tenantControllerProvider.notifier).handleDeepLink);
    });
  }

  @override
  void dispose() {
    _sub?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final router = ref.watch(routerProvider);
    ref.listen(tenantControllerProvider, (_, next) {
      if (next.phase == StartupPhase.ready && next.pendingLocation != null) {
        final loc = next.pendingLocation!;
        ref.read(tenantControllerProvider.notifier).consumePending();
        router.go(loc);
      }
    });
    final theme = ref.watch(resolvedThemeProvider);
    return MaterialApp.router(
      title: ref.watch(tenantControllerProvider.select((s) => s.config?.displayName)) ??
          'Store',
      theme: theme.tokens.toThemeData(),
      routerConfig: router,
      builder: (context, child) {
        // Honour user text scaling but cap at 200% so layouts stay usable.
        final mq = MediaQuery.of(context);
        return MediaQuery(
            data: mq.copyWith(textScaler: mq.textScaler.clamp(maxScaleFactor: 2.0)),
            child: child ?? const SizedBox.shrink());
      },
      locale: ref.watch(appLocaleProvider),
      supportedLocales: const [Locale('en'), Locale('ar'), Locale('he'), Locale('fa'), Locale('ur')],
      localizationsDelegates: GlobalMaterialLocalizations.delegates,
    );
  }
}
