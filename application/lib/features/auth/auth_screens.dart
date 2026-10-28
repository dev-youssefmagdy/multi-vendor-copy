import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../app/providers.dart';
import '../../core/errors/app_exception.dart';
import '../../core/l10n/strings.dart';
import '../../design/design.dart';
import '../shop/commerce_providers.dart';
import '../shop/widgets/shop_widgets.dart';

String _safeFrom(String? from) =>
    from != null && from.startsWith('/') && !from.startsWith('//') ? from : '/home';

/// Shared state for auth forms: busy flag + server field errors.
mixin _FormState<T extends ConsumerStatefulWidget> on ConsumerState<T> {
  bool busy = false;
  String? error;
  Map<String, String> fieldErrors = {};

  Future<bool> run(Future<void> Function() op) async {
    setState(() {
      busy = true;
      error = null;
      fieldErrors = {};
    });
    try {
      await op();
      return true;
    } on ApiException catch (e) {
      setState(() {
        error = e.message;
        fieldErrors = {
          for (final en in (e.errors ?? const {}).entries)
            en.key: en.value is List && (en.value as List).isNotEmpty
                ? '${(en.value as List).first}'
                : '${en.value}'
        };
      });
    } on AppException catch (e) {
      setState(() => error = e.message);
    } finally {
      if (mounted) setState(() => busy = false);
    }
    return false;
  }
}

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key, this.from});
  final String? from;
  @override
  ConsumerState<LoginScreen> createState() => _LoginState();
}

class _LoginState extends ConsumerState<LoginScreen> with _FormState {
  final _email = TextEditingController();
  final _pass = TextEditingController();
  final _key = GlobalKey<FormState>();

  @override
  void dispose() {
    _email.dispose();
    _pass.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_key.currentState!.validate()) return;
    final ok = await run(() => ref
        .read(authActionsProvider)
        .login(_email.text.trim(), _pass.text));
    if (ok && mounted) context.go(_safeFrom(widget.from));
  }

  @override
  Widget build(BuildContext context) {
    return ShopScaffold(
      title: context.tr('Sign in'),
      body: Form(
        key: _key,
        child: ListView(padding: const EdgeInsets.all(24), children: [
          AppTextField(
              fieldKey: const Key('login_email'),
              controller: _email,
              keyboardType: TextInputType.emailAddress,
              autofillHints: const [AutofillHints.email],
              label: context.tr('Email'),
              errorText: fieldErrors['email'],
              validator: (v) => (v == null || !v.contains('@')) ? context.tr('Enter a valid email') : null),
          const SizedBox(height: 12),
          AppTextField(
              fieldKey: const Key('login_password'),
              controller: _pass,
              password: true,
              textInputAction: TextInputAction.done,
              autofillHints: const [AutofillHints.password],
              label: context.tr('Password'),
              errorText: fieldErrors['password'],
              validator: (v) => (v == null || v.isEmpty) ? context.tr('Required') : null),
          if (error != null)
            Padding(
                padding: const EdgeInsets.only(top: 12),
                child: Text(error!, key: const Key('auth_error'), style: TextStyle(color: Theme.of(context).colorScheme.error))),
          const SizedBox(height: 20),
          SkinButton(
              key: const Key('login_submit'),
              label: context.tr('Sign in'),
              busy: busy,
              onPressed: _submit),
          TextButton(
              key: const Key('to_forgot'),
              onPressed: () => context.push('/forgot-password'),
              child: Text(context.tr('Forgot password?'))),
          TextButton(
              key: const Key('to_register'),
              onPressed: () => context.pushReplacement(
                  '/register${widget.from == null ? '' : '?from=${Uri.encodeComponent(widget.from!)}'}'),
              child: Text(context.tr('Create account'))),
          if (ref.watch(tenantControllerProvider.select(
              (t) => t.config?.feature('social_login') ?? false)))
            _SocialStub(),
        ]),
      ),
    );
  }
}

class _SocialStub extends StatelessWidget {
  @override
  Widget build(BuildContext context) => Column(children: [
        const Divider(),
        for (final p in ['Google', 'Apple'])
          OutlinedButton(
              onPressed: () => ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text(context.tr('Social sign-in is not available yet')))),
              child: Text('${context.tr('Continue with')} $p')),
      ]);
}

class RegisterScreen extends ConsumerStatefulWidget {
  const RegisterScreen({super.key, this.from});
  final String? from;
  @override
  ConsumerState<RegisterScreen> createState() => _RegisterState();
}

class _RegisterState extends ConsumerState<RegisterScreen> with _FormState {
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _phone = TextEditingController();
  final _pass = TextEditingController();
  final _conf = TextEditingController();
  final _key = GlobalKey<FormState>();

  @override
  void dispose() {
    for (final c in [_name, _email, _phone, _pass, _conf]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_key.currentState!.validate()) return;
    final ok = await run(() => ref.read(authActionsProvider).register({
          'name': _name.text.trim(),
          'email': _email.text.trim(),
          if (_phone.text.trim().isNotEmpty) 'phone': _phone.text.trim(),
          'password': _pass.text,
          'password_confirmation': _conf.text,
        }));
    if (ok && mounted) context.go(_safeFrom(widget.from));
  }

