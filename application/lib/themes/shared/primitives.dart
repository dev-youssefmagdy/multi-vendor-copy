import 'dart:async';
import 'package:flutter/material.dart';
import 'skin.dart';

/// Network image with a neutral placeholder; never throws in tests/offline.
class NetImage extends StatelessWidget {
  const NetImage(this.url,
      {super.key, this.fit = BoxFit.cover, this.width, this.height});
  final String? url;
  final BoxFit fit;
  final double? width, height;

  @override
  Widget build(BuildContext context) {
    final ph = Container(
        width: width,
        height: height,
        color: const Color(0xFFF5F5F5),
        alignment: Alignment.center,
        child: const Icon(Icons.image_outlined, color: Color(0xFFC4C4C4)));
    final u = url;
    if (u == null || u.isEmpty) return ph;
    return Image.network(u,
        width: width,
        height: height,
        fit: fit,
        errorBuilder: (_, _, _) => ph,
        loadingBuilder: (c, child, p) => p == null ? child : ph);
  }
}

/// Star row (port of the `_stars` partial). Filled / half / empty.
class RatingStars extends StatelessWidget {
  const RatingStars(this.rating, {super.key, this.size = 14, this.color});
  final double rating;
  final double size;
  final Color? color;
  @override
  Widget build(BuildContext context) {
    final c = color ?? const Color(0xFFFFB400);
    return Row(mainAxisSize: MainAxisSize.min, children: [
      for (var i = 1; i <= 5; i++)
        Icon(
            rating >= i
                ? Icons.star_rounded
                : (rating >= i - 0.5 ? Icons.star_half_rounded : Icons.star_outline_rounded),
            size: size,
            color: c),
    ]);
  }
}

/// "See more" pill button (port of the Livewire load-more button).
class LoadMore extends StatelessWidget {
  const LoadMore(
      {super.key, required this.onPressed, this.loading = false, this.label = 'See More'});
  final VoidCallback? onPressed;
  final bool loading;
  final String label;
  @override
  Widget build(BuildContext context) {
    final skin = SkinScope.of(context);
    return Center(
      child: FilledButton(
        onPressed: loading ? null : onPressed,
        style: FilledButton.styleFrom(
            backgroundColor: skin.themeSlug == 'ecommet' ? const Color(0xFF222222) : skin.text,
            foregroundColor: Colors.white,
            padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 12),
            shape: const StadiumBorder()),
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          Text(loading ? 'Loading...' : label,
              style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
          if (!loading) ...[
            const SizedBox(width: 6),
            const Icon(Icons.keyboard_arrow_down, size: 20)
          ],
        ]),
      ),
    );
  }
}

/// Section heading used by rails / grids.
class SectionTitle extends StatelessWidget {
  const SectionTitle(this.title,
      {super.key, this.actionLabel, this.onAction, this.center = false, this.color});
  final String title;
  final String? actionLabel;
  final VoidCallback? onAction;
  final bool center;
  final Color? color;
  @override
  Widget build(BuildContext context) {
    final skin = SkinScope.of(context);
    final t = Text(title,
        textAlign: center ? TextAlign.center : TextAlign.start,
        style: TextStyle(
            color: color ?? skin.text,
            fontSize: skin.themeSlug == 'ecommet' ? 20 : 18,
            fontWeight: FontWeight.w800));
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
      child: center
          ? SizedBox(width: double.infinity, child: t)
          : Row(children: [
              Expanded(child: t),
              if (actionLabel != null)
                GestureDetector(
                    onTap: onAction,
                    child: Text(actionLabel!,
                        style: TextStyle(
                            color: skin.primary,
                            fontWeight: FontWeight.w600,
                            fontSize: 13))),
            ]),
    );
  }
}

String money(num? v) {
  if (v == null) return '';
  return v == v.roundToDouble() ? v.toStringAsFixed(0) : v.toStringAsFixed(2);
}

/// HH:MM:SS countdown to [endsAt] (ISO-8601). Ticks once per second.
class Countdown extends StatefulWidget {
  const Countdown(this.endsAt, {super.key, this.style, this.now});
  final String? endsAt;
  final TextStyle? style;

  /// Test hook.
  final DateTime Function()? now;
  @override
  State<Countdown> createState() => _CountdownState();
}

class _CountdownState extends State<Countdown> {
  Timer? _t;
  @override
  void initState() {
    super.initState();
    if (DateTime.tryParse(widget.endsAt ?? '') != null) {
      _t = Timer.periodic(const Duration(seconds: 1), (_) {
        if (mounted) setState(() {});
      });
    }
  }

  @override
  void dispose() {
    _t?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final end = DateTime.tryParse(widget.endsAt ?? '');
    var text = '--:--:--';
    if (end != null) {
      var d = end.difference((widget.now ?? DateTime.now)());
      if (d.isNegative) d = Duration.zero;
      String p(int n) => n.toString().padLeft(2, '0');
      text = '${p(d.inHours)}:${p(d.inMinutes % 60)}:${p(d.inSeconds % 60)}';
    }
    return Text(text, style: widget.style);
  }
}
