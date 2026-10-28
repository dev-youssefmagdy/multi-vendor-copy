import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../design/design.dart';
import 'providers.dart';

/// Live online/offline state from the connectivity service.
final onlineProvider = StreamProvider<bool>((ref) async* {
  final c = ref.watch(connectivityProvider);
  yield await c.isOnline();
  yield* c.onChanged;
});

/// Wraps the whole app: shows a banner above every screen while offline.
class ConnectivityBanner extends ConsumerWidget {
  const ConnectivityBanner({super.key, required this.child});
  final Widget child;
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final online = ref.watch(onlineProvider).maybeWhen(data: (v) => v, orElse: () => true);
    return Column(children: [
      if (!online)
        Material(
          color: const Color(0xFF3B3B3B),
          child: SafeArea(bottom: false, child: OfflineBanner(offline: true)),
        ),
      Expanded(
        // The banner already consumed the status-bar inset.
        child: online
            ? child
            : MediaQuery.removePadding(context: context, removeTop: true, child: child),
      ),
    ]);
  }
}
