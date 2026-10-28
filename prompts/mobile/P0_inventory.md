Role: Business Analyst + Flutter lead. Read docs/mobile/00_MASTER_PLAN.md only. Task: produce
docs/mobile/01_SCREEN_THEME_MATRIX.md. Source: resources/views/themes/{ecommet,elora,souqify}, app/Livewire/Tenant/Storefront/*,
HomeVariantResolver, SectionRegistry, HomeVariant seed migrations. Do NOT write app code.
Must contain, exhaustively (grep file lists, don't read every blade fully; read one representative per distinct pattern):
1. Every page/screen per theme (home, category, product, cart, checkout, auth, profile, favorites, orders status/tracking/return/return-detail, best-selling, new-in, full-star, offers, search, CMS page, 404) incl. per-theme differences.
2. Every home variant (ecommet, elora v1-v6, souqify v1-v6): header variant, footer variant, ordered sections, per-section visual type, colors defaults.
3. Union of section types → one Flutter widget each with data source (existing query/endpoint) and config keys.
4. Settings that a tenant can change today (appearance, logo, colors, languages, currencies, features) → which become mobile bootstrap fields.
5. Gap list (backend endpoints needed per screen). End with a checklist table used by P7 to verify nothing is missed.
