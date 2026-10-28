import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../app/providers.dart';
import '../theme/theme_tokens.dart';

/// Neutral platform name shown on the first launch (no store yet).
const platformSplashName = 'Store App';

/// Branded splash: shows the cached store logo/name/colour from the stored
/// bootstrap while startup resolves; neutral platform mark otherwise.
class SplashScreen extends ConsumerWidget {
  const SplashScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final tenant = ref.watch(tenantControllerProvider.select((s) => s.brandingTenant));
    final cfg = tenant == null ? null : ref.read(tenantStorageProvider).loadConfig(tenant.id);
    final name = cfg?.displayName ?? tenant?.name;
    final image = cfg?.splashImageUrl ?? cfg?.logoUrl ?? tenant?.logo;
    final primary = ThemeTokens.parseColor(cfg?.colors['primary']);
    final dark = Theme.of(context).brightness == Brightness.dark;
    return Scaffold(
      body: AnimatedSwitcher(
        duration: const Duration(milliseconds: 250),
        child: Center(
          key: ValueKey(tenant?.id ?? 'neutral'),
          child: Semantics(
            label: name ?? platformSplashName,
            child: Column(mainAxisSize: MainAxisSize.min, children: [
              _SplashMark(url: image, fallbackColor: primary),
              if (name != null) ...[
                const SizedBox(height: 16),
                Text(name,
                    key: const Key('splash_name'),
                    textAlign: TextAlign.center,
                    style: Theme.of(context).textTheme.titleLarge),
              ],
              const SizedBox(height: 24),
              SizedBox(
                  width: 24,
                  height: 24,
                  child: CircularProgressIndicator(
                      key: const Key('splash'),
                      strokeWidth: 2.5,
                      color: primary ?? (dark ? Colors.white70 : const Color(0xFF374763)))),
            ]),
          ),
        ),
      ),
    );
  }
}

class _SplashMark extends StatelessWidget {
  const _SplashMark({this.url, this.fallbackColor});
  final String? url;
  final Color? fallbackColor;
  @override
  Widget build(BuildContext context) {
    final neutral = Container(
      key: const Key('splash_neutral_mark'),
      width: 96,
      height: 96,
      decoration: BoxDecoration(
          color: fallbackColor ?? const Color(0xFF374763),
          borderRadius: BorderRadius.circular(22)),
      child: const Icon(Icons.storefront_outlined, color: Colors.white, size: 48),
    );
    if (url == null) return neutral;
    return ConstrainedBox(
      constraints: const BoxConstraints(maxWidth: 200, maxHeight: 120),
      child: Image.network(url!,
          key: const Key('splash_image'),
          fit: BoxFit.contain,
          excludeFromSemantics: true,
          errorBuilder: (_, _, _) => neutral),
    );
  }
}

class _StatusScreen extends ConsumerWidget {
  const _StatusScreen(
      {required this.icon,
      required this.title,
      required this.body,
      required this.actions});
  final IconData icon;
  final String title;
  final String body;
  final List<Widget> actions;
  @override
  Widget build(BuildContext context, WidgetRef ref) => Scaffold(
        body: SafeArea(
          child: Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(mainAxisSize: MainAxisSize.min, children: [
                Icon(icon, size: 64),
                const SizedBox(height: 16),
                Text(title, style: Theme.of(context).textTheme.headlineSmall),
                const SizedBox(height: 8),
                Text(body, textAlign: TextAlign.center),
                const SizedBox(height: 24),
                ...actions,
              ]),
            ),
          ),
        ),
      );
}

class InvalidTenantScreen extends ConsumerWidget {
  const InvalidTenantScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) => _StatusScreen(
        icon: Icons.search_off,
        title: 'Store not found',
        body: 'We could not find this store. Check the address and try again.',
        actions: [
          FilledButton(
              key: const Key('change_store'),
              onPressed: ref.read(tenantControllerProvider.notifier).changeStore,
              child: const Text('Change store'))
        ],
      );
}

class SuspendedTenantScreen extends ConsumerWidget {
  const SuspendedTenantScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) => _StatusScreen(
        icon: Icons.pause_circle_outline,
        title: 'Store unavailable',
        body: 'This store is temporarily suspended.',
        actions: [
          FilledButton(
              key: const Key('change_store'),
              onPressed: ref.read(tenantControllerProvider.notifier).changeStore,
              child: const Text('Change store'))
        ],
      );
}

class OfflineScreen extends ConsumerWidget {
  const OfflineScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) => _StatusScreen(
        icon: Icons.wifi_off,
        title: 'You are offline',
        body: 'Connect to the internet and try again.',
        actions: [
          FilledButton(
              key: const Key('retry'),
              onPressed: ref.read(tenantControllerProvider.notifier).retry,
              child: const Text('Retry')),
          TextButton(
              onPressed: ref.read(tenantControllerProvider.notifier).changeStore,
              child: const Text('Change store')),
        ],
      );
}

class MaintenanceScreen extends ConsumerWidget {
  const MaintenanceScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) => _StatusScreen(
        icon: Icons.construction,
        title: 'Under maintenance',
        body: 'The store is being updated. Please try again shortly.',
        actions: [
          FilledButton(
              key: const Key('retry'),
              onPressed: ref.read(tenantControllerProvider.notifier).retry,
              child: const Text('Retry')),
          TextButton(
              onPressed: ref.read(tenantControllerProvider.notifier).changeStore,
              child: const Text('Change store')),
        ],
      );
}

class UpdateRequiredScreen extends ConsumerWidget {
  const UpdateRequiredScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) => _StatusScreen(
        icon: Icons.system_update,
        title: 'Update required',
        body: 'Please update the app to continue.',
        actions: [
          TextButton(
              key: const Key('change_store'),
              onPressed: ref.read(tenantControllerProvider.notifier).changeStore,
              child: const Text('Change store')),
        ],
      );
}

class PlaceholderScreen extends StatelessWidget {
  const PlaceholderScreen({super.key, required this.title});
  final String title;
  @override
  Widget build(BuildContext context) =>
      Scaffold(appBar: AppBar(title: Text(title)), body: Center(child: Text(title)));
}
