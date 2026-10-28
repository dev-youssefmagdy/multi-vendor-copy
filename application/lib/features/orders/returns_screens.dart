import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/errors/app_exception.dart';
import '../../core/l10n/strings.dart';
import '../../core/platform/media_picker.dart';
import '../shop/commerce_models.dart';
import '../shop/commerce_providers.dart';
import '../shop/shop_repository.dart';
import '../shop/widgets/shop_widgets.dart';
import 'orders_repository.dart';
import 'orders_screens.dart';

class ReturnsScreen extends ConsumerWidget {
  const ReturnsScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) => ShopScaffold(
        title: context.tr('Returns'),
        body: RequireAuth(
            from: '/returns',
            child: AsyncBody<List<Map<String, dynamic>>>(ref.watch(returnsProvider),
                onRetry: () => ref.invalidate(returnsProvider),
                data: (list) => list.isEmpty
                    ? EmptyView(context.tr('No returns yet'), icon: Icons.assignment_return_outlined)
                    : ListView.separated(
                        itemCount: list.length,
                        separatorBuilder: (_, _) => const Divider(height: 1),
                        itemBuilder: (_, i) {
                          final r = list[i];
                          return ListTile(
                            key: Key('return_${r['id']}'),
                            title: Text('#${r['order_number']}'),
                            subtitle: Text('${r['type'] ?? ''} · x${r['quantity']}'),
                            trailing: StatusChip('${r['status']}'),
                            onTap: () => openRoute(context, '/returns/${r['id']}'),
                          );
                        }))),
      );
}

/// S15: return detail.
class ReturnDetailScreen extends ConsumerWidget {
  const ReturnDetailScreen(this.id, {super.key});
  final int id;
  @override
  Widget build(BuildContext context, WidgetRef ref) => ShopScaffold(
        title: context.tr('Return'),
        body: RequireAuth(
            from: '/returns/$id',
            child: AsyncBody<Map<String, dynamic>>(ref.watch(returnDetailProvider(id)),
                onRetry: () => ref.invalidate(returnDetailProvider(id)),
                data: (r) => ListView(padding: const EdgeInsets.all(16), children: [
                      Row(children: [
                        Expanded(child: Text('#${r['order_number']}', style: const TextStyle(fontWeight: FontWeight.w800))),
                        StatusChip('${r['status']}'),
                      ]),
                      const SizedBox(height: 8),
                      Text('${context.tr('Reason')}: ${r['reason'] ?? ''}'),
                      Text('${context.tr('Quantity')}: ${r['quantity']}'),
                      if (r['refund_amount'] != null)
                        Row(children: [Text('${context.tr('Refund')}: '), Price(num.tryParse('${r['refund_amount']}'))]),
                      if ('${r['description'] ?? ''}'.isNotEmpty) Text('${r['description']}'),
                      const Divider(height: 24),
                      for (final n in asList(r['notes']))
                        ListTile(
                            contentPadding: EdgeInsets.zero,
                            title: Text('${n['note']}'),
                            subtitle: Text('${n['author_type']} · ${n['created_at'] ?? ''}')),
                    ]))),
      );
}

List<(String, String)> _opts(Object? v) => [
      for (final e in (v is List ? v : const []))
        if (e is Map) ('${e['value']}', '${e['label'] ?? e['value']}') else ('$e', '$e')
    ];

/// S14: return request (multipart with photos).
class ReturnRequestScreen extends ConsumerStatefulWidget {
  const ReturnRequestScreen(this.uuid, {super.key});
  final String uuid;
  @override
  ConsumerState<ReturnRequestScreen> createState() => _ReturnRequestState();
}

