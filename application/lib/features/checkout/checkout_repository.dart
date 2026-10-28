import '../../core/network/api_client.dart';
import '../shop/commerce_models.dart';
import '../shop/shop_repository.dart';

abstract class CheckoutRepository {
  Future<List<Gateway>> gateways();
  Future<CartData> quote({int? addressId, int? countryId});

  /// POST /checkout. Throws [ApiException] with code `price_changed` (409,
  /// `data` = fresh quote) when [expectedTotal] no longer matches.
  Future<PlacedOrder> place(Map<String, dynamic> body,
      {required String idempotencyKey});

  /// POST /orders/{uuid}/pay -> hosted payment URL (null when already paid).
  Future<String?> pay(String uuid);

  /// The only source of truth for payment results.
  Future<PaymentStatus> paymentStatus(String uuid);
}

class RemoteCheckoutRepository implements CheckoutRepository {
  RemoteCheckoutRepository(this._api);
  final ApiClient _api;

  @override
  Future<List<Gateway>> gateways() async {
    final d = asMap(await _api.request('/checkout/gateways'));
    return [for (final g in asList(d['gateways'])) Gateway.fromJson(g)];
  }

  @override
  Future<CartData> quote({int? addressId, int? countryId}) async =>
      CartData.fromJson(asMap(await _api.request('/checkout/quote',
          method: 'POST',
          body: {
            'shipping': {'address_id': ?addressId, 'country_id': ?countryId}
          })));

  @override
  Future<PlacedOrder> place(Map<String, dynamic> body,
      {required String idempotencyKey}) async {
    final d = await _api.request('/checkout',
        method: 'POST',
        body: body,
        headers: {'Idempotency-Key': idempotencyKey});
    return PlacedOrder.fromJson(asMap(d));
  }

  @override
  Future<String?> pay(String uuid) async {
    final d = asMap(await _api.request('/orders/$uuid/pay', method: 'POST'));
    return d['payment_url'] as String?;
  }

  @override
  Future<PaymentStatus> paymentStatus(String uuid) async {
    final d = asMap(await _api.request('/orders/$uuid/payment-status'));
    return PaymentStatus('${d['uuid'] ?? uuid}', d['paid'] == true, d['status'] as String?);
  }
}
