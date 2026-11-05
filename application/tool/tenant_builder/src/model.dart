import 'dart:convert';
import 'dart:io';

import 'workspace.dart';

/// `tenants/index.json`.
class Registry {
  Registry({
    required this.schemaVersion,
    required this.platformDomain,
    required this.entries,
    required this.manifests,
  });

  final int schemaVersion;
  final String platformDomain;
  final List<RegistryEntry> entries;

  /// Parsed `tenant.json` per registered id (absent when unreadable).
  final Map<String, TenantManifest> manifests;

  RegistryEntry? entry(String id) {
    for (final e in entries) {
      if (e.id == id) return e;
    }
    return null;
  }

  List<String> get ids => [for (final e in entries) e.id];
  List<String> get enabledIds => [
    for (final e in entries)
      if (e.enabled) e.id,
  ];
}

class RegistryEntry {
  const RegistryEntry(this.id, this.enabled);
  final String id;
  final bool enabled;
}

enum TenantType { platform, store }

/// `tenants/<id>/tenant.json`, typed view over the raw JSON. Fields are
/// nullable here; [Validator] reports what is missing or invalid.
class TenantManifest {
  TenantManifest(this.id, this.dir, this.raw);

  final String id;
  final String dir;
  final Map<String, dynamic> raw;

  Map<String, dynamic> _obj(String k) => raw[k] is Map<String, dynamic>
      ? raw[k] as Map<String, dynamic>
      : const {};

  int? get schemaVersion =>
      raw['schemaVersion'] is int ? raw['schemaVersion'] as int : null;
  String? get declaredId => raw['id'] as String?;
  TenantType? get type => switch (raw['type']) {
    'platform' => TenantType.platform,
    'store' => TenantType.store,
    _ => null,
  };
  bool get isPlatform => type == TenantType.platform;
  String get displayName => (raw['displayName'] as String? ?? '').trim();

  Map<String, dynamic> get store => _obj('store');
  String? get storeHost => (store['host'] as String?)?.trim().toLowerCase();
  List<String> get extraLinkHosts => [
    for (final h in (store['extraLinkHosts'] as List? ?? const []))
      if (h is String) h.trim().toLowerCase(),
  ];

  /// Hosts this app owns (App Links / Universal Links). Empty for platform.
  List<String> get linkHosts =>
      isPlatform ? const [] : [?storeHost, ...extraLinkHosts];

  Map<String, dynamic> get android => _obj('android');
  bool get androidEnabled => android['enabled'] == true;
  String? get applicationId => android['applicationId'] as String?;
  String? get androidScheme => android['urlScheme'] as String?;
  String get androidSecretsEnvironment =>
      android['secretsEnvironment'] as String? ?? 'tenant-$id-android';

  Map<String, dynamic> get ios => _obj('ios');
  bool get iosEnabled => ios['enabled'] == true;
  String? get bundleId => ios['bundleId'] as String?;
  String? get iosScheme => ios['urlScheme'] as String?;
  String? get teamId => ios['teamId'] as String?;
  String? get appStoreConnectAppId => ios['appStoreConnectAppId'] is String
      ? ios['appStoreConnectAppId'] as String
      : null;
  String? get sku => ios['sku'] is String ? ios['sku'] as String : null;
  String get iosSecretsEnvironment =>
      ios['secretsEnvironment'] as String? ?? 'tenant-$id-ios';

  Map<String, dynamic> get branding => _obj('branding');
  String? get appIcon => branding['appIcon'] as String?;
  String? get appIconForeground => branding['appIconForeground'] as String?;
  String get adaptiveIconBackground =>
      branding['adaptiveIconBackground'] as String? ?? '#FFFFFF';

  bool get pushEnabled => _obj('push')['enabled'] == true;

  bool enabledFor(String platform) => platform == 'android'
      ? androidEnabled
      : (platform == 'ios' ? iosEnabled : false);
}

/// Raised for unreadable registry files (the validator turns these into issues).
class RegistryLoadError {
  RegistryLoadError(this.file, this.message);
  final String file;
  final String message;
}

class LoadedRegistry {
  LoadedRegistry(this.registry, this.errors, this.rawIndex);
  final Registry? registry;
  final List<RegistryLoadError> errors;
  final Map<String, dynamic>? rawIndex;
}

LoadedRegistry loadRegistry(Workspace ws) {
  final errors = <RegistryLoadError>[];
  final indexPath = ws.rel(ws.indexFile);
  Map<String, dynamic>? index;
  try {
    final decoded = jsonDecode(File(ws.indexFile).readAsStringSync());
    if (decoded is! Map<String, dynamic>) {
      errors.add(RegistryLoadError(indexPath, 'must be a JSON object'));
    } else {
      index = decoded;
    }
  } on FileSystemException {
    errors.add(RegistryLoadError(indexPath, 'file not found'));
  } on FormatException catch (e) {
    errors.add(RegistryLoadError(indexPath, 'invalid JSON: ${e.message}'));
  }
  if (index == null) return LoadedRegistry(null, errors, null);

  final entries = <RegistryEntry>[];
  final list = index['tenants'];
  if (list is List) {
    for (final e in list) {
      if (e is Map && e['id'] is String) {
        entries.add(RegistryEntry(e['id'] as String, e['enabled'] == true));
      }
    }
  }
  final manifests = <String, TenantManifest>{};
  for (final e in entries) {
    if (manifests.containsKey(e.id)) continue;
    final file = ws.manifestFile(e.id);
    // Never follow ids that could escape tenants/ (validator reports them).
    if (!RegExp(r'^[a-z][a-z0-9]{2,30}$').hasMatch(e.id)) continue;
    try {
      final decoded = jsonDecode(File(file).readAsStringSync());
      if (decoded is Map<String, dynamic>) {
        manifests[e.id] = TenantManifest(e.id, ws.tenantDir(e.id), decoded);
      } else {
        errors.add(RegistryLoadError(ws.rel(file), 'must be a JSON object'));
      }
    } on FileSystemException {
      errors.add(RegistryLoadError(ws.rel(file), 'file not found'));
    } on FormatException catch (e) {
      errors.add(RegistryLoadError(ws.rel(file), 'invalid JSON: ${e.message}'));
    }
  }
  return LoadedRegistry(
    Registry(
      schemaVersion: index['schemaVersion'] is int
          ? index['schemaVersion'] as int
          : -1,
      platformDomain: (index['platformDomain'] as String? ?? '').trim(),
      entries: entries,
      manifests: manifests,
    ),
    errors,
    index,
  );
}
