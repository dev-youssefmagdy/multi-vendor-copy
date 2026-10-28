import '../startup/tenant_state.dart';

/// Pure routing guard: maps tenant/startup state + location to a redirect.
class TenantGuard {
  const TenantGuard._();

  static const splash = '/splash';
  static const storeEntry = '/store-entry';
  static const invalid = '/invalid';
  static const suspended = '/suspended';
  static const offline = '/offline';
  static const home = '/home';

  static String? redirect(TenantState s, String location) {
    final path = Uri.parse(location).path;
    String? force(String target) => path == target ? null : target;
    switch (s.phase) {
      case StartupPhase.booting:
        return force(splash);
      case StartupPhase.needsStore:
        return force(storeEntry);
      case StartupPhase.invalid:
        return force(invalid);
      case StartupPhase.suspended:
        return force(suspended);
      case StartupPhase.offline:
        return force(offline);
      case StartupPhase.ready:
        const gates = {splash, storeEntry, invalid, suspended, offline};
        return gates.contains(path) ? home : null;
    }
  }
}
