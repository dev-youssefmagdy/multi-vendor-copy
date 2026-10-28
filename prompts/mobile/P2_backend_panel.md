Role: Senior Laravel/Livewire dev. Read 00_MASTER_PLAN.md, tenant panel conventions (docs/tenant-panel-refactor.md, an existing store
settings page + ThemesController as pattern; routes/tenant_panel.php ~L586). Build two tenant-panel pages under Store:
(a) "Mobile App" settings (all app config from master plan §4, with validation, logo/splash uploads, nav items, feature toggles,
min version/maintenance, store links, deep-link info box showing the tenant's domain/code); (b) "App Themes": cards for every built-in
theme variant (ecommet, elora v1-v6, souqify v1-v6; EXCLUDE custom) with preview, activate, same UX as web Themes page; plus optional
color overrides. Add permissions (tenant.permission), sidebar entries, translation keys in ALL lang files used by the panel, help article.
Bump config_version on every save (CacheVersion). Tests for save/activate/permission. Report files.
