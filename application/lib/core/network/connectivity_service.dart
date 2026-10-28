import 'dart:async';
import 'package:connectivity_plus/connectivity_plus.dart';

abstract class ConnectivityService {
  Future<bool> isOnline();
  Stream<bool> get onChanged;
}

class PlusConnectivityService implements ConnectivityService {
  PlusConnectivityService([Connectivity? c]) : _c = c ?? Connectivity();
  final Connectivity _c;
  static bool _ok(List<ConnectivityResult> r) =>
      r.any((e) => e != ConnectivityResult.none);
  @override
  Future<bool> isOnline() async => _ok(await _c.checkConnectivity());
  @override
  Stream<bool> get onChanged => _c.onConnectivityChanged.map(_ok);
}

class FakeConnectivityService implements ConnectivityService {
  FakeConnectivityService([this.online = true]);
  bool online;
  final _ctl = StreamController<bool>.broadcast();
  void set(bool v) {
    online = v;
    _ctl.add(v);
  }

  @override
  Future<bool> isOnline() async => online;
  @override
  Stream<bool> get onChanged => _ctl.stream;
}
