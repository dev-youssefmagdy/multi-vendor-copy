import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/errors/app_exception.dart';
import '../../core/l10n/strings.dart';
import '../../core/platform/web_surface.dart';
import '../../themes/shared/skin.dart';
import '../shop/commerce_models.dart';
import '../shop/commerce_providers.dart';
import '../shop/widgets/shop_widgets.dart';

/// Polling cadence for `GET /orders/{uuid}/payment-status` (overridable in tests).
class PaymentPollConfig {
  const PaymentPollConfig({
    this.interval = const Duration(seconds: 3),
    this.maxAttempts = 10,
  });
  final Duration interval;
  final int maxAttempts;
}

final paymentPollConfigProvider = Provider((_) => const PaymentPollConfig());

/// True for the gateway-return deep link `<scheme>://payment/result?...`.
bool isPaymentReturnLink(String url) {
  final u = Uri.tryParse(url);
  if (u == null ||
      u.scheme == 'http' ||
      u.scheme == 'https' ||
      u.scheme.isEmpty) {
    return false;
  }
  return u.host == 'payment' && u.path == '/result';
}

/// Opens the hosted payment page in a WebView. Whatever the WebView reports
/// (success/cancel redirect), the outcome is only decided by the server via
/// [PaymentResultScreen]'s polling.
class PaymentScreen extends ConsumerStatefulWidget {
  const PaymentScreen(this.uuid, {super.key});
  final String uuid;
  @override
  ConsumerState<PaymentScreen> createState() => _PaymentScreenState();
}

class _PaymentScreenState extends ConsumerState<PaymentScreen> {
  String? _url;
  Object? _error;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _init();
  }

  Future<void> _init() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final url = await ref.read(checkoutRepositoryProvider).pay(widget.uuid);
      if (!mounted) return;
      if (url == null) return _toResult();
      setState(() {
        _url = url;
        _loading = false;
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      // "already paid": let the server status decide on the result screen.
      if (e.code == 'already_paid') return _toResult();
      setState(() {
        _error = e;
        _loading = false;
      });
    } on AppException catch (e) {
      if (mounted) {
        setState(() {
          _error = e;
          _loading = false;
        });
      }
    }
  }

  void _toResult() =>
      openRoute(context, '/payment/result?order=${widget.uuid}');

  @override
  Widget build(BuildContext context) {
    final web = ref.watch(webSurfaceBuilderProvider);
    return ShopScaffold(
      title: context.tr('Payment'),
      actions: [
        TextButton(
          key: const Key('payment_done'),
          onPressed: _toResult,
          child: Text(context.tr('Done')),
        ),
      ],
      body: _loading
          ? const LoadingView()
          : _error != null
          ? ErrorView(_error!, onRetry: _init)
          : web(
              context,
              url: _url,
              intercept: (u) {
                if (isPaymentReturnLink(u)) {
                  WidgetsBinding.instance.addPostFrameCallback((_) {
                    if (mounted) _toResult();
                  });
                  return true;
                }
                return false;
              },
            ),
    );
  }
}

enum PaymentOutcome { checking, paid, pending, failed }

class PaymentResultScreen extends ConsumerStatefulWidget {
  const PaymentResultScreen(this.uuid, {super.key, this.hint});
  final String uuid;

  /// `status` query of the deep link: a hint only, never trusted.
  final String? hint;
  @override
  ConsumerState<PaymentResultScreen> createState() => _PaymentResultState();
}

class _PaymentResultState extends ConsumerState<PaymentResultScreen> {
  PaymentOutcome _outcome = PaymentOutcome.checking;
  Timer? _timer;
  int _attempts = 0;
  String? _error;

  @override
  void initState() {
    super.initState();
    _check();
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  Future<void> _check() async {
    _timer?.cancel();
    final cfg = ref.read(paymentPollConfigProvider);
    setState(() {
      _outcome = PaymentOutcome.checking;
      _error = null;
    });
    PaymentStatus? st;
    try {
      st = await ref
          .read(checkoutRepositoryProvider)
          .paymentStatus(widget.uuid);
    } on AppException catch (e) {
      _error = e.message;
    }
    if (!mounted) return;
    _attempts++;
    if (st != null && st.paid) {
      ref.invalidate(cartProvider);
      ref.invalidate(orderProvider(widget.uuid));
      setState(() => _outcome = PaymentOutcome.paid);
      return;
    }
    final hintedFail = const {
      'failed',
      'cancelled',
      'canceled',
    }.contains(widget.hint);
    if (st != null && (st.closed || hintedFail)) {
      setState(() => _outcome = PaymentOutcome.failed);
      return;
    }
    if (_attempts >= cfg.maxAttempts) {
      setState(() => _outcome = PaymentOutcome.pending);
      return;
    }
    setState(() => _outcome = PaymentOutcome.checking);
    _timer = Timer(cfg.interval, _check);
  }

  @override
  Widget build(BuildContext context) => ShopScaffold(
    title: context.tr('Payment'),
    body: Builder(builder: _content),
  );

  Widget _content(BuildContext context) {
    final s = SkinScope.of(context);
    final (icon, color, title, key) = switch (_outcome) {
      PaymentOutcome.paid => (
        Icons.check_circle,
        s.success,
        context.tr('Payment successful'),
        'payment_paid',
      ),
      PaymentOutcome.failed => (
        Icons.cancel,
        s.error,
        context.tr('Payment failed'),
        'payment_failed',
      ),
      PaymentOutcome.pending => (
        Icons.hourglass_bottom,
        s.subtitle,
        context.tr('Payment pending'),
        'payment_pending',
      ),
      PaymentOutcome.checking => (
        Icons.hourglass_empty,
        s.subtitle,
        context.tr('Confirming your payment...'),
        'payment_checking',
      ),
    };
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (_outcome == PaymentOutcome.checking)
              const CircularProgressIndicator()
            else
              Icon(icon, size: 64, color: color),
            const SizedBox(height: 16),
            Text(
              title,
              key: Key(key),
              style: TextStyle(
                color: s.text,
                fontSize: 20,
                fontWeight: FontWeight.w800,
              ),
            ),
            if (_outcome == PaymentOutcome.pending)
              Padding(
                padding: const EdgeInsets.only(top: 8),
                child: Text(
                  context.tr(
                    'We have not received confirmation yet. This can take a few minutes.',
                  ),
                  textAlign: TextAlign.center,
                ),
              ),
            if (_error != null && _outcome != PaymentOutcome.paid)
              Padding(
                padding: const EdgeInsets.only(top: 8),
                child: Text(_error!),
              ),
            const SizedBox(height: 24),
            if (_outcome == PaymentOutcome.pending)
              SkinButton(
                key: const Key('payment_recheck'),
                label: context.tr('Check again'),
                onPressed: () {
                  _attempts = 0;
                  _check();
                },
              ),
            if (_outcome == PaymentOutcome.failed) ...[
              SkinButton(
                key: const Key('payment_retry'),
                label: context.tr('Try payment again'),
                onPressed: () => openRoute(context, '/payment/${widget.uuid}'),
              ),
              const SizedBox(height: 8),
            ],
            if (_outcome != PaymentOutcome.checking)
              SkinButton(
                key: const Key('view_order'),
                outline: _outcome == PaymentOutcome.failed,
                label: context.tr('View order'),
                onPressed: () => openRoute(context, '/orders/${widget.uuid}'),
              ),
          ],
        ),
      ),
    );
  }
}
