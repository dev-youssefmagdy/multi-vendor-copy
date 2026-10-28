import '../../core/errors/app_exception.dart';

enum DeepLinkKind { home, product, category, cart, checkout, account }

class DeepLinkTarget {
  const DeepLinkTarget({
    required this.host,
    required this.kind,
    this.id,
    this.query = const {},
  });
  final String host;
  final DeepLinkKind kind;
  final String? id;
  final Map<String, String> query;

  /// In-app go_router location (ids percent-encoded, query preserved).
  String get location {
    final base = switch (kind) {
      DeepLinkKind.home => '/home',
      DeepLinkKind.product => '/product/${Uri.encodeComponent(id ?? '')}',
      DeepLinkKind.category => '/category/${Uri.encodeComponent(id ?? '')}',
      DeepLinkKind.cart => '/cart',
      DeepLinkKind.checkout => '/checkout',
      DeepLinkKind.account => '/account',
    };
    return query.isEmpty
        ? base
        : Uri(path: base, queryParameters: query).toString();
  }

  @override
  bool operator ==(Object other) =>
      other is DeepLinkTarget &&
      other.host == host &&
      other.kind == kind &&
      other.id == id &&
      other.query.toString() == query.toString();
  @override
  int get hashCode => Object.hash(host, kind, id, query.toString());
}

class StoreInput {
  const StoreInput.host(this.value) : isCode = false;
  const StoreInput.code(this.value) : isCode = true;
  final String value;
  final bool isCode;
}

class UrlNormalizer {
  const UrlNormalizer();

  static final _label = RegExp(r'^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$');
  static final _code = RegExp(r'^[A-Za-z0-9_-]{2,64}$');

  /// Typed text -> store host or store code.
  StoreInput classifyInput(String raw) {
    final s = raw.trim();
    if (s.isEmpty) throw const InvalidStoreInputException();
    if (!s.contains('.') && !s.contains('/') && !s.contains(':')) {
      if (_code.hasMatch(s)) return StoreInput.code(s);
      throw const InvalidStoreInputException();
    }
    return StoreInput.host(normalizeHost(s));
  }

  /// Returns a lowercase host with no scheme, port, path, query or trailing dot.
  String normalizeHost(String raw) {
    var s = raw.trim();
    if (s.isEmpty) throw const InvalidStoreInputException();
    if (!s.contains('://')) s = 'https://$s';
    final uri = Uri.tryParse(s);
    if (uri == null || uri.host.isEmpty) {
      throw const InvalidStoreInputException();
    }
    var host = uri.host.toLowerCase();
    while (host.endsWith('.')) {
      host = host.substring(0, host.length - 1);
    }
    final labels = host.split('.');
    if (labels.length < 2 || !labels.every(_label.hasMatch)) {
      throw const InvalidStoreInputException();
    }
    return host;
  }

  /// Parses an incoming link. Returns null when no valid store host exists.
  /// A `?store=<host>` query (custom-domain landing handoff) wins over the
  /// URL host. `api_base`-style parameters are ignored by design.
  DeepLinkTarget? parseUri(Uri uri) {
    // Gateway return link `<scheme>://payment/result` is handled by the
    // payment WebView, never as a store switch.
    if (uri.scheme != 'http' && uri.scheme != 'https' && uri.host == 'payment') {
      return null;
    }
    final storeParam = uri.queryParameters['store'];
    String host;
    try {
      host = normalizeHost(
          storeParam != null && storeParam.isNotEmpty ? storeParam : uri.host);
    } on InvalidStoreInputException {
      return null;
    }
    final segs = uri.pathSegments.where((s) => s.isNotEmpty).toList();
    final query = Map<String, String>.from(uri.queryParameters)
      ..remove('store');
    DeepLinkTarget t(DeepLinkKind k, [String? id]) =>
        DeepLinkTarget(host: host, kind: k, id: id, query: query);
    if (segs.isEmpty) return t(DeepLinkKind.home);
    final head = segs.first.toLowerCase();
    final id = segs.length > 1 ? segs[1] : null;
    switch (head) {
      case 'product':
      case 'products':
        return id == null ? t(DeepLinkKind.home) : t(DeepLinkKind.product, id);
      case 'category':
      case 'categories':
        return id == null ? t(DeepLinkKind.home) : t(DeepLinkKind.category, id);
      case 'cart':
        return t(DeepLinkKind.cart);
      case 'checkout':
        return t(DeepLinkKind.checkout);
      case 'account':
      case 'profile':
        return t(DeepLinkKind.account);
      default:
        return t(DeepLinkKind.home);
    }
  }
}
