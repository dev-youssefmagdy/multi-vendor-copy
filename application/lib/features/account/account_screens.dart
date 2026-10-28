import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../app/providers.dart';
import '../../core/errors/app_exception.dart';
import '../../core/l10n/strings.dart';
import '../theme/theme_mode.dart';
import '../../design/design.dart';
import '../../themes/shared/chrome.dart';
import '../../themes/shared/skin.dart';
import '../shop/commerce_models.dart';
import '../shop/commerce_providers.dart';
import '../shop/screens/listing_screen.dart';
import '../shop/screens/misc_screens.dart';
import '../shop/widgets/shop_widgets.dart';

/// Account hub (bottom-nav tab): works signed-out (sign-in prompt, language,
/// CMS pages) and signed-in (profile, orders, returns, addresses, ...).
class AccountScreen extends ConsumerWidget {
  const AccountScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authSessionProvider);
    return ShopScaffold(
      title: context.tr('Account'),
      navIndex: 4,
      body: auth.when(
        loading: () => const LoadingView(),
        error: (e, _) => ErrorView(e, onRetry: () => ref.invalidate(authSessionProvider)),
        data: (ok) => ListView(children: [
          if (!ok) ...[
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(children: [
                SkinButton(
                    key: const Key('account_sign_in'),
                    label: context.tr('Sign in'),
                    onPressed: () => openRoute(context, '/login')),
                const SizedBox(height: 8),
                SkinButton(
                    key: const Key('account_register'),
                    outline: true,
                    label: context.tr('Create account'),
                    onPressed: () => openRoute(context, '/register')),
              ]),
            ),
          ] else ...[
            _Header(),
            _tile(context, Icons.receipt_long_outlined, 'My orders', '/orders'),
            _tile(context, Icons.assignment_return_outlined, 'Returns', '/returns'),
            _tile(context, Icons.favorite_border, 'Favorites', '/favorites'),
            _tile(context, Icons.location_on_outlined, 'Addresses', '/account/addresses'),
            _tile(context, Icons.person_outline, 'Profile', '/account/profile'),
          ],
          const Divider(),
          _tile(context, Icons.local_fire_department_outlined, 'Best selling', '/list/best-selling'),
          _tile(context, Icons.new_releases_outlined, 'New in', '/list/new-in'),
          _tile(context, Icons.star_outline, 'Top rated', '/list/full-star'),
          _tile(context, Icons.percent, 'Offers', '/list/offers'),
          const Divider(),
          _tile(context, Icons.language, 'Preferences', '/account/preferences'),
          const _ThemeModeTile(),
          const PagesListSection(),
          const _AboutTile(),
          if (ok) ...[
            const Divider(),
            ListTile(
                key: const Key('sign_out'),
                leading: const Icon(Icons.logout),
                title: Text(context.tr('Sign out')),
                onTap: () async {
                  await ref.read(authActionsProvider).logout();
                }),
          ],
        ]),
      ),
    );
  }

  Widget _tile(BuildContext c, IconData i, String t, String route) => ListTile(
      key: Key('acct_$route'),
      leading: Icon(i),
      title: Text(c.tr(t)),
      trailing: const Icon(Icons.chevron_right),
      onTap: () => openRoute(c, route));
}

class _Header extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = SkinScope.of(context);
    final p = ref.watch(profileProvider).value;
    return Container(
      padding: const EdgeInsets.all(16),
      child: Row(children: [
        CircleAvatar(
            backgroundColor: s.primary,
            child: Text(((p?['full_name'] as String?) ?? '?').characters.firstOrNull?.toUpperCase() ?? '?',
                style: const TextStyle(color: Colors.white))),
        const SizedBox(width: 12),
        Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('${p?['full_name'] ?? ''}', style: const TextStyle(fontWeight: FontWeight.w800)),
          Text('${p?['email'] ?? ''}', style: TextStyle(color: s.subtitle)),
        ])),
      ]),
    );
  }
}

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});
  @override
  Widget build(BuildContext context) => ShopScaffold(
      title: context.tr('Profile'),
      body: const RequireAuth(from: '/account/profile', child: _ProfileForm()));
}

