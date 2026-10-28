import '../tenant/tenant_models.dart';

enum StartupPhase { booting, needsStore, ready, invalid, suspended, offline, maintenance, updateRequired }

class TenantState {
  const TenantState({
    this.phase = StartupPhase.booting,
    this.tenant,
    this.config,
    this.fromCache = false,
    this.pendingLocation,
    this.message,
    this.brandingTenant,
  });
  final StartupPhase phase;
  final Tenant? tenant;
  final TenantConfig? config;

  /// True when ready using stale cached bootstrap (offline fallback).
  final bool fromCache;
  final String? pendingLocation;
  final String? message;

  /// Store whose cached branding the splash may show while booting. Only set
  /// when the store being opened is known to be this one (no wrong-store flash).
  final Tenant? brandingTenant;

  TenantState copyWith({
    StartupPhase? phase,
    Tenant? tenant,
    TenantConfig? config,
    bool? fromCache,
    String? pendingLocation,
    bool clearPending = false,
    String? message,
  }) =>
      TenantState(
        phase: phase ?? this.phase,
        tenant: tenant ?? this.tenant,
        config: config ?? this.config,
        fromCache: fromCache ?? this.fromCache,
        pendingLocation:
            clearPending ? null : (pendingLocation ?? this.pendingLocation),
        message: message ?? this.message,
        brandingTenant: brandingTenant,
      );
}
