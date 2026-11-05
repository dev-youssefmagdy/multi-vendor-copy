import 'dart:convert';
import 'dart:io';

import 'package:path/path.dart' as p;

import 'images.dart';
import 'model.dart';
import 'platforms/android_signing.dart';
import 'platforms/ios_signing.dart';
import 'platforms/plist.dart';
import 'workspace.dart';

enum Severity { error, warning }

class Issue {
  Issue(this.severity, this.where, this.message);
  final Severity severity;

  /// File (and field) the issue is about, e.g. `tenants/demo/tenant.json: android.applicationId`.
  final String where;
  final String message;
  bool get isError => severity == Severity.error;
  @override
  String toString() => '${isError ? 'error' : 'warning'}: $where: $message';
}

class ValidationOptions {
  const ValidationOptions({
    this.tenants,
    this.platform,
    this.release = false,
    this.requireEnabled = false,
    this.env = const {},
    this.apiBase,
  });

  /// Tenants whose own rules are checked (null = all registered). Registry-wide
  /// uniqueness is always checked across every registered tenant.
  final Set<String>? tenants;

  /// `android` / `ios`, or null for both.
  final String? platform;

  /// Release gate: placeholders, signing and PLATFORM_API_BASE become errors.
  final bool release;

  /// Fail when a selected tenant is disabled in index.json (build/run).
  final bool requireEnabled;
  final Map<String, String> env;
  final String? apiBase;
}

class ValidationReport {
  ValidationReport(this.issues);
  final List<Issue> issues;
  List<Issue> get errors => [
    for (final i in issues)
      if (i.isError) i,
  ];
  List<Issue> get warnings => [
    for (final i in issues)
      if (!i.isError) i,
  ];
  bool get ok => errors.isEmpty;
}

// ---------------------------------------------------------------- rules ----

final tenantIdPattern = RegExp(r'^[a-z][a-z0-9]{2,30}$');

/// Folder / Gradle source-set / build-type names a tenant id must not take.
const reservedTenantIds = {
  'main',
  'debug',
  'profile',
  'release',
  'test',
  'androidtest',
  'template',
  'all',
  'none',
  'default',
  'lint',
};

final _hostPattern = RegExp(
  r'^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z][a-z0-9-]{0,61}[a-z0-9]$',
);
final _appIdPattern = RegExp(
  r'^[a-zA-Z][a-zA-Z0-9_]*(\.[a-zA-Z][a-zA-Z0-9_]*)+$',
);
final _bundleIdPattern = RegExp(r'^[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)+$');
final _schemePattern = RegExp(r'^[a-z][a-z0-9+.-]{1,40}$');
final _colorPattern = RegExp(r'^#[0-9A-Fa-f]{6}$');
final _envNamePattern = RegExp(r'^[A-Za-z0-9_-]{1,100}$');
final _teamIdPattern = RegExp(r'^[A-Z0-9]{10}$');

const _reservedSchemes = {
  'http',
  'https',
  'file',
  'content',
  'intent',
  'javascript',
  'data',
  'about',
  'market',
  'mailto',
  'tel',
  'sms',
  'payment',
  'app',
  'android',
  'ftp',
};
const _javaKeywords = {
  'abstract',
  'assert',
  'boolean',
  'break',
  'byte',
  'case',
  'catch',
  'char',
  'class',
  'const',
  'continue',
  'default',
  'do',
  'double',
  'else',
  'enum',
  'extends',
  'final',
  'finally',
  'float',
  'for',
  'goto',
  'if',
  'implements',
  'import',
  'instanceof',
  'int',
  'interface',
  'long',
  'native',
  'new',
  'package',
  'private',
  'protected',
  'public',
  'return',
  'short',
  'static',
  'strictfp',
  'super',
  'switch',
  'synchronized',
  'this',
  'throw',
  'throws',
  'transient',
  'try',
  'void',
  'volatile',
  'while',
  'true',
  'false',
  'null',
};

