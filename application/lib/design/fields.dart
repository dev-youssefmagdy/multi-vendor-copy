import 'package:flutter/material.dart';
import 'tokens.dart';

/// Text field with mobile conventions: autofill hints, input action, inline
/// validation after first interaction and a show/hide toggle for passwords.
class AppTextField extends StatefulWidget {
  const AppTextField({
    super.key,
    this.fieldKey,
    required this.controller,
    required this.label,
    this.password = false,
    this.keyboardType,
    this.autofillHints,
    this.validator,
    this.errorText,
    this.textInputAction = TextInputAction.next,
    this.onSubmitted,
    this.enabled = true,
    this.maxLines = 1,
    this.autocorrect,
  });
  final Key? fieldKey;
  final TextEditingController controller;
  final String label;
  final bool password;
  final TextInputType? keyboardType;
  final Iterable<String>? autofillHints;
  final String? Function(String?)? validator;
  final String? errorText;
  final TextInputAction textInputAction;
  final ValueChanged<String>? onSubmitted;
  final bool enabled;
  final int maxLines;

  /// Overrides the default (on, except for passwords).
  final bool? autocorrect;
  @override
  State<AppTextField> createState() => _AppTextFieldState();
}

class _AppTextFieldState extends State<AppTextField> {
  bool _hidden = true;
  @override
  Widget build(BuildContext context) => TextFormField(
        key: widget.fieldKey,
        controller: widget.controller,
        enabled: widget.enabled,
        maxLines: widget.password ? 1 : widget.maxLines,
        obscureText: widget.password && _hidden,
        keyboardType: widget.keyboardType,
        autofillHints: widget.autofillHints,
        textInputAction: widget.textInputAction,
        onFieldSubmitted: widget.onSubmitted,
        autovalidateMode: AutovalidateMode.onUserInteraction,
        validator: widget.validator,
        enableSuggestions: !widget.password,
        autocorrect: widget.autocorrect ?? !widget.password,
        decoration: InputDecoration(
          labelText: widget.label,
          errorText: widget.errorText,
          suffixIcon: widget.password
              ? IconButton(
                  constraints: const BoxConstraints(minWidth: kMinTouch, minHeight: kMinTouch),
                  tooltip: _hidden ? 'Show password' : 'Hide password',
                  icon: Icon(_hidden ? Icons.visibility_outlined : Icons.visibility_off_outlined),
                  onPressed: () => setState(() => _hidden = !_hidden))
              : null,
        ),
      );
}
