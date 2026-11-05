import 'dart:convert';
import 'dart:typed_data';

import 'package:archive/archive.dart';
import 'package:image/image.dart' as img;

import '../images.dart';
import 'plist.dart';

/// What a tenant's IPA must (and must not) contain.
class IpaExpectations {
  IpaExpectations({
    required this.tenantId,
    required this.bundleId,
    required this.displayName,
    required this.urlSchemes,
    required this.associatedDomains,
    required this.push,
    this.teamId,
    this.versionName,
    this.buildNumber,
    this.icon,
    this.storeHost,
    this.otherTenants = const [],
    this.release = true,
  });
  final String tenantId;
  final String bundleId;
  final String displayName;
  final Set<String> urlSchemes;

  /// Full entitlement values (`applinks:host`, `webcredentials:host`).
  final Set<String> associatedDomains;
  final bool push;
  final String? teamId;
  final String? versionName;
  final int? buildNumber;

  /// The tenant's 1024 icon (decoded).
  final img.Image? icon;

  /// Host the Dart code must be bound to (store apps).
  final String? storeHost;
  final List<OtherTenant> otherTenants;

  /// App Store build: signed, App Store profile, no get-task-allow.
  final bool release;
}

/// Identity of another tenant that must not appear in this IPA.
class OtherTenant {
  OtherTenant(this.id, {this.needles = const [], this.icon});
  final String id;

  /// Bundle/package ids and hosts.
  final List<String> needles;
  final img.Image? icon;
}

class IpaReport {
  final problems = <String>[];
  final warnings = <String>[];
  final notes = <String>[];

  /// Raw facts for build-info / summaries.
  final facts = <String, Object?>{};
}

/// Entitlement keys an App Store build of this app may carry. Anything else is
/// a capability the app does not use (spec §5.5) and is reported.
const allowedEntitlements = {
  'application-identifier',
  'com.apple.developer.team-identifier',
  'get-task-allow',
  'keychain-access-groups',
  'beta-reports-active',
  'com.apple.developer.associated-domains',
  'aps-environment',
};

