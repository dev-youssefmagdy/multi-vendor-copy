import 'dart:async';
import 'package:flutter/material.dart';
import 'tokens.dart';

/// Hand-written shimmer (no dependency). Wrap placeholder boxes in [Shimmer].
class Shimmer extends StatefulWidget {
  const Shimmer({super.key, required this.child});
  final Widget child;
  @override
  State<Shimmer> createState() => _ShimmerState();
}

class _ShimmerState extends State<Shimmer> with SingleTickerProviderStateMixin {
  late final AnimationController _c =
      AnimationController(vsync: this, duration: const Duration(milliseconds: 1400))..repeat();
  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (MediaQuery.maybeOf(context)?.disableAnimations ?? false) {
      return Opacity(opacity: 0.7, child: widget.child);
    }
    return AnimatedBuilder(
      animation: _c,
      child: widget.child,
      builder: (_, child) => ShaderMask(
        blendMode: BlendMode.srcATop,
        shaderCallback: (r) => LinearGradient(
          begin: Alignment(-1 + _c.value * 3, 0),
          end: Alignment(_c.value * 3, 0),
          colors: const [Color(0xFFEDEDED), Color(0xFFF8F8F8), Color(0xFFEDEDED)],
          stops: const [0.1, 0.5, 0.9],
        ).createShader(r),
        child: child,
      ),
    );
  }
}

/// Grey rounded box used inside skeletons.
class SkeletonBox extends StatelessWidget {
  const SkeletonBox({super.key, this.width, this.height = 14, this.radius = 8});
  final double? width;
  final double height, radius;
  @override
  Widget build(BuildContext context) => Container(
      width: width,
      height: height,
      decoration: BoxDecoration(color: const Color(0xFFEDEDED), borderRadius: BorderRadius.circular(radius)));
}

/// Generic section skeleton: title line + a rail of cards.
class SectionSkeleton extends StatelessWidget {
  const SectionSkeleton({super.key, this.cards = 3, this.cardWidth = 150, this.height = 180});
  final int cards;
  final double cardWidth, height;
  @override
  Widget build(BuildContext context) => Shimmer(
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: Space.x3),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            const Padding(
                padding: EdgeInsets.symmetric(horizontal: Space.gutter),
                child: SkeletonBox(width: 140, height: 18)),
            const SizedBox(height: Space.x3),
            SizedBox(
              height: height,
              child: ListView(
                scrollDirection: Axis.horizontal,
                physics: const NeverScrollableScrollPhysics(),
                padding: const EdgeInsets.symmetric(horizontal: Space.gutter),
                children: [
                  for (var i = 0; i < cards; i++)
                    Padding(
                        padding: const EdgeInsetsDirectional.only(end: Space.x3),
                        child: SkeletonBox(width: cardWidth, height: height, radius: 12)),
                ],
              ),
            ),
          ]),
        ),
      );
}

/// Fades its child in once, replacing the abrupt pop-in of loaded content.
class FadeIn extends StatelessWidget {
  const FadeIn({super.key, required this.child, this.duration = Motion.fade});
  final Widget child;
  final Duration duration;
  @override
  Widget build(BuildContext context) => TweenAnimationBuilder<double>(
      tween: Tween(begin: 0, end: 1),
      duration: duration,
      curve: Motion.curve,
      builder: (_, v, c) => Opacity(opacity: v, child: c),
      child: child);
}

/// Empty state with icon, message and optional CTA.
class EmptyState extends StatelessWidget {
  const EmptyState(
      {super.key, required this.icon, required this.title, this.message, this.actionLabel, this.onAction});
  final IconData icon;
  final String title;
  final String? message;
  final String? actionLabel;
  final VoidCallback? onAction;
  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(Space.x8),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Container(
              width: 96,
              height: 96,
              decoration: BoxDecoration(color: cs.primary.withValues(alpha: 0.08), shape: BoxShape.circle),
              child: Icon(icon, size: 44, color: cs.primary)),
          const SizedBox(height: Space.x4),
          Text(title,
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: TypeScale.title, fontWeight: TypeScale.bold)),
          if (message != null) ...[
            const SizedBox(height: Space.x2),
            Text(message!, textAlign: TextAlign.center, style: const TextStyle(color: Color(0xFF6B6B6B))),
          ],
          if (actionLabel != null) ...[
            const SizedBox(height: Space.x5),
            FilledButton(
                style: FilledButton.styleFrom(minimumSize: const Size(kMinTouch * 2, kMinTouch)),
                onPressed: onAction,
                child: Text(actionLabel!)),
          ],
        ]),
      ),
    );
  }
}

