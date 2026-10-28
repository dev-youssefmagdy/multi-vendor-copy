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
  const ApiException(super.message, {this.statusCode, this.errors});
  final int? statusCode;
  final Map<String, dynamic>? errors;
}

class UnauthorizedException extends AppException {
  const UnauthorizedException([super.message = 'Unauthorized']);
}

class InvalidStoreInputException extends AppException {
  const InvalidStoreInputException([super.message = 'Invalid store address']);
}
