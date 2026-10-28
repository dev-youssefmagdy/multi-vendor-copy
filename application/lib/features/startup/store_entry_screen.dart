import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../app/providers.dart';
import '../../core/errors/app_exception.dart';
import '../../design/fields.dart';
import '../deeplink/url_normalizer.dart';
import '../tenant/tenant_models.dart';
import 'screens.dart' show platformSplashName;

/// Reads the clipboard text. Overridable in tests.
final clipboardReaderProvider = Provider<Future<String?> Function()>((_) => () async {
      try {
        return (await Clipboard.getData(Clipboard.kTextPlain))?.text;
      } catch (_) {
        return null;
      }
    });

/// What a typed/pasted string would open, or null when it is not a store.
/// Accepts `https://x.com/products/1`, `x.com` and store codes.
String? previewStoreInput(String raw, [UrlNormalizer n = const UrlNormalizer()]) {
  try {
    final i = n.classifyInput(raw);
    return i.value;
  } on InvalidStoreInputException {
    return null;
  }
}

/// A clipboard value is only suggested when it clearly is a store address
/// (contains a dot or scheme), never for arbitrary words that could be codes.
String? clipboardStoreCandidate(String? raw,
    [UrlNormalizer n = const UrlNormalizer()]) {
  final t = raw?.trim();
  if (t == null || t.isEmpty || t.length > 300 || t.contains(RegExp(r'\s'))) {
    return null;
  }
  if (!t.contains('.') && !t.contains('://')) return null;
  try {
    return n.classifyInput(t).value;
  } on InvalidStoreInputException {
    return null;
  }
}

class StoreEntryScreen extends ConsumerStatefulWidget {
  const StoreEntryScreen({super.key});
  @override
  ConsumerState<StoreEntryScreen> createState() => _StoreEntryState();
}

class _StoreEntryState extends ConsumerState<StoreEntryScreen>
    with WidgetsBindingObserver {
  final _ctl = TextEditingController();
  String? _suggestion; // normalised host from the clipboard
  String? _dismissed;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _ctl.addListener(() => setState(() {}));
    _checkClipboard();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) _checkClipboard();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _ctl.dispose();
    super.dispose();
  }

  Future<void> _checkClipboard() async {
    final text = await ref.read(clipboardReaderProvider)();
    final c = clipboardStoreCandidate(text);
    if (!mounted || c == null || c == _dismissed) return;
    setState(() => _suggestion = c);
  }

  Future<void> _paste() async {
    final text = await ref.read(clipboardReaderProvider)();
    if (!mounted || text == null || text.trim().isEmpty) return;
    _ctl.text = text.trim();
    _ctl.selection = TextSelection.collapsed(offset: _ctl.text.length);
  }

  void _submit() =>
      ref.read(tenantControllerProvider.notifier).submitInput(_ctl.text);

  void _useSuggestion() {
    final host = _suggestion;
    if (host == null) return;
    setState(() => _suggestion = null);
    ref.read(tenantControllerProvider.notifier).submitInput(host);
  }

  void _dismissSuggestion() => setState(() {
        _dismissed = _suggestion;
        _suggestion = null;
      });

  @override
  Widget build(BuildContext context) {
    final message = ref.watch(tenantControllerProvider.select((s) => s.message));
    final recents = ref.read(tenantStorageProvider).loadRecent();
    final cs = Theme.of(context).colorScheme;
    final text = _ctl.text.trim();
    final preview = text.isEmpty ? null : previewStoreInput(text);
    final isCode = preview != null && !text.contains('.') && !text.contains('/');
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 480),
            child: ListView(
                padding: const EdgeInsetsDirectional.fromSTEB(24, 32, 24, 24),
                children: [
                  Semantics(
                    header: true,
                    child: Column(children: [
                      Container(
                        width: 72,
                        height: 72,
                        decoration: BoxDecoration(
                            color: cs.primary,
                            borderRadius: BorderRadius.circular(18)),
                        child: Icon(Icons.storefront_outlined,
                            color: cs.onPrimary, size: 36),
                      ),
                      const SizedBox(height: 16),
                      Text(platformSplashName,
                          style: Theme.of(context).textTheme.headlineSmall),
                      const SizedBox(height: 4),
                      Text('Find your store',
                          style: Theme.of(context).textTheme.titleMedium),
                    ]),
                  ),
                  const SizedBox(height: 28),
                  if (_suggestion != null) ...[
                    Card(
                      key: const Key('clipboard_suggestion'),
                      margin: EdgeInsets.zero,
                      child: Padding(
                        padding: const EdgeInsets.all(12),
                        child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('Open the store from your clipboard?',
                                  style: Theme.of(context).textTheme.titleSmall),
                              const SizedBox(height: 4),
                              Text(_suggestion!),
                              Wrap(alignment: WrapAlignment.end, children: [
                                TextButton(
                                    key: const Key('clipboard_dismiss'),
                                    onPressed: _dismissSuggestion,
                                    child: const Text('No thanks')),
                                FilledButton(
                                    key: const Key('clipboard_use'),
                                    onPressed: _useSuggestion,
                                    child: const Text('Open store')),
                              ]),
                            ]),
                      ),
                    ),
                    const SizedBox(height: 16),
                  ],
                  AppTextField(
                    fieldKey: const Key('store_input'),
                    controller: _ctl,
                    keyboardType: TextInputType.url,
                    autocorrect: false,
                    textInputAction: TextInputAction.go,
                    onSubmitted: (_) => _submit(),
                    label: 'Enter store URL or code',
                    errorText: message,
                  ),
                  if (preview != null)
                    Padding(
                      padding: const EdgeInsets.only(top: 8),
                      child: Semantics(
                        liveRegion: true,
                        child: Text(
                            isCode ? 'Store code: $preview' : 'Opens: $preview',
                            key: const Key('store_preview'),
                            style: Theme.of(context).textTheme.bodySmall),
                      ),
                    ),
                  const SizedBox(height: 12),
                  Row(children: [
                    OutlinedButton.icon(
                        key: const Key('store_paste'),
                        onPressed: _paste,
                        icon: const Icon(Icons.content_paste),
                        label: const Text('Paste')),
                    const SizedBox(width: 12),
                    Expanded(
                      child: FilledButton(
                          key: const Key('store_submit'),
                          onPressed: _submit,
                          child: const Text('Continue')),
                    ),
                  ]),
                  Align(
                    alignment: AlignmentDirectional.centerStart,
                    child: TextButton(
                        key: const Key('store_help'),
                        onPressed: () => _showHelp(context),
                        child: const Text('How do I find my store code?')),
                  ),
                  if (recents.isNotEmpty) ...[
                    const SizedBox(height: 16),
                    Semantics(
                        header: true,
                        child: Text('Recent stores',
                            style: Theme.of(context).textTheme.titleMedium)),
                    for (final t in recents) _RecentTile(t),
                  ],
                ]),
          ),
        ),
      ),
    );
  }

  void _showHelp(BuildContext context) => showModalBottomSheet<void>(
        context: context,
        showDragHandle: true,
        isScrollControlled: true,
        builder: (_) => const _HelpSheet(),
      );
}

