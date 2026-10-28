import 'dart:async';
import 'package:dio/dio.dart';
import '../config/env.dart';
import '../errors/app_exception.dart';
import '../logger/app_logger.dart';

/// Identity of the currently active store; set by the tenant controller.
class TenantContext {
  const TenantContext({required this.tenantId, required this.host});
  final String tenantId;
  final String host;
}

typedef TokenReader = Future<String?> Function(String tenantId);
typedef RefreshCallback = Future<bool> Function(String tenantId);
typedef UnauthorizedCallback = Future<void> Function(String tenantId);
typedef DelayFn = Future<void> Function(Duration d);

/// Holds mutable per-session state read by interceptors.
class ApiSession {
  TenantContext? tenant;
  String locale = 'en';
  String? currency;
  CancelToken _cancel = CancelToken();
  CancelToken get cancelToken => _cancel;

  /// Cancels all in-flight requests (tenant switch) and arms a fresh token.
  void cancelAll() {
    if (!_cancel.isCancelled) _cancel.cancel('tenant switch');
    _cancel = CancelToken();
  }
}

class HeaderInterceptor extends Interceptor {
  HeaderInterceptor(this.config, this.session, this.readToken);
  final AppConfig config;
  final ApiSession session;
  final TokenReader readToken;

  @override
  Future<void> onRequest(
      RequestOptions options, RequestInterceptorHandler handler) async {
    final o = options;
    final h = handler;
    o.headers['Accept'] = 'application/json';
    o.headers['X-App-Version'] = config.appVersion;
    o.headers['X-Platform'] = config.platform;
    o.headers['Accept-Language'] = session.locale;
    if (session.currency != null) o.headers['X-Currency'] = session.currency;
    o.cancelToken ??= session.cancelToken;
    final t = session.tenant;
    if (t != null && o.extra['no_tenant'] != true) {
      o.headers['X-Store'] = t.host;
      o.headers['X-Tenant'] = t.tenantId;
      final token = await readToken(t.tenantId);
      if (token != null && token.isNotEmpty) {
        o.headers['Authorization'] = 'Bearer $token';
      }
    }
    h.next(o);
  }
}

class RetryInterceptor extends Interceptor {
  RetryInterceptor(this.dio, this.maxRetries, {DelayFn? delay})
      : _delay = delay ?? ((d) => Future<void>.delayed(d));
  final Dio dio;
  final int maxRetries;
  final DelayFn _delay;

  bool _retriable(DioException e) {
    if (e.type == DioExceptionType.cancel) return false;
    final m = e.requestOptions.method.toUpperCase();
    if (m != 'GET' && m != 'HEAD') return false;
    final s = e.response?.statusCode;
    return s == null
        ? e.type != DioExceptionType.badCertificate
        : (s >= 500 || s == 429);
  }

  @override
  Future<void> onError(DioException err, ErrorInterceptorHandler handler) async {
    final e = err;
    final h = handler;
    final attempt = (e.requestOptions.extra['retry_attempt'] as int?) ?? 0;
    if (!_retriable(e) || attempt >= maxRetries) return h.next(e);
    await _delay(Duration(milliseconds: 300 * (1 << attempt)));
    final o = e.requestOptions..extra['retry_attempt'] = attempt + 1;
    try {
      h.resolve(await dio.fetch<dynamic>(o));
    } on DioException catch (e2) {
      h.next(e2);
    }
  }
}

/// On 401: refresh once (single-flight), replay; otherwise signal logout.
class AuthInterceptor extends Interceptor {
  AuthInterceptor(this.dio, this.session,
      {required this.refresh, required this.onUnauthorized});
  final Dio dio;
  final ApiSession session;
  final RefreshCallback refresh;
  final UnauthorizedCallback onUnauthorized;
  Future<bool>? _inFlight;

  @override
  Future<void> onError(DioException err, ErrorInterceptorHandler handler) async {
    final e = err;
    final h = handler;
    final o = e.requestOptions;
    final tenant = session.tenant;
    final hadAuth = o.headers['Authorization'] != null;
    if (e.response?.statusCode != 401 || !hadAuth || tenant == null) {
      return h.next(e);
    }
    if (o.extra['auth_retried'] == true || o.path.contains('/auth/')) {
      await onUnauthorized(tenant.tenantId);
      return h.next(e);
    }
    _inFlight ??= refresh(tenant.tenantId).whenComplete(() => _inFlight = null);
    final ok = await _inFlight!;
    if (!ok) {
      await onUnauthorized(tenant.tenantId);
      return h.next(e);
    }
    o.extra['auth_retried'] = true;
    try {
      h.resolve(await dio.fetch<dynamic>(o));
    } on DioException catch (e2) {
      h.next(e2);
    }
  }
}

class ApiClient {
  ApiClient({
    required this.config,
    required this.session,
    required TokenReader readToken,
    required RefreshCallback refresh,
    required UnauthorizedCallback onUnauthorized,
    AppLogger? logger,
    DelayFn? retryDelay,
    HttpClientAdapter? adapter,
  }) : dio = Dio(BaseOptions(
          baseUrl: config.apiRoot,
          connectTimeout: config.connectTimeout,
          receiveTimeout: config.receiveTimeout,
          sendTimeout: config.receiveTimeout,
        )) {
    if (adapter != null) dio.httpClientAdapter = adapter;
    dio.interceptors.addAll([
      HeaderInterceptor(config, session, readToken),
      AuthInterceptor(dio, session,
          refresh: refresh, onUnauthorized: onUnauthorized),
      RetryInterceptor(dio, config.maxRetries, delay: retryDelay),
      if (logger != null)
        InterceptorsWrapper(onError: (e, h) {
          logger.w('${e.requestOptions.method} ${e.requestOptions.path} '
              '-> ${e.response?.statusCode ?? e.type.name}');
          h.next(e);
        }),
    ]);
  }

  final AppConfig config;
  final ApiSession session;
  final Dio dio;

  void cancelAll() => session.cancelAll();

  /// Performs a request and returns the envelope `data` (or whole body).
  Future<dynamic> request(String path,
      {String method = 'GET',
      Map<String, dynamic>? query,
      Object? body,
      bool noTenant = false,
      Map<String, dynamic>? headers}) async {
    try {
      final r = await dio.request<dynamic>(path,
          data: body,
          queryParameters: query,
          options: Options(
              method: method,
              headers: headers,
              extra: {if (noTenant) 'no_tenant': true}));
      return unwrap(r.data);
    } on DioException catch (e) {
      throw mapError(e);
    }
  }

  static dynamic unwrap(dynamic body) {
    if (body is Map && body.containsKey('success')) {
      if (body['success'] == false) {
        throw ApiException((body['message'] ?? 'Request failed').toString());
      }
      return body.containsKey('data') ? body['data'] : body;
    }
    return body;
  }

  static AppException mapError(DioException e) {
    final s = e.response?.statusCode;
    if (s == null) {
      return e.type == DioExceptionType.cancel
          ? const NetworkException('Request cancelled')
          : const NetworkException();
    }
    if (s == 401) return const UnauthorizedException();
    final d = e.response?.data;
    return ApiException(
      d is Map && d['message'] != null ? d['message'].toString() : 'HTTP $s',
      statusCode: s,
      errors: d is Map && d['errors'] is Map
          ? Map<String, dynamic>.from(d['errors'] as Map)
          : null,
    );
  }
}
