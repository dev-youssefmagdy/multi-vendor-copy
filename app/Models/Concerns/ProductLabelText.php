<?php

namespace App\Models\Concerns;

use Carbon\Carbon;

/**
 * Text rendering of a product label (icon + text chip), shared by the central `ProductLabel`, the tenant copy
 * `Tenant\ProductLabel` and the merged central read `Tenant\Catalog\MergedProductLabel`. Needs `text` and
 * `translations` (locale => text) attributes.
 */
trait ProductLabelText
{
    /** Text in the current app locale, falling back to the default `text`. */
    public function localizedText(?string $locale = null): string
    {
        $translated = trim((string) data_get($this->translations, $locale ?? app()->getLocale()));

        return $translated !== '' ? $translated : (string) $this->text;
    }

    /** Replace {stock} / {delivery_date} for one product. */
    public function resolveText(int $stock): string
    {
        return strtr($this->localizedText(), [
            '{stock}' => $stock > 99 ? '+99' : (string) max(0, $stock),
            '{delivery_date}' => Carbon::now()->addDays(3)->translatedFormat('d F'),
        ]);
    }
}