/// Inspects an IPA (zip) without Xcode: Info.plist, signed entitlements (code
/// signature blob), embedded provisioning profile, icons, Firebase plist and
/// other tenants' identifiers. `codesign --verify` is added on macOS by the
/// caller.
IpaReport verifyIpa(List<int> ipaBytes, IpaExpectations e, {DateTime? now}) {
  final r = IpaReport();
  final Archive zip;
  try {
    zip = ZipDecoder().decodeBytes(ipaBytes);
  } catch (_) {
    r.problems.add('not a valid IPA (zip) file');
    return r;
  }
  final files = <String, ArchiveFile>{
    for (final f in zip.files)
      if (f.isFile) f.name: f,
  };
  if (files.isEmpty) {
    r.problems.add('not a valid IPA (zip) file');
    return r;
  }
  final infoPaths = files.keys
      .where((n) => RegExp(r'^Payload/[^/]+\.app/Info\.plist$').hasMatch(n))
      .toList();
  if (infoPaths.length != 1) {
    r.problems.add(
      'expected exactly one Payload/<App>.app/Info.plist, found ${infoPaths.length}',
    );
    return r;
  }
  final app = infoPaths.single.substring(
    0,
    infoPaths.single.length - 'Info.plist'.length,
  );
  Uint8List bytesOf(String name) => files[name]!.content;

  // ---- Info.plist
  Map<String, Object?> info;
  try {
    info = decodePlistDict(bytesOf(infoPaths.single));
  } on FormatException catch (x) {
    r.problems.add('Info.plist cannot be read: ${x.message}');
    return r;
  }
  final id = info['CFBundleIdentifier'];
  final name = info['CFBundleDisplayName'];
  final version = info['CFBundleShortVersionString'];
  final build = info['CFBundleVersion'];
  r.facts.addAll({
    'bundleId': id,
    'displayName': name,
    'version': version,
    'build': build,
  });
  r.notes.add('bundle $id  "$name"  version $version ($build)');
  if (id != e.bundleId) {
    r.problems.add('CFBundleIdentifier is $id, expected ${e.bundleId}');
  }
  if (name != e.displayName) {
    r.problems.add(
      'CFBundleDisplayName is "$name", expected "${e.displayName}"',
    );
  }
  if (e.versionName != null && version != e.versionName) {
    r.problems.add(
      'CFBundleShortVersionString is $version, expected ${e.versionName}',
    );
  }
  if (e.buildNumber != null && build != '${e.buildNumber}') {
    r.problems.add('CFBundleVersion is $build, expected ${e.buildNumber}');
  }
  final schemes = <String>{
    for (final t in (info['CFBundleURLTypes'] as List? ?? const []))
      if (t is Map)
        for (final s in (t['CFBundleURLSchemes'] as List? ?? const [])) '$s',
  };
  if (!_sameSet(schemes, e.urlSchemes)) {
    r.problems.add(
      'URL schemes are ${_fmt(schemes)}, expected ${_fmt(e.urlSchemes)}',
    );
  }
  for (final k in [
    'NSCameraUsageDescription',
    'NSPhotoLibraryUsageDescription',
  ]) {
    if ((info[k] as String? ?? '').isEmpty) {
      r.problems.add(
        'Info.plist $k is missing (App Store Connect rejects the upload)',
      );
    }
  }
  if (info['ITSAppUsesNonExemptEncryption'] != false) {
    r.warnings.add(
      'ITSAppUsesNonExemptEncryption is not false (export compliance asked per build)',
    );
  }
  final iconName =
      ((info['CFBundleIcons'] as Map?)?['CFBundlePrimaryIcon']
          as Map?)?['CFBundleIconName'];
  if (iconName == null) {
    r.problems.add(
      'Info.plist has no CFBundleIcons.CFBundlePrimaryIcon (no app icon compiled)',
    );
  }

  // ---- signed entitlements (from the code signature of the executable)
  final exe = info['CFBundleExecutable'];
  final exePath = '$app$exe';
  Map<String, Object?>? ents;
  if (exe is! String || !files.containsKey(exePath)) {
    r.problems.add('executable $exePath is missing');
  } else {
    ents = entitlementsFromMachO(bytesOf(exePath));
    if (ents == null && e.release) {
      r.problems.add('executable is not signed (no entitlements blob)');
    }
  }
  if (ents != null) {
    final appId = ents['application-identifier'];
    final team = ents['com.apple.developer.team-identifier'];
    r.facts['team'] = team;
    r.notes.add('signed for $appId');
    if (e.teamId != null) {
      if (appId != '${e.teamId}.${e.bundleId}') {
        r.problems.add(
          'entitlement application-identifier is $appId, expected ${e.teamId}.${e.bundleId}',
        );
      }
      if (team != e.teamId) {
        r.problems.add('signed for team $team, expected ${e.teamId}');
      }
    }
    if (e.release && ents['get-task-allow'] == true) {
      r.problems.add(
        'get-task-allow is true (development signing, not App Store)',
      );
    }
    final domains = <String>{
      for (final d
          in (ents['com.apple.developer.associated-domains'] as List? ??
              const []))
        '$d',
    };
    if (!_sameSet(domains, e.associatedDomains)) {
      r.problems.add(
        'associated domains are ${_fmt(domains)}, expected ${_fmt(e.associatedDomains)}',
      );
    }
    final aps = ents['aps-environment'];
    if (e.push && aps != 'production') {
      r.problems.add(
        'aps-environment is $aps, expected production (push enabled)',
      );
    } else if (!e.push && aps != null) {
      r.problems.add(
        'aps-environment is set but push is disabled for ${e.tenantId}',
      );
    }
    final extra = ents.keys.where((k) => !allowedEntitlements.contains(k));
    if (extra.isNotEmpty) {
      r.problems.add(
        'unexpected entitlements (capabilities not used): ${extra.join(', ')}',
      );
    }

    // ---- provisioning profile
    final profPath = '${app}embedded.mobileprovision';
    if (!files.containsKey(profPath)) {
      if (e.release) r.problems.add('embedded.mobileprovision is missing');
    } else {
      _checkProfile(r, e, ents, bytesOf(profPath), now ?? DateTime.now());
    }
  }

  // ---- Firebase
  final gsi = '${app}GoogleService-Info.plist';
  if (e.push) {
    if (!files.containsKey(gsi)) {
      r.problems.add('GoogleService-Info.plist is missing (push enabled)');
    } else {
      try {
        final g = decodePlistDict(bytesOf(gsi));
        if (g['BUNDLE_ID'] != e.bundleId) {
          r.problems.add(
            'GoogleService-Info.plist is for ${g['BUNDLE_ID']}, expected ${e.bundleId}',
          );
        }
      } on FormatException {
        r.problems.add('GoogleService-Info.plist cannot be read');
      }
    }
  } else if (files.containsKey(gsi)) {
    r.problems.add('GoogleService-Info.plist is bundled but push is disabled');
  }

  // ---- icon
  _checkIcons(r, e, app, files);

  // ---- other tenants' identity must not be inside; own host in the Dart code
  final needles = <String, String>{};
  for (final o in e.otherTenants) {
    for (final n in o.needles) {
      if (n.isNotEmpty &&
          n != e.bundleId &&
          !e.associatedDomains.any((d) => d.endsWith(':$n')) &&
          n != e.storeHost) {
        needles[n] = o.id;
      }
    }
  }
  var hostInDart = false;
  for (final f in files.values) {
    final s = latin1.decode(f.content);
    needles.forEach((n, owner) {
      if (s.contains(n)) {
        r.problems.add('${f.name} contains "$n" of tenant $owner');
      }
    });
    if (e.storeHost != null &&
        f.name.endsWith('App.framework/App') &&
        s.contains(e.storeHost!)) {
      hostInDart = true;
    }
  }
  if (e.storeHost != null) {
    if (hostInDart) {
      r.notes.add('Dart code is bound to ${e.storeHost}');
    } else {
      r.warnings.add(
        'could not find "${e.storeHost}" in App.framework (TENANT_HOST binding not confirmed)',
      );
    }
  }
  return r;
}