class _RecentTile extends ConsumerWidget {
  const _RecentTile(this.t);
  final Tenant t;
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final initial = t.name.isEmpty ? '?' : t.name.characters.first.toUpperCase();
    final fallback = CircleAvatar(child: Text(initial));
    return ListTile(
      key: Key('recent_${t.id}'),
      contentPadding: EdgeInsets.zero,
      leading: SizedBox(
        width: 40,
        height: 40,
        child: t.logo == null
            ? fallback
            : ClipOval(
                child: Image.network(t.logo!,
                    fit: BoxFit.cover,
                    excludeFromSemantics: true,
                    errorBuilder: (_, _, _) => fallback)),
      ),
      title: Text(t.name),
      subtitle: Text(t.domain),
      onTap: () => ref.read(tenantControllerProvider.notifier).selectRecent(t),
    );
  }
}

class _HelpSheet extends StatelessWidget {
  const _HelpSheet();
  @override
  Widget build(BuildContext context) {
    final tt = Theme.of(context).textTheme;
    Widget step(String n, String body) => Padding(
          padding: const EdgeInsets.symmetric(vertical: 6),
          child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
            CircleAvatar(radius: 12, child: Text(n, style: tt.labelSmall)),
            const SizedBox(width: 12),
            Expanded(child: Text(body)),
          ]),
        );
    return SafeArea(
      child: SingleChildScrollView(
        padding: const EdgeInsetsDirectional.fromSTEB(24, 0, 24, 24),
        child: Column(
            key: const Key('store_help_sheet'),
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Finding your store', style: tt.titleLarge),
              const SizedBox(height: 12),
              step('1', 'Ask the store owner for the store link or store code. It is also shown in the store\'s Mobile App settings.'),
              step('2', 'Type the address (for example shop.example.com) or paste any link from the store, such as a product link.'),
              step('3', 'Tap Continue. Next time the app opens your store directly, and links to the store open here automatically.'),
              const SizedBox(height: 8),
              Align(
                alignment: AlignmentDirectional.centerEnd,
                child: TextButton(
                    onPressed: () => Navigator.of(context).pop(),
                    child: const Text('Got it')),
              ),
            ]),
      ),
    );
  }
}
