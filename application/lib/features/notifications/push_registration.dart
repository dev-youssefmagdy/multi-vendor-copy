import 'dart:io' show Platform;
import '../../core/network/api_client.dart';

/// Build-time switch. Push stays off until a Firebase/APNs token source is
/// wired in (`--dart-define=PUSH_ENABLED=true`).
const kPushEnabled = bool.fromEnvironment('PUSH_ENABLED');

/// Supplies the platform push token. The default has no Firebase, so no token.
abstract class PushTokenSource {
  Future<String?> currentToken();
}

class NoPushTokenSource implements PushTokenSource {
  const NoPushTokenSource();
  @override
  Future<String?> currentToken() async => null;
}

/// Registers/unregisters the device token with `POST|DELETE /device-tokens`
/// (requires a signed-in customer). A no-op while the feature flag is off or
/// no token is available.
class PushRegistration {
  PushRegistration(this._api, this._source, {this.enabled = kPushEnabled});
  final ApiClient _api;
  final PushTokenSource _source;
  final bool enabled;

  /// Returns true when a token was sent to the server.
  Future<bool> register() async {
    if (!enabled) return false;
    final token = await _source.currentToken();
    if (token == null || token.isEmpty) return false;
    await _api.request('/device-tokens', method: 'POST', body: {
      'token': token,
      'platform': Platform.isIOS ? 'ios' : 'android',
    });
    return true;
  }

  Future<bool> unregister() async {
    if (!enabled) return false;
    final token = await _source.currentToken();
    if (token == null || token.isEmpty) return false;
    await _api.request('/device-tokens', method: 'DELETE', body: {'token': token});
    return true;
  }
}