class _ProfileForm extends ConsumerStatefulWidget {
  const _ProfileForm();
  @override
  ConsumerState<_ProfileForm> createState() => _ProfileFormState();
}

class _ProfileFormState extends ConsumerState<_ProfileForm> {
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _phone = TextEditingController();
  final _cur = TextEditingController();
  final _new = TextEditingController();
  final _conf = TextEditingController();
  bool _loaded = false, _busy = false;
  String? _msg;
  Map<String, String> _errors = {};

  @override
  void dispose() {
    for (final c in [_name, _email, _phone, _cur, _new, _conf]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _run(Future<void> Function() op, String ok) async {
    setState(() {
      _busy = true;
      _msg = null;
      _errors = {};
    });
    try {
      await op();
      _msg = ok;
    } on ApiException catch (e) {
      _msg = e.message;
      _errors = {
        for (final en in (e.errors ?? const {}).entries)
          en.key: en.value is List ? '${(en.value as List).firstOrNull}' : '${en.value}'
      };
    } on AppException catch (e) {
      _msg = e.message;
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final async = ref.watch(profileProvider);
    return AsyncBody<Map<String, dynamic>>(async,
        onRetry: () => ref.invalidate(profileProvider), data: (p) {
      if (!_loaded) {
        _loaded = true;
        _name.text = '${p['full_name'] ?? ''}';
        _email.text = '${p['email'] ?? ''}';
        _phone.text = '${p['phone'] ?? ''}';
      }
      return ListView(padding: const EdgeInsets.all(16), children: [
        AppTextField(fieldKey: const Key('p_name'), controller: _name, label: context.tr('Name'), errorText: _errors['full_name']),
        const SizedBox(height: 12),
        AppTextField(fieldKey: const Key('p_email'), controller: _email, label: context.tr('Email'), errorText: _errors['email']),
        const SizedBox(height: 12),
        AppTextField(fieldKey: const Key('p_phone'), controller: _phone, label: context.tr('Phone'), errorText: _errors['phone']),
        const SizedBox(height: 12),
        SkinButton(
            key: const Key('p_save'),
            label: context.tr('Save'),
            busy: _busy,
            onPressed: () => _run(() async {
                  await ref.read(accountRepositoryProvider).updateProfile({
                    'full_name': _name.text.trim(),
                    'email': _email.text.trim(),
                    'phone': _phone.text.trim(),
                  });
                  ref.invalidate(profileProvider);
                }, context.tr('Saved'))),
        if (_msg != null)
          Padding(padding: const EdgeInsets.only(top: 8), child: Text(_msg!, key: const Key('p_msg'))),
        const Divider(height: 32),
        Text(context.tr('Change password'), style: const TextStyle(fontWeight: FontWeight.w800)),
        const SizedBox(height: 8),
        AppTextField(fieldKey: const Key('pw_current'), controller: _cur, password: true, label: context.tr('Current password')),
        const SizedBox(height: 12),
        AppTextField(fieldKey: const Key('pw_new'), controller: _new, password: true, label: context.tr('Password'), errorText: _errors['password']),
        const SizedBox(height: 12),
        AppTextField(fieldKey: const Key('pw_confirm'), controller: _conf, password: true, label: context.tr('Confirm password')),
        const SizedBox(height: 12),
        SkinButton(
            key: const Key('pw_save'),
            outline: true,
            label: context.tr('Change password'),
            busy: _busy,
            onPressed: () => _run(
                () => ref.read(accountRepositoryProvider).changePassword(_cur.text, _new.text, _conf.text),
                context.tr('Password updated'))),
        const Divider(height: 32),
        TextButton(
            key: const Key('delete_account'),
            onPressed: _deleteAccount,
            child: Text(context.tr('Delete my account'),
                style: TextStyle(color: Theme.of(context).colorScheme.error))),
      ]);
    });
  }

  Future<void> _deleteAccount() async {
    final ctl = TextEditingController();
    final ok = await showDialog<bool>(
        context: context,
        builder: (ctx) => AlertDialog(
              title: Text(context.tr('Delete my account')),
              content: TextField(controller: ctl, obscureText: true, decoration: InputDecoration(labelText: context.tr('Password'))),
              actions: [
                TextButton(onPressed: () => Navigator.pop(ctx, false), child: Text(context.tr('Cancel'))),
                TextButton(onPressed: () => Navigator.pop(ctx, true), child: Text(context.tr('Delete'))),
              ],
            ));
    final pw = ctl.text;
    ctl.dispose();
    if (ok != true) return;
    try {
      await ref.read(authActionsProvider).deleteAccount(pw);
      if (mounted) context.go('/home');
    } on AppException catch (e) {
      if (mounted) showError(context, e);
    }
  }
}

// ---------------------------------------------------------- addresses ----

class AddressesScreen extends ConsumerWidget {
  const AddressesScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) => ShopScaffold(
        title: context.tr('Addresses'),
        actions: [
          IconButton(
              key: const Key('add_address'),
              icon: const Icon(Icons.add),
              onPressed: () => _edit(context, ref, null)),
        ],
        body: RequireAuth(
            from: '/account/addresses',
            child: AsyncBody<List<Map<String, dynamic>>>(ref.watch(addressesProvider),
                onRetry: () => ref.invalidate(addressesProvider),
                data: (list) => list.isEmpty
                    ? EmptyView(context.tr('No addresses yet'),
                        icon: Icons.location_off_outlined,
                        actionLabel: context.tr('Add address'),
                        onAction: () => _edit(context, ref, null))
                    : RefreshIndicator(
                        onRefresh: () async => ref.invalidate(addressesProvider),
                        child: ListView(physics: const AlwaysScrollableScrollPhysics(), children: [
                        for (final a in list)
                          ListTile(
                            key: Key('addr_${a['id']}'),
                            title: Text('${a['label'] ?? a['full_name']}'),
                            subtitle: Text([a['full_name'], a['address_line_1'], a['city'], a['country']]
                                .where((e) => e != null && '$e'.isNotEmpty)
                                .join(', ')),
                            leading: Icon(a['is_default'] == true ? Icons.home : Icons.home_outlined),
                            trailing: PopupMenuButton<String>(
                              onSelected: (v) async {
                                final repo = ref.read(accountRepositoryProvider);
                                try {
                                  if (v == 'edit') {
                                    await _edit(context, ref, a);
                                    return;
                                  }
                                  if (v == 'default') await repo.setDefaultAddress((a['id'] as num).toInt());
                                  if (v == 'delete') await repo.deleteAddress((a['id'] as num).toInt());
                                  ref.invalidate(addressesProvider);
                                } on AppException catch (e) {
                                  if (context.mounted) showError(context, e);
                                }
                              },
                              itemBuilder: (_) => [
                                PopupMenuItem(value: 'edit', child: Text(context.tr('Edit'))),
                                if (a['is_default'] != true)
                                  PopupMenuItem(value: 'default', child: Text(context.tr('Make default'))),
                                PopupMenuItem(value: 'delete', child: Text(context.tr('Delete'))),
                              ],
                            ),
                          ),
                      ])))),
      );

  Future<void> _edit(BuildContext context, WidgetRef ref, Map<String, dynamic>? a) async {
    final saved = await Navigator.of(context).push<bool>(MaterialPageRoute(
        builder: (_) => UncontrolledProviderScope(
            container: ProviderScope.containerOf(context), child: AddressFormScreen(a))));
    if (saved == true) ref.invalidate(addressesProvider);
  }
}

class AddressFormScreen extends ConsumerStatefulWidget {
  const AddressFormScreen(this.address, {super.key});
  final Map<String, dynamic>? address;
  @override
  ConsumerState<AddressFormScreen> createState() => _AddressFormState();
}

class _AddressFormState extends ConsumerState<AddressFormScreen> {
  late final Map<String, TextEditingController> _c = {
    for (final k in ['label', 'full_name', 'phone', 'address_line_1', 'city', 'state'])
      k: TextEditingController(text: '${widget.address?[k] ?? ''}')
  };
  int? _country;
  bool _default = false, _busy = false;
  String? _error;
  Map<String, String> _errors = {};

  @override
  void initState() {
    super.initState();
    _country = (widget.address?['country_id'] as num?)?.toInt();
    _default = widget.address?['is_default'] == true;
  }

  @override
  void dispose() {
    for (final c in _c.values) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _save() async {
    setState(() {
      _busy = true;
      _error = null;
      _errors = {};
    });
    try {
      await ref.read(accountRepositoryProvider).saveAddress({
        for (final e in _c.entries) e.key: e.value.text.trim(),
        'country_id': _country,
        'is_default': _default,
      }, id: (widget.address?['id'] as num?)?.toInt());
      if (mounted) Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      setState(() {
        _error = e.message;
        _errors = {
          for (final en in (e.errors ?? const {}).entries)
            en.key: en.value is List ? '${(en.value as List).firstOrNull}' : '${en.value}'
        };
      });
    } on AppException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final opts = ref.watch(preferenceOptionsProvider);
    Widget f(String k, String label) => Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: TextField(
            key: Key('addr_$k'),
            controller: _c[k],
            decoration: InputDecoration(labelText: context.tr(label), errorText: _errors[k])));
    return ShopScaffold(
      title: context.tr(widget.address == null ? 'Add address' : 'Edit address'),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        f('label', 'Label'),
        f('full_name', 'Name'),
        f('phone', 'Phone'),
        f('address_line_1', 'Address'),
        f('city', 'City'),
        f('state', 'State'),
        opts.when(
          data: (o) => DropdownButtonFormField<int>(
            key: const Key('addr_country'),
            initialValue: o.countries.any((c) => (c['id'] as num).toInt() == _country) ? _country : null,
            decoration: InputDecoration(labelText: context.tr('Country'), errorText: _errors['country_id']),
            items: [
              for (final c in o.countries)
                DropdownMenuItem(value: (c['id'] as num).toInt(), child: Text('${c['name']}'))
            ],
            onChanged: (v) => setState(() => _country = v),
          ),
          loading: () => const LinearProgressIndicator(),
          error: (e, _) => ErrorView(e, onRetry: () => ref.invalidate(preferenceOptionsProvider)),
        ),
        CheckboxListTile(
            value: _default,
            onChanged: (v) => setState(() => _default = v ?? false),
            title: Text(context.tr('Default'))),
        if (_error != null) Text(_error!, key: const Key('addr_error'), style: TextStyle(color: Theme.of(context).colorScheme.error)),
        const SizedBox(height: 12),
        SkinButton(key: const Key('addr_save'), label: context.tr('Save'), busy: _busy, onPressed: _save),
      ]),
    );
  }
}

