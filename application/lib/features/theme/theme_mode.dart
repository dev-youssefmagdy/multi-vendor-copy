import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../app/providers.dart';
import '../../themes/shared/skin.dart';
import 'theme_registry.dart';

const _kKey = 'device.theme_mode';

/// Light / dark / system choice, stored per device (not per store).
final themeModeProvider = NotifierProvider<ThemeModeNotifier, ThemeMode>(ThemeModeNotifier.new);

class ThemeModeNotifier extends Notifier<ThemeMode> {
  @override
  ThemeMode build() {
    final v = ref.read(sharedPreferencesProvider).getString(_kKey);
    return ThemeMode.values.where((m) => m.name == v).firstOrNull ?? ThemeMode.system;
  }

  Future<void> set(ThemeMode m) async {
    state = m;
    await ref.read(sharedPreferencesProvider).setString(_kKey, m.name);
  }
}

/// True when the UI should render dark for the given mode + platform brightness.
bool useDark(ThemeMode mode, Brightness platform) =>
    mode == ThemeMode.dark || (mode == ThemeMode.system && platform == Brightness.dark);

/// The resolved skin in the right brightness (watches the mode setting).
ThemeSkin effectiveSkin(BuildContext context, WidgetRef ref, ResolvedTheme theme) {
  final mode = ref.watch(themeModeProvider);
  final dark = useDark(mode, MediaQuery.platformBrightnessOf(context));
  return dark ? theme.skin.toDark() : theme.skin;
}
