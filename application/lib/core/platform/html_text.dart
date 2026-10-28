/// Minimal HTML -> plain text (CMS pages / product descriptions) without a
/// rendering dependency. Block tags become line breaks; entities are decoded.
String htmlToText(String? html) {
  if (html == null || html.isEmpty) return '';
  var s = html
      .replaceAll(RegExp(r'<(script|style)[^>]*>.*?</\1>', dotAll: true, caseSensitive: false), '')
      .replaceAll(RegExp(r'<br\s*/?>', caseSensitive: false), '\n')
      .replaceAll(RegExp(r'</(p|div|h[1-6]|li|tr)>', caseSensitive: false), '\n')
      .replaceAll(RegExp(r'<li[^>]*>', caseSensitive: false), '• ')
      .replaceAll(RegExp(r'<[^>]+>'), '');
  const ent = {
    '&nbsp;': ' ',
    '&amp;': '&',
    '&lt;': '<',
    '&gt;': '>',
    '&quot;': '"',
    '&#39;': "'",
    '&apos;': "'",
  };
  ent.forEach((k, v) => s = s.replaceAll(k, v));
  return s.replaceAll(RegExp(r'[ \t]+\n'), '\n').replaceAll(RegExp(r'\n{3,}'), '\n\n').trim();
}
