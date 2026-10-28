Role: Senior Mobile UX/UI Engineer (Flutter). Read docs/mobile/00_MASTER_PLAN.md and 01_SCREEN_THEME_MATRIX.md. Work in application/ only.
Goal: every screen and every home section looks and behaves like a professional NATIVE mobile app, not a shrunken website, while keeping each
theme/variant identity (colors, card style, radii, typography from ThemeSkin). Web layout is a reference for content/branding, NOT for flow.
Deliver a mobile design system in lib/design/ (spacing 4/8 grid, type scale, elevation, motion durations, haptics, min 48dp touch targets,
safe-area/notch/gesture-bar handling, text scaling up to 200%, contrast AA, semantics labels) consumed by ThemeSkin, then refactor screens:
- Navigation: bottom nav (Home, Categories, Cart w/ badge, Favorites/Offers, Account) driven by mobile settings nav items; collapsing/sliver app bars,
  search as a tappable pill opening a full-screen search with recents/suggestions; predictive back; no web-style mega headers/footers
  (footer info moves to Account/About + collapsible), drawer only if needed.
- Home sections: horizontal snap rails, peeking next card, page indicators, skeleton shimmer placeholders (no spinners), sticky/pinned category chips,
  flash-sale countdown chip, section headers with "See all"; smooth autoplay with pause on touch; image placeholders + blur/fade-in.
- Product list: 2-col grid default w/ list toggle, filter & sort in bottom sheets, active-filter chips, infinite scroll footer.
- Product detail: full-bleed swipeable gallery w/ zoom, sticky bottom bar (qty + Add to cart + Buy now), variant selectors as chips, sections as
  collapsible tiles, share + favorite actions, add-to-cart feedback (snackbar w/ view cart, haptic, badge bump).
- Cart/checkout: swipe-to-delete, qty steppers, sticky order summary + CTA, stepper checkout (address -> shipping/payment -> review), bottom-sheet pickers,
  coupon sheet, clear error/price-changed UX, success screen.
- Auth/account/orders/returns: clean forms (autofill hints, proper keyboards, inline validation, password toggle), order timeline stepper, status chips,
  pull-to-refresh everywhere, empty states with illustrations/CTAs, offline banner, consistent error retry.
- Dark mode support derived from tokens (with safe fallbacks) and RTL correctness (directional icons/padding), tablet/landscape responsive grids.
Rules: reuse shared widgets (do not fork per theme); per-variant differences stay in ThemeSkin; no new heavy deps (shimmer can be hand-written);
keep all tests green and add widget tests for key flows + golden-free layout tests at 320, 375, 430 widths and text scale 2.0 (no overflow).
Run flutter analyze + flutter test. Commit in logical chunks (design system, navigation/home, catalog/product, cart/checkout, account/orders, polish),
application/ paths only, messages ending 'Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>'. Report summary, tests, and anything left.
