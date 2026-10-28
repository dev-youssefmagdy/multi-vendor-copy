import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../app/providers.dart';
import 'home_repository.dart';

final homeRepositoryProvider = Provider<HomeRepository>((ref) {
  ref.watch(sessionEpochProvider); // fresh cache on tenant switch
  ref.watch(tenantControllerProvider.select((s) => s.tenant?.id));
  return HomeRepository(ref.watch(apiClientProvider));
});

/// Data of one home section key for the active tenant.
final homeSectionProvider =
    FutureProvider.family<Map<String, dynamic>, String>((ref, key) {
  return ref.watch(homeRepositoryProvider).section(key);
});