// ---------------------------------------------------------- favorites ----

class FavoritesScreen extends ConsumerWidget {
  const FavoritesScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) => ShopScaffold(
        title: context.tr('Favorites'),
        navIndex: 3,
        body: RequireAuth(
            from: '/favorites',
            child: AsyncBody(ref.watch(favoritesListProvider),
                onRetry: () => ref.invalidate(favoritesListProvider),
                data: (items) => items.isEmpty
                    ? EmptyView(context.tr('No favorites yet'),
                        icon: Icons.favorite_border,
                        actionLabel: context.tr('Continue shopping'),
                        onAction: () => openRoute(context, '/home'))
                    : RefreshIndicator(
                        onRefresh: () async => ref.invalidate(favoritesListProvider),
                        child: ProductGridView(key: const Key('favorites_grid'), products: items)))),
      );
}

// -------------------------------------------------------- preferences ----

/// Language / currency / country picker (`GET /preferences/options`).
class PreferencesScreen extends ConsumerWidget {
  const PreferencesScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final opts = ref.watch(preferenceOptionsProvider);
    final prefs = ref.watch(prefsProvider);
    final ctl = ref.read(prefsProvider.notifier);
    return ShopScaffold(
      title: context.tr('Preferences'),
      body: AsyncBody<PreferenceOptions>(opts,
          onRetry: () => ref.invalidate(preferenceOptionsProvider),
          data: (o) => ListView(children: [
                _section(context, 'Language'),
                RadioGroup<String>(
                  groupValue: prefs.locale,
                  onChanged: (v) => v == null ? null : ctl.set(locale: v),
                  child: Column(children: [
                    for (final l in o.locales)
                      RadioListTile<String>(
                          key: Key('locale_${l['code']}'),
                          value: '${l['code']}',
                          title: Text('${l['name']}')),
                  ]),
                ),
                _section(context, 'Currency'),
                RadioGroup<String>(
                  groupValue: prefs.currency,
                  onChanged: (v) => v == null ? null : ctl.set(currency: v),
                  child: Column(children: [
                    for (final c in o.currencies)
                      RadioListTile<String>(
                          key: Key('currency_${c['code']}'),
                          value: '${c['code']}',
                          title: Text('${c['code']} ${c['symbol'] ?? ''}')),
                  ]),
                ),
                _section(context, 'Country'),
                RadioGroup<int>(
                  groupValue: prefs.countryId,
                  onChanged: (v) => v == null ? null : ctl.set(countryId: v),
                  child: Column(children: [
                    for (final c in o.countries)
                      RadioListTile<int>(
                          key: Key('country_${c['id']}'),
                          value: (c['id'] as num).toInt(),
                          title: Text('${c['flag_emoji'] ?? ''} ${c['name']}')),
                  ]),
                ),
              ])),
    );
  }

  Widget _section(BuildContext c, String t) => Padding(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 4),
      child: Text(c.tr(t), style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16)));
}

