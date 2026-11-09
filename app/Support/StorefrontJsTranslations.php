<?php

namespace App\Support;

/**
 * Strings the storefront JavaScript reads through window.trans(). Only these are
 * inlined into the page - shipping the whole lang/<locale>.json (~100KB, 1,700+
 * keys) on every storefront page was the largest part of each document.
 * Add a key here when new JS calls trans('...').
 */
class StorefrontJsTranslations
{
    public const KEYS = [
        '2Pay.js is still loading. Please try again.',
        'Accept.js is still loading. Please try again.',
        'Added to favorites!',
        'Card error',
        'Clear',
        'Close video',
        'Copied!',
        'Copy',
        'Ended',
        'Failed to add to cart',
        'Invalid phone number',
        'Item',
        'No products found for',
        'Out of stock',
        'Please enter a valid phone number.',
        'Please log in to add products to your favorites.',
        'Product added to cart successfully.',
        'Products',
        'Recent searches',
        'Removed from favorites.',
        'Searching…',
        'See all results for',
        'Show subcategories',
        'Stripe.js is still loading. Please try again in a moment.',
    ];

    /** @return array<string, string> */
    public static function forCurrentLocale(): array
    {
        $out = [];
        foreach (self::KEYS as $key) {
            $out[$key] = __($key);
        }

        return $out;
    }
}