/// Allowed keys per object (unknown keys are typos until proven otherwise).
/// `$comment` is allowed everywhere. Extend here when a phase adds fields.
const manifestSchema = <String, Set<String>>{
  '': {
    'schemaVersion',
    'id',
    'type',
    'displayName',
    'store',
    'android',
    'ios',
    'branding',
    'push',
  },
  'store': {'host', 'extraLinkHosts'},
  'android': {'enabled', 'applicationId', 'urlScheme', 'secretsEnvironment'},
  'ios': {
    'enabled',
    'bundleId',
    'teamId',
    'appStoreConnectAppId',
    'sku',
    'urlScheme',
    'secretsEnvironment',
  },
  'branding': {
    'appIcon',
    'appIconForeground',
    'adaptiveIconBackground',
    'splash',
  },
  'push': {'enabled'},
};

/// Key names that suggest a secret was put in the public manifest.
final _secretKey = RegExp(
  r'(password|passphrase|secret(?!sEnvironment)|private_?key|keystore|api_?key|token|credential|\bp8\b)',
  caseSensitive: false,
);
const _secretFileExt = {
  '.jks',
  '.keystore',
  '.p12',
  '.pfx',
  '.p8',
  '.pem',
  '.key',
  '.mobileprovision',
  '.cer',
  '.der',
};

/// Values that are obviously not production yet (fine for debug, errors with --release).
bool isPlaceholderId(String v) {
  final segs = v.toLowerCase().split('.');
  return segs.contains('example') ||
      segs.contains('yourcompany') ||
      segs.contains('replace');
}

bool isPlaceholderHost(String h) =>
    h == 'example.com' ||
    h.endsWith('.example.com') ||
    h.endsWith('.example') ||
    h.endsWith('.test') ||
    h.endsWith('.invalid') ||
    h.endsWith('.localhost');

bool isValidHost(String h) => h.length <= 253 && _hostPattern.hasMatch(h);

// ------------------------------------------------------------ validator ----

class Validator {
  Validator(this.ws);
  final Workspace ws;

  ValidationReport validate(LoadedRegistry loaded, ValidationOptions o) {
    final issues = <Issue>[];
    void err(String where, String msg) =>
        issues.add(Issue(Severity.error, where, msg));
    void warn(String where, String msg) =>
        issues.add(Issue(Severity.warning, where, msg));
    void placeholder(String where, String msg) => o.release
        ? err(where, '$msg (placeholder; not allowed for release)')
        : warn(where, '$msg (placeholder)');

    for (final e in loaded.errors) {
      err(e.file, e.message);
    }
    final reg = loaded.registry;
    final indexRel = ws.rel(ws.indexFile);
    if (reg == null) return ValidationReport(issues);

    // ---- registry
    if (reg.schemaVersion != 1) {
      err(
        '$indexRel: schemaVersion',
        'must be 1 (found ${loaded.rawIndex?['schemaVersion']})',
      );
    }
    if (!isValidHost(reg.platformDomain)) {
      err(
        '$indexRel: platformDomain',
        '"${reg.platformDomain}" is not a valid domain (e.g. platform.com)',
      );
    } else if (isPlaceholderHost(reg.platformDomain)) {
      placeholder('$indexRel: platformDomain', '"${reg.platformDomain}"');
    }
    final rawList = loaded.rawIndex?['tenants'];
    if (rawList is! List || rawList.isEmpty) {
      err(
        '$indexRel: tenants',
        'must be a non-empty list of {"id": ..., "enabled": true|false}',
      );
    } else {
      for (var i = 0; i < rawList.length; i++) {
        final e = rawList[i];
        if (e is! Map || e['id'] is! String) {
          err('$indexRel: tenants[$i]', 'needs a string "id"');
        } else if (e['enabled'] is! bool) {
          err('$indexRel: tenants[$i].enabled', 'must be true or false');
        }
        if (e is Map) {
          for (final k in e.keys) {
            if (k != 'id' && k != 'enabled' && k != r'$comment') {
              err('$indexRel: tenants[$i].$k', 'unknown field');
            }
          }
        }
      }
    }
    final seen = <String>{};
    for (final e in reg.entries) {
      if (!seen.add(e.id)) {
        err('$indexRel: tenants', 'duplicate tenant id "${e.id}"');
      }
      _checkId(e.id, '$indexRel: tenants', err);
      if (tenantIdPattern.hasMatch(e.id) &&
          !Directory(ws.tenantDir(e.id)).existsSync()) {
        err(ws.rel(ws.tenantDir(e.id)), 'tenant folder missing');
      }
    }
    // Unregistered tenant folders are probably forgotten.
    final dir = Directory(ws.tenantsDir);
    if (dir.existsSync()) {
      for (final d in dir.listSync().whereType<Directory>()) {
        final name = p.basename(d.path);
        if (name.startsWith('_') || name.startsWith('.')) continue;
        if (!seen.contains(name)) {
          warn(
            ws.rel(d.path),
            'folder is not registered in tenants/index.json',
          );
        }
      }
    }

    // ---- selected tenants exist / enabled
    final selected = o.tenants ?? reg.ids.toSet();
    for (final id in selected) {
      final e = reg.entry(id);
      if (e == null) {
        err(
          indexRel,
          'unknown tenant "$id" (registered: ${reg.ids.join(', ')})',
        );
      } else if (o.requireEnabled && !e.enabled) {
        err('$indexRel: $id', 'tenant is disabled ("enabled": false)');
      }
    }

    // ---- per-tenant rules
    for (final id in selected) {
      final m = reg.manifests[id];
      if (m == null) continue;
      _validateTenant(m, reg, o, err, warn, placeholder);
    }

    // ---- registry-wide uniqueness (all registered tenants)
    final platforms = reg.manifests.values
        .where((m) => m.isPlatform)
        .map((m) => m.id)
        .toList();
    if (platforms.length > 1) {
      err(
        indexRel,
        'only one tenant may have "type": "platform" (found ${platforms.join(', ')})',
      );
    }
    _unique(
      reg,
      'android.applicationId',
      (m) => m.androidEnabled ? [m.applicationId] : const [],
      err,
      caseInsensitive: true,
    );
    _unique(
      reg,
      'ios.bundleId',
      (m) => m.iosEnabled ? [m.bundleId] : const [],
      err,
      caseInsensitive: true,
    );
    _unique(
      reg,
      'android.urlScheme',
      (m) => m.androidEnabled ? [m.androidScheme] : const [],
      err,
    );
    _unique(
      reg,
      'ios.urlScheme',
      (m) => m.iosEnabled ? [m.iosScheme] : const [],
      err,
    );
    _unique(reg, 'store host', (m) => m.linkHosts, err);
    return ValidationReport(issues);
  }

