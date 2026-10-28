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
}
