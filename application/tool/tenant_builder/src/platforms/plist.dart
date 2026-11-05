import 'dart:convert';
import 'dart:typed_data';

/// Minimal property-list support for the iOS pipeline, runnable on Linux:
///  * [decodePlist] reads binary (`bplist00`, what Xcode puts in a built app)
///    and XML plists (entitlements, provisioning profiles, sources).
///  * [encodeXmlPlist] writes deterministic XML plists (generated files).
///
/// Values map to Dart as: dict -> `Map<String, Object?>`, array -> `List`,
/// string -> String, integer -> int, real -> double, bool -> bool,
/// data -> Uint8List, date -> DateTime (UTC).
Object? decodePlist(List<int> bytes) {
  final b = bytes is Uint8List ? bytes : Uint8List.fromList(bytes);
  if (b.length >= 8 &&
      ascii.decode(b.sublist(0, 6), allowInvalid: true) == 'bplist') {
    return _BinaryPlist(b).decode();
  }
  return decodeXmlPlist(utf8.decode(b, allowMalformed: true));
}

/// Like [decodePlist] but requires a dictionary at the top.
Map<String, Object?> decodePlistDict(List<int> bytes) {
  final v = decodePlist(bytes);
  if (v is! Map<String, Object?>) {
    throw const FormatException('plist root is not a dictionary');
  }
  return v;
}

/// Extracts the first `<?xml ... </plist>` document embedded in binary data
/// (CMS-signed provisioning profiles, code-signature entitlement blobs).
String? findEmbeddedXmlPlist(List<int> bytes, [int start = 0]) {
  final open = _indexOf(bytes, utf8.encode('<?xml'), start);
  if (open < 0) return null;
  final endTag = utf8.encode('</plist>');
  final close = _indexOf(bytes, endTag, open);
  if (close < 0) return null;
  return utf8.decode(
    bytes.sublist(open, close + endTag.length),
    allowMalformed: true,
  );
}

int _indexOf(List<int> hay, List<int> needle, [int start = 0]) {
  if (needle.isEmpty) return start;
  outer:
  for (var i = start; i <= hay.length - needle.length; i++) {
    if (hay[i] != needle[0]) continue;
    for (var j = 1; j < needle.length; j++) {
      if (hay[i + j] != needle[j]) continue outer;
    }
    return i;
  }
  return -1;
}

// ------------------------------------------------------------- binary ----

class _BinaryPlist {
  _BinaryPlist(this.b) : data = ByteData.sublistView(b);
  final Uint8List b;
  final ByteData data;
  late final int _offsetSize;
  late final int _refSize;
  late final List<int> _offsets;

  Object? decode() {
    if (b.length < 40) throw const FormatException('binary plist too short');
    final t = b.length - 32;
    _offsetSize = b[t + 6];
    _refSize = b[t + 7];
    final numObjects = _uint(t + 8, 8);
    final top = _uint(t + 16, 8);
    final tableOffset = _uint(t + 24, 8);
    if (_offsetSize < 1 ||
        _refSize < 1 ||
        numObjects < 1 ||
        tableOffset + numObjects * _offsetSize > t) {
      throw const FormatException('corrupt binary plist trailer');
    }
    _offsets = [
      for (var i = 0; i < numObjects; i++)
        _uint(tableOffset + i * _offsetSize, _offsetSize),
    ];
    return _object(top, 0);
  }

  int _uint(int at, int size) {
    var v = 0;
    for (var i = 0; i < size; i++) {
      v = (v << 8) | b[at + i];
    }
    return v;
  }

  /// Length nibble (0xF = an int object follows). Returns (length, data start).
  (int, int) _len(int at) {
    final low = b[at] & 0x0F;
    if (low != 0x0F) return (low, at + 1);
    final intMarker = b[at + 1];
    if (intMarker >> 4 != 0x1) throw const FormatException('bad length');
    final size = 1 << (intMarker & 0x0F);
    return (_uint(at + 2, size), at + 2 + size);
  }

  Object? _object(int ref, int depth) {
    if (depth > 64) throw const FormatException('plist nested too deeply');
    if (ref >= _offsets.length) throw const FormatException('bad object ref');
    final at = _offsets[ref];
    final marker = b[at];
    final type = marker >> 4;
    final low = marker & 0x0F;
    switch (type) {
      case 0x0:
        if (marker == 0x08) return false;
        if (marker == 0x09) return true;
        return null;
      case 0x1:
        final size = 1 << low;
        if (size == 8) return data.getInt64(at + 1);
        if (size == 16) return data.getInt64(at + 9); // low 64 bits
        return _uint(at + 1, size);
      case 0x2:
        return low == 2 ? data.getFloat32(at + 1) : data.getFloat64(at + 1);
      case 0x3:
        final secs = data.getFloat64(at + 1);
        return DateTime.utc(2001)
            .add(Duration(microseconds: (secs * 1000000).round()));
      case 0x4:
        final (n, s) = _len(at);
        return Uint8List.fromList(b.sublist(s, s + n));
      case 0x5:
        final (n, s) = _len(at);
        return latin1.decode(b.sublist(s, s + n));
      case 0x6:
        final (n, s) = _len(at);
        final units = [for (var i = 0; i < n; i++) data.getUint16(s + i * 2)];
        return String.fromCharCodes(units);
      case 0x8:
        return _uint(at + 1, low + 1);
      case 0xA:
        final (n, s) = _len(at);
        return [
          for (var i = 0; i < n; i++)
            _object(_uint(s + i * _refSize, _refSize), depth + 1),
        ];
      case 0xD:
        final (n, s) = _len(at);
        final out = <String, Object?>{};
        for (var i = 0; i < n; i++) {
          final k = _object(_uint(s + i * _refSize, _refSize), depth + 1);
          final v = _object(_uint(s + (n + i) * _refSize, _refSize), depth + 1);
          out['$k'] = v;
        }
        return out;
      default:
        throw FormatException(
          'unsupported binary plist object 0x${marker.toRadixString(16)}',
        );
    }
  }
}