void _checkProfile(
  IpaReport r,
  IpaExpectations e,
  Map<String, Object?> ents,
  Uint8List bytes,
  DateTime now,
) {
  final xml = findEmbeddedXmlPlist(bytes);
  if (xml == null) {
    r.problems.add('embedded.mobileprovision has no readable plist');
    return;
  }
  final Map<String, Object?> prof;
  try {
    final v = decodeXmlPlist(xml);
    if (v is! Map<String, Object?>) throw const FormatException('not a dict');
    prof = v;
  } on FormatException {
    r.problems.add('embedded.mobileprovision cannot be parsed');
    return;
  }
  final pe = prof['Entitlements'] as Map? ?? const {};
  r.facts['profile'] = prof['Name'];
  r.notes.add('profile "${prof['Name']}"');
  final appId = pe['application-identifier'];
  if (e.teamId != null && appId != '${e.teamId}.${e.bundleId}') {
    r.problems.add(
      'provisioning profile is for $appId, expected ${e.teamId}.${e.bundleId}',
    );
  }
  final teams = [
    for (final t in (prof['TeamIdentifier'] as List? ?? const [])) '$t',
  ];
  if (e.teamId != null && !teams.contains(e.teamId)) {
    r.problems.add(
      'provisioning profile team ${teams.join(',')} is not ${e.teamId}',
    );
  }
  if (e.release) {
    if (prof['ProvisionedDevices'] != null) {
      r.problems.add(
        'provisioning profile lists devices (development/ad-hoc, not App Store)',
      );
    }
    if (prof['ProvisionsAllDevices'] == true) {
      r.problems.add(
        'provisioning profile is an enterprise (in-house) profile',
      );
    }
    if (pe['get-task-allow'] == true) {
      r.problems.add(
        'provisioning profile allows get-task-allow (development)',
      );
    }
  }
  final exp = prof['ExpirationDate'];
  if (exp is DateTime) {
    r.facts['profileExpires'] = exp.toUtc().toIso8601String();
    if (exp.isBefore(now)) {
      r.problems.add(
        'provisioning profile expired on ${exp.toIso8601String()}',
      );
    } else if (exp.difference(now).inDays < 30) {
      r.warnings.add(
        'provisioning profile expires on ${exp.toIso8601String()} (< 30 days): renew with match',
      );
    }
  }
  // Signed entitlements must be allowed by the profile.
  for (final k in ents.keys) {
    if (k == 'keychain-access-groups' || k == 'get-task-allow') continue;
    if (!pe.containsKey(k)) {
      r.problems.add(
        'entitlement $k is not allowed by the provisioning profile',
      );
    }
  }
}

void _checkIcons(
  IpaReport r,
  IpaExpectations e,
  String app,
  Map<String, ArchiveFile> files,
) {
  final loose =
      files.keys
          .where(
            (n) =>
                n.startsWith(app) &&
                !n.substring(app.length).contains('/') &&
                n.substring(app.length).startsWith('AppIcon') &&
                n.endsWith('.png'),
          )
          .toList()
        ..sort();
  if (loose.isEmpty) {
    if (files.containsKey('${app}Assets.car')) {
      r.warnings.add('icon only inside Assets.car; pixel comparison skipped');
    } else {
      r.problems.add('no compiled app icon (no AppIcon*.png, no Assets.car)');
    }
    return;
  }
  if (e.icon == null) {
    r.warnings.add(
      'no reference icon for ${e.tenantId}; icon pixels not compared',
    );
    return;
  }
  var compared = 0;
  for (final n in loose) {
    final image = decodeIosPng(files[n]!.content);
    if (image == null) {
      r.warnings.add('$n cannot be decoded; skipped');
      continue;
    }
    compared++;
    final own = iconDistance(image, e.icon!);
    if (own > iconTolerance) {
      r.problems.add(
        '$n does not match tenants/${e.tenantId} icon (difference ${own.toStringAsFixed(1)})',
      );
    }
    for (final o in e.otherTenants) {
      if (o.icon == null) continue;
      final d = iconDistance(image, o.icon!);
      if (d <= iconTolerance && d < own) {
        r.problems.add('$n looks like the icon of tenant ${o.id}');
      }
    }
  }
  if (compared > 0) {
    r.notes.add('app icon matches tenants/${e.tenantId} ($compared file(s))');
  }
}

