/// Base class for expected, user-presentable failures.
sealed class AppException implements Exception {
  const AppException(this.message);
  final String message;
  @override
  String toString() => '$runtimeType: $message';
}

/// No connectivity / timeout / DNS failure.
class NetworkException extends AppException {
  const NetworkException([super.message = 'Network unavailable']);
}

/// Server answered with a non-success status.
class ApiException extends AppException {
  const ApiException(super.message,
      {this.statusCode, this.errors, this.code, this.data});
  final int? statusCode;
  final Map<String, dynamic>? errors;

  /// Machine readable envelope `code` (e.g. `price_changed`, `cart_empty`).
  final String? code;

  /// Envelope `data` of an error response (e.g. the fresh quote on 409).
  final Object? data;
}

class UnauthorizedException extends AppException {
  const UnauthorizedException([super.message = 'Unauthorized']);
}

class InvalidStoreInputException extends AppException {
  const InvalidStoreInputException([super.message = 'Invalid store address']);
}
