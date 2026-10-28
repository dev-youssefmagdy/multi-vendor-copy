Role: Senior Flutter Dev. Read 00_MASTER_PLAN.md, 01_SCREEN_THEME_MATRIX.md. Implement every commerce screen from the matrix with shared
controllers/repositories and a `ThemeSkin` abstraction (cards, buttons, app bar, product card, price) so P5 skins plug in:
category/listing+filters+sort+pagination, product detail (variants, gallery, reviews, related), cart, coupon, checkout (addresses, shipping,
payment WebView, never fake success), auth (login/register/forgot/social stub), profile, addresses, favorites, orders list/detail/status/
tracking/cancel/reorder/return+detail, best-selling/new-in/full-star/offers/search+autocomplete, CMS pages, 404. RTL + localization,
loading/empty/error/offline states. Widget tests for key flows with mock repos. flutter analyze clean.