/// Mean absolute RGB difference (0-255) after scaling [ref] to [image]'s size.
double iconDistance(img.Image image, img.Image ref) {
  final a = image.convert(numChannels: 4, format: img.Format.uint8);
  final b = img
      .copyResize(
        ref,
        width: a.width,
        height: a.height,
        interpolation: img.Interpolation.average,
      )
      .convert(numChannels: 4, format: img.Format.uint8);
  var sum = 0;
  var n = 0;
  for (var y = 0; y < a.height; y++) {
    for (var x = 0; x < a.width; x++) {
      final p = a.getPixel(x, y);
      final q = b.getPixel(x, y);
      sum +=
          (p.r - q.r).abs().toInt() +
          (p.g - q.g).abs().toInt() +
          (p.b - q.b).abs().toInt();
      n += 3;
    }
  }
  return n == 0 ? 255 : sum / n;
}

/// Tolerance for re-encoding/resampling by Xcode's asset compiler.
const iconTolerance = 12.0;

/// Decodes a PNG from an app bundle, including Xcode's "CgBI" variant
/// (raw deflate, BGRA). Returns null when it cannot be decoded.
img.Image? decodeIosPng(Uint8List bytes) {
  if (!isPng(bytes)) return null;
  final chunks = <(String, Uint8List)>[];
  var at = 8;
  final bd = ByteData.sublistView(bytes);
  while (at + 8 <= bytes.length) {
    final len = bd.getUint32(at);
    final type = ascii.decode(
      bytes.sublist(at + 4, at + 8),
      allowInvalid: true,
    );
    if (at + 12 + len > bytes.length) return null;
    chunks.add((type, Uint8List.sublistView(bytes, at + 8, at + 8 + len)));
    at += 12 + len;
    if (type == 'IEND') break;
  }
  final cgbi = chunks.any((c) => c.$1 == 'CgBI');
  if (!cgbi) {
    try {
      return img.decodePng(bytes);
    } catch (_) {
      return null;
    }
  }
  try {
    final idat = BytesBuilder();
    for (final c in chunks) {
      if (c.$1 == 'IDAT') idat.add(c.$2);
    }
    final raw = Inflate(idat.takeBytes()).getBytes();
    final out = BytesBuilder()..add(bytes.sublist(0, 8));
    void chunk(String type, List<int> data) {
      final t = ascii.encode(type);
      final len = ByteData(4)..setUint32(0, data.length);
      final crc = ByteData(4)..setUint32(0, getCrc32([...t, ...data]));
      out
        ..add(len.buffer.asUint8List())
        ..add(t)
        ..add(data)
        ..add(crc.buffer.asUint8List());
    }

    for (final c in chunks) {
      if (c.$1 == 'CgBI' || c.$1 == 'IDAT' || c.$1 == 'IEND') continue;
      chunk(c.$1, c.$2);
    }
    chunk('IDAT', ZLibEncoder().encode(raw));
    chunk('IEND', const []);
    final image = img.decodePng(out.takeBytes());
    if (image == null) return null;
    // BGRA -> RGBA (premultiplication is irrelevant for opaque icons).
    for (final px in image) {
      final r = px.r;
      px
        ..r = px.b
        ..b = r;
    }
    return image;
  } catch (_) {
    return null;
  }
}

/// Entitlements embedded in a Mach-O code signature (blob magic 0xfade7171).
Map<String, Object?>? entitlementsFromMachO(Uint8List exe) {
  const magic = [0xfa, 0xde, 0x71, 0x71];
  final bd = ByteData.sublistView(exe);
  for (var i = 0; i + 8 <= exe.length; i++) {
    if (exe[i] != magic[0] ||
        exe[i + 1] != magic[1] ||
        exe[i + 2] != magic[2] ||
        exe[i + 3] != magic[3]) {
      continue;
    }
    final len = bd.getUint32(i + 4);
    if (len < 8 || i + len > exe.length) continue;
    try {
      final v = decodePlist(exe.sublist(i + 8, i + len));
      if (v is Map<String, Object?>) return v;
    } on FormatException {
      continue;
    }
  }
  return null;
}

bool _sameSet(Set<String> a, Set<String> b) =>
    a.length == b.length && a.containsAll(b);

String _fmt(Set<String> s) =>
    s.isEmpty ? '(none)' : (s.toList()..sort()).join(', ');
