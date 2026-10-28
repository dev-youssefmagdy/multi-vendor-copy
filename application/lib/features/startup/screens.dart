import '../../design/fields.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../app/providers.dart';

class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key});
  @override
  Widget build(BuildContext context) => const Scaffold(
      body: Center(child: CircularProgressIndicator(key: Key('splash'))));
}

class StoreEntryScreen extends ConsumerStatefulWidget {
  const StoreEntryScreen({super.key});
  @override
  ConsumerState<StoreEntryScreen> createState() => _StoreEntryState();
}

class _StoreEntryState extends ConsumerState<StoreEntryScreen> {
  final _ctl = TextEditingController();
  @override
  void dispose() {
    _ctl.dispose();
    super.dispose();
  }

  void _submit() =>
      ref.read(tenantControllerProvider.notifier).submitInput(_ctl.text);

  @override
  Widget build(BuildContext context) {
    final message = ref.watch(tenantControllerProvider.select((s) => s.message));
    final recents = ref.read(tenantStorageProvider).loadRecent();
    return Scaffold(
      appBar: AppBar(title: const Text('Find your store')),
      body: SafeArea(
        child: ListView(padding: const EdgeInsets.all(16), children: [
          AppTextField(
            fieldKey: const Key('store_input'),
            controller: _ctl,
            keyboardType: TextInputType.url,
            autocorrect: false,
            textInputAction: TextInputAction.go,
            onSubmitted: (_) => _submit(),
            label: 'Store URL or store code',
            errorText: message,
          ),
          const SizedBox(height: 12),
          FilledButton(
              key: const Key('store_submit'),
              onPressed: _submit,
              child: const Text('Continue')),
          if (recents.isNotEmpty) ...[
            const SizedBox(height: 24),
            Text('Recent stores', style: Theme.of(context).textTheme.titleMedium),
            for (final t in recents)
              ListTile(
                key: Key('recent_${t.id}'),
                title: Text(t.name),
                subtitle: Text(t.domain),
                onTap: () =>
                    ref.read(tenantControllerProvider.notifier).selectRecent(t),
              ),
          ],
        ]),
      ),
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
