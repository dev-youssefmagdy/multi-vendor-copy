import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../app/providers.dart';
import '../../../core/errors/app_exception.dart';
import '../../../core/l10n/strings.dart';
import '../../../core/storage/key_value_store.dart';
import '../../../themes/shared/models.dart';
import '../../../themes/shared/primitives.dart';
import '../commerce_models.dart';
import '../commerce_providers.dart';
import '../widgets/shop_widgets.dart';
import 'listing_screen.dart';

/// S03: search box with debounced autocomplete, recent searches and results.
class SearchScreen extends ConsumerStatefulWidget {
  const SearchScreen({super.key, this.initial = '', this.debounce = const Duration(milliseconds: 300)});
  final String initial;
  final Duration debounce;
  @override
  ConsumerState<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends ConsumerState<SearchScreen> {
  late final _ctl = TextEditingController(text: widget.initial);
  Timer? _t;
  List<ProductItem> _suggestions = const [];
  Object? _error;
  bool _loading = false;
  String? _submitted;
  int _seq = 0;

  @override
  void initState() {
    super.initState();
    if (widget.initial.isNotEmpty) _submitted = widget.initial;
  }

  @override
  void dispose() {
    _t?.cancel();
    _ctl.dispose();
    super.dispose();
  }

  String? get _recentKey {
    final id = ref.read(tenantControllerProvider).tenant?.id;
    return id == null ? null : tenantKey(id, 'recent_searches');
  }

  List<String> get _recent {
    final k = _recentKey;
    return k == null ? [] : ref.read(sharedPreferencesProvider).getStringList(k) ?? [];
  }

  void _onChanged(String v) {
    _t?.cancel();
    setState(() => _submitted = null);
    if (v.trim().length < 2) {
      setState(() {
        _suggestions = const [];
        _error = null;
      });
      return;
    }
    _t = Timer(widget.debounce, () async {
      final seq = ++_seq;
      setState(() => _loading = true);
      try {
        final r = await ref.read(shopRepositoryProvider).autocomplete(v.trim());
        if (!mounted || seq != _seq) return;
        setState(() {
          _suggestions = r;
          _error = null;
          _loading = false;
        });
      } on AppException catch (e) {
        if (!mounted || seq != _seq) return;
        setState(() {
          _error = e;
          _loading = false;
        });
      }
    });
  }

  Future<void> _submit(String v) async {
    final q = v.trim();
    if (q.isEmpty) return;
    _t?.cancel();
    final k = _recentKey;
    if (k != null) {
      final list = [q, ..._recent.where((e) => e != q)].take(8).toList();
      await ref.read(sharedPreferencesProvider).setStringList(k, list);
    }
    if (mounted) {
      _ctl.text = q;
      setState(() => _submitted = q);
    }
  }

  @override
  Widget build(BuildContext context) {
    return ShopScaffold(
      title: context.tr('Search'),
      titleWidget: TextField(
        key: const Key('search_field'),
        controller: _ctl,
        autofocus: widget.initial.isEmpty,
        textInputAction: TextInputAction.search,
        onChanged: _onChanged,
        onSubmitted: _submit,
        decoration: InputDecoration(
            hintText: context.tr('Search products'),
            border: InputBorder.none,
            suffixIcon: _ctl.text.isEmpty
                ? null
                : IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () {
                      _ctl.clear();
                      _onChanged('');
                    })),
      ),
      body: _submitted != null
          ? _Results(_submitted!, key: ValueKey(_submitted))
          : _suggestionsView(),
    );
  }

  Widget _suggestionsView() {
    if (_error != null) {
      return ErrorView(_error!, onRetry: () => _onChanged(_ctl.text));
    }
    if (_ctl.text.trim().length < 2) {
      final recent = _recent;
      return ListView(children: [
        if (recent.isNotEmpty)
          Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
              child: Text(context.tr('Recent searches'),
                  style: const TextStyle(fontWeight: FontWeight.w800))),
        for (final r in recent)
          ListTile(
              key: Key('recent_$r'),
              leading: const Icon(Icons.history),
              title: Text(r),
              onTap: () => _submit(r)),
        ListTile(
            leading: const Icon(Icons.local_fire_department_outlined),
            title: Text(context.tr('Best selling')),
            onTap: () => openRoute(context, '/list/best-selling')),
        ListTile(
            leading: const Icon(Icons.new_releases_outlined),
            title: Text(context.tr('New in')),
            onTap: () => openRoute(context, '/list/new-in')),
        ListTile(
            leading: const Icon(Icons.star_outline),
            title: Text(context.tr('Top rated')),
            onTap: () => openRoute(context, '/list/full-star')),
        ListTile(
            leading: const Icon(Icons.percent),
            title: Text(context.tr('Offers')),
            onTap: () => openRoute(context, '/list/offers')),
      ]);
    }
    if (_loading && _suggestions.isEmpty) return const LoadingView();
    if (_suggestions.isEmpty) {
      return EmptyView(context.tr('No results'), icon: Icons.search_off);
    }
    return ListView.builder(
      key: const Key('suggestions'),
      itemCount: _suggestions.length,
      itemBuilder: (_, i) {
        final p = _suggestions[i];
        return ListTile(
          key: Key('suggestion_${p.slug}'),
          leading: SizedBox(width: 48, height: 48, child: NetImage(p.image)),
          title: Text(p.name, maxLines: 2, overflow: TextOverflow.ellipsis),
          subtitle: Price(p.price),
          onTap: () => openRoute(context, '/product/${p.slug}'),
        );
      },
    );
  }
}

class _Results extends ConsumerWidget {
  const _Results(this.keyword, {super.key});
  final String keyword;
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final q = ListingQuery(ListingKind.search, keyword: keyword);
    final state = ref.watch(listingProvider(q));
    final n = ref.read(listingProvider(q).notifier);
    if (state.initialLoading) return const LoadingView();
    if (state.error != null && state.items.isEmpty) {
      return ErrorView(state.error!, onRetry: n.retry);
    }
    if (state.items.isEmpty) {
      return EmptyView(context.tr('No results'), icon: Icons.search_off);
    }
    return NotificationListener<ScrollNotification>(
      onNotification: (sn) {
        if (sn.metrics.pixels > sn.metrics.maxScrollExtent - 400) n.loadMore();
        return false;
      },
      child: ProductGridView(key: const Key('search_results'), products: state.items),
    );
  }
}