class _ReturnRequestState extends ConsumerState<ReturnRequestScreen> {
  Map<String, dynamic>? _options;
  Object? _loadError;
  int? _itemId;
  int _qty = 1;
  String? _reason, _type, _method;
  final _desc = TextEditingController();
  final _variant = TextEditingController();
  final List<PickedFile> _photos = [];
  bool _busy = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _desc.dispose();
    _variant.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() => _loadError = null);
    try {
      final o = await ref.read(ordersRepositoryProvider).returnOptions();
      if (!mounted) return;
      setState(() {
        _options = o;
        _reason = _opts(o['reasons']).firstOrNull?.$1;
        _type = _opts(o['types']).firstOrNull?.$1;
        _method = _opts(o['methods']).firstOrNull?.$1;
      });
    } on AppException catch (e) {
      if (mounted) setState(() => _loadError = e);
    }
  }

  Future<void> _submit() async {
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final r = await ref.read(ordersRepositoryProvider).createReturn(
          widget.uuid,
          {
            'order_item_id': _itemId,
            'quantity': _qty,
            'reason': _reason,
            'type': _type,
            'return_method': _method,
            'description': _desc.text.trim().isEmpty ? null : _desc.text.trim(),
            if (_type == 'exchange') 'replacement_variant_id': _variant.text.trim(),
          },
          [for (final p in _photos) ReturnFile(p.path, p.name)]);
      ref.invalidate(returnsProvider);
      if (mounted) openRoute(context, '/returns/${r['id']}');
    } on ApiException catch (e) {
      final detail = e.errors?.values.map((v) => v is List ? v.join(' ') : '$v').join('\n');
      setState(() => _error = [e.message, if (detail != null && detail.isNotEmpty) detail].join('\n'));
    } on AppException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final order = ref.watch(orderProvider(widget.uuid));
    return ShopScaffold(
      title: context.tr('Request a return'),
      body: RequireAuth(
        from: '/orders/${widget.uuid}/return',
        child: _loadError != null
            ? ErrorView(_loadError!, onRetry: _load)
            : _options == null
                ? const LoadingView()
                : AsyncBody<OrderDetail>(order, onRetry: () => ref.invalidate(orderProvider(widget.uuid)), data: (o) {
                    final items = o.items;
                    DropdownButtonFormField<String> dd(String k, String label, List<(String, String)> opts,
                            String? v, ValueChanged<String?> on) =>
                        DropdownButtonFormField<String>(
                          key: Key(k),
                          initialValue: v,
                          decoration: InputDecoration(labelText: context.tr(label)),
                          items: [for (final e in opts) DropdownMenuItem(value: e.$1, child: Text(statusLabel(e.$2)))],
                          onChanged: on,
                        );
                    return ListView(padding: const EdgeInsets.all(16), children: [
                      DropdownButtonFormField<int>(
                        key: const Key('return_item'),
                        initialValue: _itemId,
                        decoration: InputDecoration(labelText: context.tr('Item')),
                        items: [
                          for (final it in items)
                            if ((it['id'] ?? it['order_item_id']) != null)
                              DropdownMenuItem(
                                  value: ((it['id'] ?? it['order_item_id']) as num).toInt(),
                                  child: Text('${it['product_name']}')),
                        ],
                        onChanged: (v) => setState(() => _itemId = v),
                      ),
                      const SizedBox(height: 12),
                      Row(children: [
                        Text(context.tr('Quantity')),
                        const Spacer(),
                        IconButton(icon: const Icon(Icons.remove), onPressed: _qty > 1 ? () => setState(() => _qty--) : null),
                        Text('$_qty', key: const Key('return_qty')),
                        IconButton(icon: const Icon(Icons.add), onPressed: () => setState(() => _qty++)),
                      ]),
                      dd('return_reason', 'Reason', _opts(_options!['reasons']), _reason, (v) => setState(() => _reason = v)),
                      const SizedBox(height: 12),
                      dd('return_type', 'Type', _opts(_options!['types']), _type, (v) => setState(() => _type = v)),
                      const SizedBox(height: 12),
                      dd('return_method', 'Return method', _opts(_options!['methods']), _method, (v) => setState(() => _method = v)),
                      if (_type == 'exchange') ...[
                        const SizedBox(height: 12),
                        TextField(
                            key: const Key('return_variant'),
                            controller: _variant,
                            keyboardType: TextInputType.number,
                            decoration: InputDecoration(labelText: context.tr('Replacement variant id'))),
                      ],
                      const SizedBox(height: 12),
                      TextField(
                          key: const Key('return_desc'),
                          controller: _desc,
                          maxLines: 3,
                          decoration: InputDecoration(labelText: context.tr('Description'))),
                      const SizedBox(height: 12),
                      Wrap(spacing: 8, children: [
                        for (final p in _photos)
                          Chip(label: Text(p.name), onDeleted: () => setState(() => _photos.remove(p))),
                        ActionChip(
                            key: const Key('add_photos'),
                            avatar: const Icon(Icons.add_a_photo_outlined, size: 18),
                            label: Text(context.tr('Add photos')),
                            onPressed: () async {
                              final picked = await ref.read(mediaPickerProvider).pickPhotos();
                              if (mounted) setState(() => _photos.addAll(picked));
                            }),
                      ]),
                      if (_error != null)
                        Padding(
                            padding: const EdgeInsets.only(top: 8),
                            child: Text(_error!, key: const Key('return_error'), style: TextStyle(color: Theme.of(context).colorScheme.error))),
                      const SizedBox(height: 16),
                      SkinButton(
                          key: const Key('return_submit'),
                          label: context.tr('Submit request'),
                          busy: _busy,
                          onPressed: _itemId == null || _reason == null || _type == null || _method == null ? null : _submit),
                    ]);
                  }),
      ),
    );
  }
}