/// Footer info (about text, social, copyright) moved off home into a collapsible tile.
class _AboutTile extends ConsumerWidget {
  const _AboutTile();
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final cfg = ref.watch(tenantControllerProvider.select((s) => s.config));
    final text = cfg?.footer['text'] as String?;
    final copy = cfg?.footer['copyright'] as String?;
    if (cfg == null || ((text ?? '').isEmpty && (copy ?? '').isEmpty && cfg.socialLinks.isEmpty)) {
      return const SizedBox.shrink();
    }
    return ExpansionTile(
      key: const Key('about_tile'),
      leading: const Icon(Icons.info_outline),
      title: Text(context.tr('About')),
      children: [FooterInfo(text: text, copyright: copy, social: cfg.socialLinks)],
    );
  }
}

/// Light / dark / system appearance toggle (stored per device).
class _ThemeModeTile extends ConsumerWidget {
  const _ThemeModeTile();
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final mode = ref.watch(themeModeProvider);
    return ListTile(
      key: const Key('theme_mode_tile'),
      leading: const Icon(Icons.brightness_6_outlined),
      title: Text(context.tr('Appearance')),
      subtitle: SegmentedButton<ThemeMode>(
        key: const Key('theme_mode_toggle'),
        showSelectedIcon: false,
        segments: [
          ButtonSegment(value: ThemeMode.system, label: Text(context.tr('System'))),
          ButtonSegment(value: ThemeMode.light, label: Text(context.tr('Light'))),
          ButtonSegment(value: ThemeMode.dark, label: Text(context.tr('Dark'))),
        ],
        selected: {mode},
        onSelectionChanged: (v) => ref.read(themeModeProvider.notifier).set(v.first),
      ),
    );
  }
}