  void _checkId(String id, String where, void Function(String, String) err) {
    if (!tenantIdPattern.hasMatch(id)) {
      err(
        where,
        'tenant id "$id" must match ^[a-z][a-z0-9]{2,30}\$ (lowercase letters/digits, 3-31 chars)',
      );
    } else if (reservedTenantIds.contains(id) || id.startsWith('test')) {
      err(
        where,
        'tenant id "$id" is reserved (Gradle source set / build type name)',
      );
    }
  }

  void _unique(
    Registry reg,
    String field,
    List<String?> Function(TenantManifest) values,
    void Function(String, String) err, {
    bool caseInsensitive = false,
  }) {
    final owners = <String, String>{};
    for (final m in reg.manifests.values) {
      for (final v in values(m)) {
        if (v == null || v.isEmpty) continue;
        final key = caseInsensitive ? v.toLowerCase() : v;
        final other = owners[key];
        if (other != null && other != m.id) {
          err(
            ws.rel(ws.manifestFile(m.id)),
            '$field "$v" is already used by tenant "$other"',
          );
        } else {
          owners[key] = m.id;
        }
      }
    }
  }

  void _validateTenant(
    TenantManifest m,
    Registry reg,
    ValidationOptions o,
    void Function(String, String) err,
    void Function(String, String) warn,
    void Function(String, String) placeholder,
  ) {
    final file = ws.rel(ws.manifestFile(m.id));
    String at(String field) => '$file: $field';
    final raw = m.raw;

    // schema shape
    for (final entry in manifestSchema.entries) {
      final obj = entry.key.isEmpty ? raw : raw[entry.key];
      if (obj == null) continue;
      if (obj is! Map) {
        err(at(entry.key), 'must be an object');
        continue;
      }
      for (final k in obj.keys) {
        if (k == r'$comment') continue;
        if (!entry.value.contains(k)) {
          err(
            at(entry.key.isEmpty ? '$k' : '${entry.key}.$k'),
            'unknown field (allowed: ${entry.value.join(', ')})',
          );
        }
      }
    }
    _scanSecrets(raw, '', at, err);

    if (m.schemaVersion != 1) err(at('schemaVersion'), 'must be 1');
    if (m.declaredId != m.id) {
      err(
        at('id'),
        'must equal the folder name "${m.id}" (found "${m.declaredId}")',
      );
    }
    if (m.type == null) {
      err(
        at('type'),
        'must be "store" (white-label) or "platform" (generic store browser)',
      );
    }

    final name = m.displayName;
    if (name.isEmpty) {
      err(at('displayName'), 'required');
    } else if (name.length > 30) {
      err(
        at('displayName'),
        'max 30 characters (store listing / home-screen limit), found ${name.length}',
      );
    } else if (name.runes.any((r) => r < 0x20) ||
        RegExp(r'[<>&"\\]').hasMatch(name)) {
      err(
        at('displayName'),
        r'must not contain control characters or < > & " \',
      );
    } else if (name.toUpperCase().contains('REPLACE')) {
      placeholder(at('displayName'), '"$name"');
    }

    // store / links
    if (m.type == TenantType.store) {
      final host = m.storeHost;
      if (host == null || host.isEmpty) {
        err(
          at('store.host'),
          'required for a store app (e.g. "alpha.${reg.platformDomain}")',
        );
      } else if (!isValidHost(host)) {
        err(
          at('store.host'),
          '"$host" is not a valid host name (no scheme, port, path or wildcard)',
        );
      } else {
        if (host == reg.platformDomain) {
          err(
            at('store.host'),
            'must be a store domain, not the platform domain',
          );
        }
        if (isPlaceholderHost(host)) placeholder(at('store.host'), '"$host"');
      }
      final extra = m.store['extraLinkHosts'];
      if (extra != null && extra is! List) {
        err(at('store.extraLinkHosts'), 'must be a list of host names');
      }
      final seen = <String>{};
      for (final h in m.extraLinkHosts) {
        if (!isValidHost(h)) {
          err(at('store.extraLinkHosts'), '"$h" is not a valid host name');
        } else if (!seen.add(h) || h == host) {
          err(at('store.extraLinkHosts'), 'duplicate host "$h"');
        } else if (isPlaceholderHost(h)) {
          placeholder(at('store.extraLinkHosts'), '"$h"');
        }
      }
    } else if (m.type == TenantType.platform && raw['store'] != null) {
      err(at('store'), 'a platform tenant has no fixed store (remove "store")');
    }

    final wantAndroid = o.platform == null || o.platform == 'android';
    final wantIos = o.platform == null || o.platform == 'ios';
    if (!m.androidEnabled && !m.iosEnabled) {
      warn(file, 'neither android nor ios is enabled');
    }
    if (o.platform != null && !m.enabledFor(o.platform!) && o.requireEnabled) {
      err(
        at('${o.platform}.enabled'),
        'platform is not enabled for this tenant',
      );
    }

    // android
    if (raw['android'] != null && m.android['enabled'] is! bool) {
      err(at('android.enabled'), 'must be true or false');
    }
    if (m.androidEnabled && wantAndroid) {
      final id = m.applicationId ?? '';
      if (!_appIdPattern.hasMatch(id) || id.length > 150) {
        err(
          at('android.applicationId'),
          '"$id" is not a valid Android application id (e.g. com.brand.shop: 2+ segments, letters first)',
        );
      } else {
        final bad = id
            .split('.')
            .where((s) => _javaKeywords.contains(s.toLowerCase()))
            .toList();
        if (bad.isNotEmpty) {
          err(
            at('android.applicationId'),
            'segment "${bad.first}" is a Java keyword',
          );
        }
        if (isPlaceholderId(id)) {
          placeholder(at('android.applicationId'), '"$id"');
        }
      }
      _checkScheme(m.androidScheme, at('android.urlScheme'), err);
      final envName = m.android['secretsEnvironment'];
      if (envName != null &&
          (envName is! String || !_envNamePattern.hasMatch(envName))) {
        err(
          at('android.secretsEnvironment'),
          'must be a GitHub Environment name ([A-Za-z0-9_-])',
        );
      }
      if (o.release) {
        final s = resolveAndroidSigning(ws, m.id, o.env);
        if (s == null) {
          err(
            '${m.id}: android signing',
            'no upload key. Set ANDROID_SIGNING_TENANT=${m.id} + ${signingEnvKeys.join(', ')}, '
                'or create android/keystores/${m.id}.properties (see tenants/README.md)',
          );
        } else if (!File(s.storeFile).existsSync()) {
          err(
            '${m.id}: android signing',
            'keystore from ${s.description} does not exist',
          );
        }
      }
    }

    // ios (schema-level only here; the iOS pipeline adds its own checks)
    if (raw['ios'] != null && m.ios['enabled'] is! bool) {
      err(at('ios.enabled'), 'must be true or false');
    }
    if (m.iosEnabled && wantIos) {
      final b = m.bundleId ?? '';
      if (!_bundleIdPattern.hasMatch(b) || b.length > 155) {
        err(
          at('ios.bundleId'),
          '"$b" is not a valid iOS bundle id (e.g. com.brand.shop)',
        );
      } else if (isPlaceholderId(b)) {
        placeholder(at('ios.bundleId'), '"$b"');
      }
      _checkScheme(m.iosScheme, at('ios.urlScheme'), err);
      if (m.androidEnabled &&
          m.androidScheme != null &&
          m.iosScheme != m.androidScheme) {
        // One APP_URL_SCHEME define per build and one scheme per store in the backend (payment return).
        err(
          at('ios.urlScheme'),
          '"${m.iosScheme ?? ''}" must equal android.urlScheme "${m.androidScheme}" (the backend stores one scheme per store)',
        );
      }
      final team = m.teamId;
      if (team != null && !_teamIdPattern.hasMatch(team)) {
        err(at('ios.teamId'), '"$team" must be the 10-character Apple Team ID');
      } else if (team == null && o.release) {
        err(at('ios.teamId'), 'required for an iOS release build');
      }
      final asc = m.ios['appStoreConnectAppId'];
      if (asc != null &&
          (asc is! String || !RegExp(r'^\d{6,12}$').hasMatch(asc))) {
        err(
          at('ios.appStoreConnectAppId'),
          'must be the numeric Apple ID of the app record, as a string',
        );
      }
      final sku = m.ios['sku'];
      if (sku != null &&
          (sku is! String ||
              !RegExp(r'^[A-Za-z0-9._-]{1,100}$').hasMatch(sku))) {
        err(at('ios.sku'), 'letters, digits, . _ - only');
      }
      final envName = m.ios['secretsEnvironment'];
      if (envName != null &&
          (envName is! String || !_envNamePattern.hasMatch(envName))) {
        err(
          at('ios.secretsEnvironment'),
          'must be a GitHub Environment name ([A-Za-z0-9_-])',
        );
      }
      // Values go into an xcconfig (`//` starts a comment) and Info.plist
      // (`$(` is a build-setting reference).
      if (name.contains('//') || name.contains(r'$')) {
        err(
          at('displayName'),
          r'must not contain "//" or "$" (iOS build settings)',
        );
      }
      if (o.release) {
        if (asc == null) {
          err(
            at('ios.appStoreConnectAppId'),
            'required for an iOS release (Apple ID of the App Store Connect app record)',
          );
        }
        if (sku == null) {
          err(
            at('ios.sku'),
            'required for an iOS release (SKU of the app record)',
          );
        }
        final creds = resolveIosCredentials(ws, m.id, o.env);
        if (creds == null) {
          err(
            '${m.id}: ios signing',
            'no Apple credentials. Set IOS_SIGNING_TENANT=${m.id} + ${[...matchKeys, ...ascKeys].join(', ')}, '
                'ASC_PRIVATE_KEY_P8_BASE64, or create ios/signing/${m.id}.env (see tenants/README.md)',
          );
        } else if (creds.missing.isNotEmpty) {
          err(
            '${m.id}: ios signing',
            '${creds.description} is missing ${creds.missing.join(', ')}',
          );
        } else {
          final key = creds.values['ASC_KEY_PATH'];
          if ((creds.values['ASC_PRIVATE_KEY_P8_BASE64'] ?? '').isEmpty &&
              key != null &&
              !File(key).existsSync()) {
            err(
              '${m.id}: ios signing',
              'ASC_KEY_PATH from ${creds.description} does not exist',
            );
          }
        }
      }
      if (m.pushEnabled) _checkFirebasePlist(m, err);
    }

    // branding
    final needsIcon =
        m.type == TenantType.store &&
        ((m.androidEnabled && wantAndroid) || (m.iosEnabled && wantIos));
    final icon = m.appIcon;
    if (icon == null) {
      if (needsIcon) {
        err(
          at('branding.appIcon'),
          'required: 1024x1024 PNG, e.g. "assets/app_icon.png"',
        );
      }
    } else {
      final info = _checkImage(m, icon, at('branding.appIcon'), err);
      if (info != null) {
        if (info.hasAlphaChannel && m.iosEnabled && wantIos) {
          err(
            at('branding.appIcon'),
            'has an alpha channel; App Store icons must be opaque RGB (export without transparency)',
          );
        } else if (info.hasTransparency) {
          warn(
            at('branding.appIcon'),
            'has transparent pixels (legacy Android icons will show them)',
          );
        }
        if (_sameAsTemplate(m, icon)) {
          placeholder(at('branding.appIcon'), 'still the _template icon');
        }
      }
    }
    final fg = m.appIconForeground;
    if (fg != null) _checkImage(m, fg, at('branding.appIconForeground'), err);
    final bg = m.branding['adaptiveIconBackground'];
    if (bg != null && (bg is! String || !_colorPattern.hasMatch(bg))) {
      err(at('branding.adaptiveIconBackground'), 'must be a #RRGGBB color');
    }
    final splash = m.branding['splash'];
    if (splash is String) {
      if (_safeTenantPath(m, splash) == null ||
          !File(_safeTenantPath(m, splash)!).existsSync()) {
        err(at('branding.splash'), 'file not found: $splash');
      } else {
        warn(
          at('branding.splash'),
          'not used by the build yet (native splash keeps the neutral mark)',
        );
      }
    }

    // push (Phase 7)
    if (m.pushEnabled) {
      err(
        at('push.enabled'),
        'push notifications are not wired into the build yet (PUBLISH_TO_STORES.md Phase 7)',
      );
      if (m.androidEnabled) _checkGoogleServices(m, at, err);
    }

    _scanSecretFiles(m, err);

    if (o.release &&
        ((wantAndroid && m.androidEnabled) || (wantIos && m.iosEnabled))) {
      final api = o.apiBase ?? o.env['PLATFORM_API_BASE'];
      final uri = api == null ? null : Uri.tryParse(api);
      if (uri == null || uri.scheme != 'https' || uri.host.isEmpty) {
        err(
          'PLATFORM_API_BASE',
          'release builds need PLATFORM_API_BASE=https://<central api origin> (env or --api-base)',
        );
      } else if (isPlaceholderHost(uri.host)) {
        err('PLATFORM_API_BASE', '"$api" is a placeholder');
      }
    }
  }