  @override
  Widget build(BuildContext context) {
    Widget f(String k, String label, TextEditingController c,
            {bool obscure = false,
            TextInputType? type,
            String? Function(String?)? v,
            String? errKey,
            List<String>? hints}) =>
        Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: AppTextField(
                fieldKey: Key(k),
                controller: c,
                password: obscure,
                keyboardType: type,
                autofillHints: hints,
                label: context.tr(label),
                errorText: fieldErrors[errKey ?? ''],
                validator: v));
    String? req(String? v) => (v == null || v.trim().isEmpty) ? context.tr('Required') : null;
    return ShopScaffold(
      title: context.tr('Create account'),
      body: Form(
        key: _key,
        child: ListView(padding: const EdgeInsets.all(24), children: [
          f('reg_name', 'Name', _name, v: req, errKey: 'name', hints: const [AutofillHints.name]),
          f('reg_email', 'Email', _email,
              type: TextInputType.emailAddress,
              hints: const [AutofillHints.email],
              errKey: 'email',
              v: (v) => (v == null || !v.contains('@')) ? context.tr('Enter a valid email') : null),
          f('reg_phone', 'Phone', _phone, type: TextInputType.phone, errKey: 'phone', hints: const [AutofillHints.telephoneNumber]),
          f('reg_password', 'Password', _pass,
              obscure: true,
              hints: const [AutofillHints.newPassword],
              errKey: 'password',
              v: (v) => (v == null || v.length < 8) ? context.tr('At least 8 characters') : null),
          f('reg_confirm', 'Confirm password', _conf,
              obscure: true,
              v: (v) => v != _pass.text ? context.tr('Passwords do not match') : null),
          if (error != null)
            Text(error!, key: const Key('auth_error'), style: const TextStyle(color: Colors.red)),
          const SizedBox(height: 12),
          SkinButton(
              key: const Key('register_submit'),
              label: context.tr('Create account'),
              busy: busy,
              onPressed: _submit),
        ]),
      ),
    );
  }
}

/// Forgot password (request code) -> reset password (code + new password).
class ForgotPasswordScreen extends ConsumerStatefulWidget {
  const ForgotPasswordScreen({super.key});
  @override
  ConsumerState<ForgotPasswordScreen> createState() => _ForgotState();
}

class _ForgotState extends ConsumerState<ForgotPasswordScreen> with _FormState {
  final _email = TextEditingController();
  final _token = TextEditingController();
  final _pass = TextEditingController();
  final _conf = TextEditingController();
  bool _sent = false;

  @override
  void dispose() {
    for (final c in [_email, _token, _pass, _conf]) {
      c.dispose();
    }
    super.dispose();
  }


  @override
  Widget build(BuildContext context) {
    return ShopScaffold(
      title: context.tr('Reset password'),
      body: ListView(padding: const EdgeInsets.all(24), children: [
        AppTextField(
            fieldKey: const Key('forgot_email'),
            controller: _email,
            enabled: !_sent,
            keyboardType: TextInputType.emailAddress,
            autofillHints: const [AutofillHints.email],
            label: context.tr('Email')),
        if (!_sent) ...[
          const SizedBox(height: 16),
          SkinButton(
              key: const Key('forgot_submit'),
              label: context.tr('Send reset code'),
              busy: busy,
              onPressed: () async {
                final ok = await run(() => ref
                    .read(authRepositoryProvider)
                    .forgotPassword(_email.text.trim()));
                if (ok && mounted) setState(() => _sent = true);
              }),
        ] else ...[
          const SizedBox(height: 12),
          Text(context.tr('If the email exists, we sent a reset code to it.'),
              key: const Key('forgot_sent')),
          const SizedBox(height: 12),
          AppTextField(
              fieldKey: const Key('reset_token'),
              controller: _token,
              autocorrect: false,
              label: context.tr('Reset code')),
          const SizedBox(height: 12),
          AppTextField(
              fieldKey: const Key('reset_password'),
              controller: _pass,
              password: true,
              label: context.tr('Password'),
              errorText: fieldErrors['password']),
          const SizedBox(height: 12),
          AppTextField(
              fieldKey: const Key('reset_confirm'),
              controller: _conf,
              password: true,
              textInputAction: TextInputAction.done,
              label: context.tr('Confirm password')),
          if (error != null)
            Padding(padding: const EdgeInsets.only(top: 8), child: Text(error!, key: const Key('auth_error'), style: const TextStyle(color: Colors.red))),
          const SizedBox(height: 16),
          SkinButton(
              key: const Key('reset_submit'),
              label: context.tr('Reset password'),
              busy: busy,
              onPressed: () async {
                final ok = await run(() => ref.read(authRepositoryProvider).resetPassword(
                    email: _email.text.trim(),
                    token: _token.text.trim(),
                    password: _pass.text,
                    confirmation: _conf.text));
                if (ok && context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(content: Text(context.tr('Password updated. Please sign in.'))));
                  context.pushReplacement('/login');
                }
              }),
        ],
      ]),
    );
  }
}
