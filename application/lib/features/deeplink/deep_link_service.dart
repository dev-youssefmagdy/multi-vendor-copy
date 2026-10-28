import 'dart:async';
import 'package:app_links/app_links.dart';
import 'url_normalizer.dart';

abstract class DeepLinkSource {
  Future<Uri?> initialLink();
  Stream<Uri> get links;
}

class AppLinksSource implements DeepLinkSource {
  AppLinksSource([AppLinks? a]) : _a = a ?? AppLinks();
  final AppLinks _a;
  @override
  Future<Uri?> initialLink() => _a.getInitialLink();
  @override
  Stream<Uri> get links => _a.uriLinkStream;
}

class FakeDeepLinkSource implements DeepLinkSource {
  FakeDeepLinkSource([this.initial]);
  Uri? initial;
  final _ctl = StreamController<Uri>.broadcast();
  void emit(Uri u) => _ctl.add(u);
  @override
  Future<Uri?> initialLink() async => initial;
  @override
  Stream<Uri> get links => _ctl.stream;
}

class DeepLinkService {
  DeepLinkService(this._source, [this._normalizer = const UrlNormalizer()]);
  final DeepLinkSource _source;
  final UrlNormalizer _normalizer;

  Future<DeepLinkTarget?> initialTarget() async {
    try {
      final u = await _source.initialLink();
      return u == null ? null : _normalizer.parseUri(u);
    } catch (_) {
      return null;
    }
  }

  Stream<DeepLinkTarget> get targets => _source.links
      .map(_normalizer.parseUri)
      .where((t) => t != null)
      .cast<DeepLinkTarget>();

  /// Gateway return links `<scheme>://payment/result?order=..&status=..` that
  /// arrive outside the payment WebView (e.g. external browser). Mapped to the
  /// in-app result route; the screen verifies the status server-side, the
  /// `status` value is only a hint.
  Stream<String> get paymentLocations => _source.links
      .map(paymentLocationFor)
      .where((l) => l != null)
      .cast<String>();

  static String? paymentLocationFor(Uri u) {
    if (u.scheme == 'http' || u.scheme == 'https' || u.scheme.isEmpty) {
      return null;
    }
    if (u.host != 'payment' || u.path != '/result') return null;
    final order = u.queryParameters['order'];
    if (order == null || order.isEmpty) return null;
    final status = u.queryParameters['status'];
    return Uri(path: '/payment/result', queryParameters: {
      'order': order,
      if (status != null && status.isNotEmpty) 'status': status,
    }).toString();
  }
}