// ---------------------------------------------------------------- XML ----

final _tag = RegExp(r'<(/?)([A-Za-z]+)([^>]*?)(/?)>');

Object? decodeXmlPlist(String xml) {
  final tokens =
      <(bool close, String name, bool selfClosing, int start, int end)>[];
  var s = xml
      .replaceAll(RegExp(r'<\?xml[^>]*\?>'), '')
      .replaceAll(RegExp(r'<!DOCTYPE[^>]*>'), '')
      .replaceAll(RegExp(r'<!--.*?-->', dotAll: true), '');
  for (final m in _tag.allMatches(s)) {
    tokens.add((
      m.group(1) == '/',
      m.group(2)!,
      m.group(4) == '/',
      m.start,
      m.end,
    ));
  }
  var i = 0;
  String text(int from, int to) => _unescape(s.substring(from, to));

  Object? value() {
    if (i >= tokens.length) {
      throw const FormatException('unexpected end of plist');
    }
    final t = tokens[i++];
    if (t.$1) throw FormatException('unexpected </${t.$2}>');
    switch (t.$2) {
      case 'plist':
        if (t.$3) return null;
        final v = value();
        _expectClose(tokens, i++, 'plist');
        return v;
      case 'dict':
        final out = <String, Object?>{};
        if (t.$3) return out;
        while (true) {
          final n = tokens[i];
          if (n.$1 && n.$2 == 'dict') {
            i++;
            return out;
          }
          if (n.$2 != 'key') {
            throw FormatException('expected <key>, got <${n.$2}>');
          }
          i++;
          String key;
          if (n.$3) {
            key = '';
          } else {
            final c = tokens[i++];
            if (!c.$1 || c.$2 != 'key') {
              throw const FormatException('bad <key>');
            }
            key = text(n.$5, c.$4);
          }
          out[key] = value();
        }
      case 'array':
        final out = <Object?>[];
        if (t.$3) return out;
        while (true) {
          final n = tokens[i];
          if (n.$1 && n.$2 == 'array') {
            i++;
            return out;
          }
          out.add(value());
        }
      case 'true':
        if (!t.$3) _expectClose(tokens, i++, 'true');
        return true;
      case 'false':
        if (!t.$3) _expectClose(tokens, i++, 'false');
        return false;
      case 'string' || 'integer' || 'real' || 'data' || 'date':
        var raw = '';
        if (!t.$3) {
          final c = tokens[i++];
          if (!c.$1 || c.$2 != t.$2) throw FormatException('bad <${t.$2}>');
          raw = text(t.$5, c.$4);
        }
        return switch (t.$2) {
          'string' => raw,
          'integer' => int.parse(raw.trim()),
          'real' => double.parse(raw.trim()),
          'data' => base64.decode(raw.replaceAll(RegExp(r'\s'), '')),
          _ => DateTime.parse(raw.trim()),
        };
      default:
        throw FormatException('unsupported plist element <${t.$2}>');
    }
  }

  return value();
}

void _expectClose(
  List<(bool, String, bool, int, int)> tokens,
  int i,
  String name,
) {
  if (i >= tokens.length || !tokens[i].$1 || tokens[i].$2 != name) {
    throw FormatException('expected </$name>');
  }
}

String _unescape(String s) => s
    .replaceAllMapped(
      RegExp(r'&#(x?)([0-9A-Fa-f]+);'),
      (m) =>
          String.fromCharCode(int.parse(m[2]!, radix: m[1]!.isEmpty ? 10 : 16)),
    )
    .replaceAll('&lt;', '<')
    .replaceAll('&gt;', '>')
    .replaceAll('&quot;', '"')
    .replaceAll('&apos;', "'")
    .replaceAll('&amp;', '&');

String _escape(String s) =>
    s.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;');

/// Deterministic XML plist (dictionary keys in insertion order, tabs like Xcode).
String encodeXmlPlist(Object? root, {String? comment}) {
  final b = StringBuffer()
    ..writeln('<?xml version="1.0" encoding="UTF-8"?>')
    ..writeln(
      '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">',
    );
  if (comment != null) b.writeln('<!-- ${comment.replaceAll('--', '- -')} -->');
  b.writeln('<plist version="1.0">');
  void write(Object? v, int depth) {
    final ind = '\t' * depth;
    switch (v) {
      case Uint8List():
        b.writeln('$ind<data>${base64.encode(v)}</data>');
      case Map():
        if (v.isEmpty) {
          b.writeln('$ind<dict/>');
          return;
        }
        b.writeln('$ind<dict>');
        v.forEach((k, x) {
          b.writeln('$ind\t<key>${_escape('$k')}</key>');
          write(x, depth + 1);
        });
        b.writeln('$ind</dict>');
      case List():
        if (v.isEmpty) {
          b.writeln('$ind<array/>');
          return;
        }
        b.writeln('$ind<array>');
        for (final x in v) {
          write(x, depth + 1);
        }
        b.writeln('$ind</array>');
      case bool():
        b.writeln(v ? '$ind<true/>' : '$ind<false/>');
      case int():
        b.writeln('$ind<integer>$v</integer>');
      case double():
        b.writeln('$ind<real>$v</real>');
      case DateTime():
        b.writeln(
          '$ind<date>${v.toUtc().toIso8601String().split('.').first}Z</date>',
        );
      default:
        b.writeln('$ind<string>${_escape('$v')}</string>');
    }
  }

  write(root, 0);
  b.writeln('</plist>');
  return b.toString();
}
