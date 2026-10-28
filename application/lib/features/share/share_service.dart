import 'package:share_plus/share_plus.dart';

abstract class ShareService {
  Future<void> shareText(String text, {String? subject});
}

class SharePlusService implements ShareService {
  const SharePlusService();
  @override
  Future<void> shareText(String text, {String? subject}) async {
    await SharePlus.instance.share(ShareParams(text: text, subject: subject));
  }
}
