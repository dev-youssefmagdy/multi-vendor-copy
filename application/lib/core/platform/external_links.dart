import 'package:url_launcher/url_launcher.dart';

/// Opens a URL outside the app (browser or the app that owns the link).
abstract class ExternalLinkOpener {
  Future<bool> open(Uri uri);
}

class UrlLauncherOpener implements ExternalLinkOpener {
  const UrlLauncherOpener();
  @override
  Future<bool> open(Uri uri) async {
    if (uri.scheme != 'https') return false;
    try {
      return await launchUrl(uri, mode: LaunchMode.externalApplication);
    } catch (_) {
      return false;
    }
  }
}

/// Test double: records what would have been opened.
class RecordingLinkOpener implements ExternalLinkOpener {
  final opened = <Uri>[];
  @override
  Future<bool> open(Uri uri) async {
    opened.add(uri);
    return true;
  }
}