  void _checkScheme(
    String? s,
    String where,
    void Function(String, String) err,
  ) {
    if (s == null || !_schemePattern.hasMatch(s)) {
      err(
        where,
        '"${s ?? ''}" must be a lowercase URL scheme (letters first, 2-41 chars, a-z0-9+.-), e.g. "alphashop"',
      );
    } else if (_reservedSchemes.contains(s)) {
      err(where, '"$s" is reserved; use a scheme unique to this app');
    }
  }

  /// Resolves a manifest-relative path, refusing anything outside the tenant folder.
  String? _safeTenantPath(TenantManifest m, String rel) {
    if (rel.isEmpty || p.isAbsolute(rel) || rel.contains('\\')) return null;
    final full = p.normalize(p.join(m.dir, rel));
    return p.isWithin(m.dir, full) ? full : null;
  }

  PngInfo? _checkImage(
    TenantManifest m,
    Object? rel,
    String where,
    void Function(String, String) err,
  ) {
    if (rel is! String) {
      err(where, 'must be a path relative to tenants/${m.id}/');
      return null;
    }
    final full = _safeTenantPath(m, rel);
    if (full == null) {
      err(where, '"$rel" must be a relative path inside tenants/${m.id}/');
      return null;
    }
    final f = File(full);
    if (!f.existsSync()) {
      err(where, 'file not found: ${ws.rel(full)}');
      return null;
    }
    final info = readPng(f);
    if (info == null) {
      err(where, '${ws.rel(full)} is not a valid PNG');
      return null;
    }
    if (info.width != info.height) {
      err(
        where,
        '${ws.rel(full)} must be square (is ${info.width}x${info.height})',
      );
    } else if (info.width < 1024) {
      err(
        where,
        '${ws.rel(full)} must be at least 1024x1024 (is ${info.width}x${info.height})',
      );
    }
    return info;
  }

