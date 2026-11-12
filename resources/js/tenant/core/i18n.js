// Vendor-panel translations for JavaScript-rendered text.
// The dictionary is inlined by tenant/layouts/partials/js-i18n.blade.php from
// App\Support\Tenant\PanelJsTranslations::KEYS (values come from lang/<locale>.json).
// Add every new t('...') key to that KEYS list, otherwise it falls back to English.

/**
 * Translate a panel string. Placeholders use Laravel's `:name` syntax.
 * @param {string} key English source string (the lang/*.json key)
 * @param {Record<string, string|number>} [replacements]
 * @returns {string}
 */
export function t(key, replacements = {}) {
    const dict = window.__tenantI18n || {};
    let str = Object.prototype.hasOwnProperty.call(dict, key) ? dict[key] : key;

    Object.keys(replacements)
        .sort((a, b) => b.length - a.length)
        .forEach((name) => {
            str = str.split(`:${name}`).join(String(replacements[name] ?? ''));
        });

    return str;
}
