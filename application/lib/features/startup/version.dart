/// Compares dotted numeric versions ("1.2.10" vs "1.3"); <0, 0, >0.
int compareVersions(String a, String b) {
  List<int> p(String v) => [
        for (final x in v.split('+').first.split('.'))
          int.tryParse(x.replaceAll(RegExp(r'[^0-9]'), '')) ?? 0
      ];
  final x = p(a), y = p(b);
  for (var i = 0; i < (x.length > y.length ? x.length : y.length); i++) {
    final d = (i < x.length ? x[i] : 0) - (i < y.length ? y[i] : 0);
    if (d != 0) return d;
  }
  return 0;
}