  bool _sameAsTemplate(TenantManifest m, String rel) {
    final full = _safeTenantPath(m, rel);
    final tpl = File(p.join(ws.templateDir, 'assets', 'app_icon.png'));
    if (full == null || !tpl.existsSync() || !File(full).existsSync()) {
      return false;
    }
    final a = File(full).readAsBytesSync();
    final b = tpl.readAsBytesSync();
    if (a.length != b.length) return false;
    for (var i = 0; i < a.length; i++) {
      if (a[i] != b[i]) return false;
    }
    return true;
  }

  void _scanSecrets(
    Object? node,
    String path,
    String Function(String) at,
    void Function(String, String) err,
  ) {
    if (node is Map) {
      node.forEach((k, v) {
        final key = '$k';
        final here = path.isEmpty ? key : '$path.$key';
        if (_secretKey.hasMatch(key)) {
          err(
            at(here),
            'looks like a secret. tenant.json is public: keep secrets in GitHub Environment secrets',
          );
        }
        _scanSecrets(v, here, at, err);
      });
    } else if (node is List) {
      for (var i = 0; i < node.length; i++) {
        _scanSecrets(node[i], '$path[$i]', at, err);
      }
    } else if (node is String && node.contains('-----BEGIN')) {
      err(at(path), 'contains key material');
    }
  }

