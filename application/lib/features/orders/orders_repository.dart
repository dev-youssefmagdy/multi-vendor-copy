import 'package:dio/dio.dart';
import '../../core/network/api_client.dart';
import '../shop/commerce_models.dart';
import '../shop/shop_repository.dart';

class ReturnFile {
  const ReturnFile(this.path, this.name);
  final String path, name;
}

abstract class OrdersRepository {
  Future<List<OrderSummary>> orders({String? status});
  Future<OrderDetail> order(String uuid);
  Future<OrderDetail> cancel(String uuid, {required String reason, String? note});
  Future<List<Map<String, dynamic>>> cancellationReasons();
  Future<int> reorder(String uuid);
  Future<Tracking> tracking(String uuid);
  Future<String> invoiceHtml(String uuid);
  Future<Map<String, dynamic>> returnOptions();
  Future<List<Map<String, dynamic>>> returns();
  Future<Map<String, dynamic>> returnDetail(int id);
  Future<Map<String, dynamic>> createReturn(String uuid, Map<String, dynamic> fields,
      List<ReturnFile> photos);
}

class RemoteOrdersRepository implements OrdersRepository {
  RemoteOrdersRepository(this._api);
  final ApiClient _api;

  @override
  Future<List<OrderSummary>> orders({String? status}) async {
    final d = asMap(await _api.request('/orders',
        query: {if (status != null && status.isNotEmpty) 'status': status}));
    return [for (final o in asList(d['data'])) OrderSummary.fromJson(o)];
  }

  @override
  Future<OrderDetail> order(String uuid) async =>
      OrderDetail.fromJson(asMap(asMap(await _api.request('/orders/$uuid'))['order']));

  @override
  Future<OrderDetail> cancel(String uuid, {required String reason, String? note}) async =>
      OrderDetail.fromJson(asMap(asMap(await _api.request('/orders/$uuid/cancel',
          method: 'POST',
          body: {'reason': reason, if (note != null && note.isNotEmpty) 'note': note}))['order']));

  @override
  Future<List<Map<String, dynamic>>> cancellationReasons() async =>
      asList(asMap(await _api.request('/orders/cancellation-reasons'))['data']);

  @override
  Future<int> reorder(String uuid) async {
    final d = asMap(await _api.request('/orders/$uuid/reorder', method: 'POST'));
    return (d['cart_count'] as num?)?.toInt() ?? 0;
  }

  @override
  Future<Tracking> tracking(String uuid) async =>
      Tracking.fromJson(asMap(await _api.request('/orders/$uuid/tracking')));

  @override
  Future<String> invoiceHtml(String uuid) => _api.requestText('/orders/$uuid/invoice');

  @override
  Future<Map<String, dynamic>> returnOptions() async =>
      asMap(await _api.request('/returns/options'));

  @override
  Future<List<Map<String, dynamic>>> returns() async =>
      asList(await _api.request('/returns'));

  @override
  Future<Map<String, dynamic>> returnDetail(int id) async =>
      asMap(await _api.request('/returns/$id'));

  @override
  Future<Map<String, dynamic>> createReturn(
      String uuid, Map<String, dynamic> fields, List<ReturnFile> photos) async {
    final form = FormData.fromMap({
      for (final e in fields.entries) if (e.value != null) e.key: '${e.value}',
    });
    for (final p in photos) {
      form.files.add(MapEntry(
          'photos[]', await MultipartFile.fromFile(p.path, filename: p.name)));
    }
    return asMap(
        await _api.request('/orders/$uuid/return', method: 'POST', body: form));
  }
}
