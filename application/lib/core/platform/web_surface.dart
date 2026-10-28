import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:webview_flutter/webview_flutter.dart';

/// Decides whether a navigation inside the WebView must be intercepted
/// (returns true to BLOCK it, e.g. the app deep link back from the gateway).
typedef NavigationInterceptor = bool Function(String url);

typedef WebSurfaceBuilder = Widget Function(BuildContext context,
    {String? url, String? html, NavigationInterceptor? intercept});

/// Overridable in tests (no platform WebView available in widget tests).
final webSurfaceBuilderProvider =
    Provider<WebSurfaceBuilder>((_) => _realWebSurface);

Widget _realWebSurface(BuildContext context,
    {String? url, String? html, NavigationInterceptor? intercept}) {
  return _RealWebView(url: url, html: html, intercept: intercept);
}

class _RealWebView extends StatefulWidget {
  const _RealWebView({this.url, this.html, this.intercept});
  final String? url, html;
  final NavigationInterceptor? intercept;
  @override
  State<_RealWebView> createState() => _RealWebViewState();
}

class _RealWebViewState extends State<_RealWebView> {
  late final WebViewController _c;
  @override
  void initState() {
    super.initState();
    _c = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(NavigationDelegate(onNavigationRequest: (r) {
        final block = widget.intercept?.call(r.url) ?? false;
        return block ? NavigationDecision.prevent : NavigationDecision.navigate;
      }));
    if (widget.html != null) {
      _c.loadHtmlString(widget.html!);
    } else if (widget.url != null) {
      _c.loadRequest(Uri.parse(widget.url!));
    }
  }

  @override
  Widget build(BuildContext context) => WebViewWidget(controller: _c);
}