  void _scanSecretFiles(TenantManifest m, void Function(String, String) err) {
    final dir = Directory(m.dir);
    if (!dir.existsSync()) return;
    for (final f
        in dir
            .listSync(recursive: true, followLinks: false)
            .whereType<File>()) {
      final ext = p.extension(f.path).toLowerCase();
      if (_secretFileExt.contains(ext)) {
        err(
          ws.rel(f.path),
          'signing/credential files must never be committed under tenants/',
        );
      } else if (ext == '.json' && f.lengthSync() < 1 << 20) {
        final s = f.readAsStringSync();
        if (s.contains('"private_key"') || s.contains('-----BEGIN')) {
          err(
            ws.rel(f.path),
            'looks like a service-account / private key file; keep it in CI secrets',
          );
        }
      }
    }
  }

  void _checkFirebasePlist(
    TenantManifest m,
    void Function(String, String) err,
  ) {
    final f = File(p.join(m.dir, 'ios', 'GoogleService-Info.plist'));
    if (!f.existsSync()) {
      err(
        '${ws.rel(ws.manifestFile(m.id))}: push.enabled',
        'needs ${ws.rel(f.path)} for ${m.bundleId}',
      );
      return;
    }
    try {
      final d = decodePlistDict(f.readAsBytesSync());
      if (d['BUNDLE_ID'] != m.bundleId) {
        err(
          ws.rel(f.path),
          'is for bundle id ${d['BUNDLE_ID']}, expected ${m.bundleId} (another tenant\'s Firebase app?)',
        );
      }
    } on FormatException {
      err(ws.rel(f.path), 'is not a valid GoogleService-Info.plist');
    }
  }

  void _checkGoogleServices(
    TenantManifest m,
    String Function(String) at,
    void Function(String, String) err,
  ) {
    final f = File(p.join(m.dir, 'android', 'google-services.json'));
    if (!f.existsSync()) {
      err(at('push.enabled'), 'needs ${ws.rel(f.path)} for ${m.applicationId}');
      return;
    }
    try {
      final j = jsonDecode(f.readAsStringSync()) as Map<String, dynamic>;
      final pkgs = [
        for (final c in (j['client'] as List? ?? const []))
          ((c as Map)['client_info']
              as Map?)?['android_client_info']?['package_name'],
      ];
      if (!pkgs.contains(m.applicationId)) {
        err(
          ws.rel(f.path),
          'has no Android app for ${m.applicationId} (found: ${pkgs.join(', ')})',
        );
      }
    } catch (_) {
      err(ws.rel(f.path), 'is not a valid google-services.json');
    }
  }
}
