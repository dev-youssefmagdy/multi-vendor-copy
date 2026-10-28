import 'package:flutter/foundation.dart';

typedef LogSink = void Function(String line);

/// Minimal logger that redacts secrets before anything is emitted.
class AppLogger {
  AppLogger({LogSink? sink}) : _sink = sink ?? ((l) => debugPrint(l));
  final LogSink _sink;

  static final _patterns = <RegExp, String>{
    RegExp(r'(Bearer\s+)[A-Za-z0-9\-_\.~\+/=]+', caseSensitive: false):
        r'$1***',
    RegExp(
      r'''("?(?:password|token|access_token|refresh_token|authorization|secret)"?\s*[:=]\s*)("[^"]*"|[^\s,&}]+)''',
      caseSensitive: false,
    ): r'$1***',
  };

  static String redact(String input) {
    var out = input;
    _patterns.forEach((re, rep) => out = out.replaceAllMapped(re, (m) {
          return rep.replaceAll(r'$1', m.group(1) ?? '');
        }));
    return out;
  }

  void d(String m) => _sink('[D] ${redact(m)}');
  void i(String m) => _sink('[I] ${redact(m)}');
  void w(String m) => _sink('[W] ${redact(m)}');
  void e(String m, [Object? error]) =>
      _sink('[E] ${redact(error == null ? m : '$m: $error')}');
}
