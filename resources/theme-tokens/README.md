# Theme design tokens

`catalog.json` lists every color a tenant can change per **theme + home variant** (13 editions across ecommet, elora,
souqify) with a label, group, description, "affects" areas and the default value, plus each edition's default fonts.
It feeds *Tenant panel ▸ Store ▸ Appearance ▸ Colors & Fonts* (`App\Support\ThemeDesign\ThemeTokenCatalog`).

Defaults are the values the storefront renders **today**, so listing a token never changes a design until a tenant edits it.
Only tokens that are really used (`var(--token)` or a Tailwind utility) are listed.

## When to regenerate
A theme/variant gains, loses or renames a `:root` color variable, or you want different default fonts
(`VARIANTS` in `generate_catalog.py`).

## How
1. For each edition, make it the active one, load the storefront home page and dump its `:root` tokens
   (`inventory.js` needs `puppeteer-core` and Chrome):
   `node scripts/theme-tokens/inventory.js elora-v3 http://<tenant-domain>/ inventory/elora-v3.json`
   (file name = `<theme>-<variant key>`; the 13 names are the keys of `catalog.json`).
2. `python3 scripts/theme-tokens/generate_catalog.py inventory`
3. Review the diff. Human labels/descriptions live in `CUR`, edition blurbs and default fonts in `VARIANTS`.

## Rendering
`resources/views/storefront/partials/theme-color-overrides.blade.php` emits the tenant's changed colors as
`:root:root{…}` and the font for the current text direction. The "Default" Ecommet/Elora editions use Tailwind v3:
their palette colors read CSS variables (`resources/tailwind-v3/*.config.cjs`, `css-var-color.cjs`) with the original
value as fallback — rebuild with `npm run build` after editing those configs.
