import 'package:flutter/material.dart';
import 'package:flutter/services.dart' show appFlavor;
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'app/app.dart';
import 'app/providers.dart';
import 'core/config/env.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  // Fail fast: a binary whose flavor and tenant config disagree must never
  // open some other store (or the generic picker) under a store's identity.
  final bindingError = TenantBinding.fromEnvironment().validate(flavor: appFlavor);
  if (bindingError != null) {
    runApp(ConfigErrorApp(bindingError));
    return;
  }
  final prefs = await SharedPreferences.getInstance();
  runApp(ProviderScope(
    // Failed requests are surfaced with a Retry button, not retried silently.
    retry: (_, _) => null,
    overrides: [sharedPreferencesProvider.overrideWithValue(prefs)],
    child: const MobileApp(),
  ));
}

/// Shown instead of the app when the build-time tenant config is invalid.
class ConfigErrorApp extends StatelessWidget {
  const ConfigErrorApp(this.message, {super.key});
  final String message;
  @override
  Widget build(BuildContext context) => MaterialApp(
        home: Scaffold(
          body: SafeArea(
            child: Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Text('This build is misconfigured.\n\n$message',
                    key: const Key('config_error'), textAlign: TextAlign.center),
              ),
            ),
          ),
        ),
      );
}