/// Consistent error with retry.
class ErrorRetry extends StatelessWidget {
  const ErrorRetry({super.key, required this.onRetry, this.message = 'Something went wrong'});
  final VoidCallback onRetry;
  final String message;
  @override
  Widget build(BuildContext context) => EmptyState(
      icon: Icons.cloud_off_outlined, title: message, actionLabel: 'Retry', onAction: onRetry);
}

/// Status chip with AA-safe text color.
class StatusChip extends StatelessWidget {
  const StatusChip(this.label, {super.key, required this.color});
  final String label;
  final Color color;
  @override
  Widget build(BuildContext context) {
    final bg = color.withValues(alpha: 0.12);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: Space.x3, vertical: Space.x1),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(20)),
      child: Text(label,
          style: TextStyle(
              fontSize: TypeScale.caption,
              fontWeight: TypeScale.medium,
              color: Contrast.ensureAA(color, Color.alphaBlend(bg, Colors.white)))),
    );
  }
}

/// Banner shown while the device is offline.
class OfflineBanner extends StatelessWidget {
  const OfflineBanner({super.key, required this.offline});
  final bool offline;
  @override
  Widget build(BuildContext context) => AnimatedSize(
        duration: Motion.base,
        child: offline
            ? Semantics(
                liveRegion: true,
                child: Container(
                  width: double.infinity,
                  color: const Color(0xFF3B3B3B),
                  padding: const EdgeInsets.symmetric(vertical: Space.x2, horizontal: Space.gutter),
                  child: const Text("You're offline. Showing saved data.",
                      textAlign: TextAlign.center, style: TextStyle(color: Colors.white, fontSize: TypeScale.caption)),
                ))
            : const SizedBox(width: double.infinity),
      );
}

/// Ensures a tappable area of at least [kMinTouch] without changing the visual.
class MinTouch extends StatelessWidget {
  const MinTouch({super.key, required this.child, this.onTap, this.label});
  final Widget child;
  final VoidCallback? onTap;
  final String? label;
  @override
  Widget build(BuildContext context) => Semantics(
      button: true,
      label: label,
      child: InkResponse(
          onTap: onTap == null
              ? null
              : () {
                  Haptics.tap();
                  onTap!();
                },
          child: ConstrainedBox(
              constraints: const BoxConstraints(minWidth: kMinTouch, minHeight: kMinTouch),
              child: Center(widthFactor: 1, heightFactor: 1, child: child))));
}

/// Countdown chip (flash sale).
class CountdownChip extends StatelessWidget {
  const CountdownChip({super.key, required this.child, required this.color});
  final Widget child;
  final Color color;
  @override
  Widget build(BuildContext context) => Container(
      padding: const EdgeInsets.symmetric(horizontal: Space.x3, vertical: Space.x1),
      decoration: BoxDecoration(color: color, borderRadius: BorderRadius.circular(20)),
      child: FittedBox(fit: BoxFit.scaleDown, child: Row(mainAxisSize: MainAxisSize.min, children: [
        Icon(Icons.timer_outlined, size: 14, color: Contrast.on(color)),
        const SizedBox(width: Space.x1),
        DefaultTextStyle.merge(
            style: TextStyle(color: Contrast.on(color), fontWeight: TypeScale.medium, fontSize: TypeScale.caption),
            child: child),
      ])));
}

/// Debounce helper for search/suggestions.
class Debouncer {
  Debouncer([this.delay = const Duration(milliseconds: 300)]);
  final Duration delay;
  Timer? _t;
  void run(VoidCallback f) {
    _t?.cancel();
    _t = Timer(delay, f);
  }

  void dispose() => _t?.cancel();
}

/// Horizontal snap scrolling: settles on item boundaries ([extent] = item + gap).
class SnapPhysics extends ScrollPhysics {
  const SnapPhysics({required this.extent, super.parent});
  final double extent;
  @override
  SnapPhysics applyTo(ScrollPhysics? ancestor) => SnapPhysics(extent: extent, parent: buildParent(ancestor));

  double _target(ScrollMetrics m, double velocity) {
    var page = m.pixels / extent;
    if (velocity > 300) {
      page = page.ceilToDouble();
    } else if (velocity < -300) {
      page = page.floorToDouble();
    } else {
      page = page.roundToDouble();
    }
    return (page * extent).clamp(m.minScrollExtent, m.maxScrollExtent);
  }

  @override
  Simulation? createBallisticSimulation(ScrollMetrics position, double velocity) {
    if ((velocity <= 0 && position.pixels <= position.minScrollExtent) ||
        (velocity >= 0 && position.pixels >= position.maxScrollExtent)) {
      return super.createBallisticSimulation(position, velocity);
    }
    final target = _target(position, velocity);
    if ((target - position.pixels).abs() < 0.5) return null;
    return ScrollSpringSimulation(spring, position.pixels, target, velocity, tolerance: toleranceFor(position));
  }

  @override
  bool get allowImplicitScrolling => false;
}
