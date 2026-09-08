<?php

use App\Models\HomeVariant;
use Illuminate\Database\Migrations\Migration;

/**
 * Backfills the curated default color palette for each home variant's `colors`
 * JSON column, sourced from the :root custom properties in that variant's CSS
 * file (resources/css/{theme}-{key}.css). This is what the tenant panel's
 * "reset to default" action restores and what renders when a tenant has made
 * no color overrides yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        $palettes = [
            'elora:v1' => [
                '--color-primary' => '#111827',
                '--color-secondary' => '#4b5563',
            ],
            'elora:v2' => [
                '--color-primary' => '#ff4d00',
                '--color-secondary' => '#ff522c',
                '--color-bg-main' => '#fdfdfd',
                '--color-text-primary' => '#121212',
                '--color-text-subtitle' => '#adadad',
                '--color-success' => '#2aaf2f',
                '--color-error' => '#de1709',
            ],
            'elora:v3' => [
                '--color-primary' => '#ff4d00',
                '--color-secondary' => '#ff522c',
                '--color-bg-main' => '#fdfdfd',
                '--color-text-primary' => '#121212',
                '--color-text-subtitle' => '#adadad',
                '--color-success' => '#2aaf2f',
                '--color-error' => '#de1709',
            ],
            'elora:v4' => [
                '--color-primary' => '#ff4d00',
                '--color-secondary' => '#ff522c',
                '--color-bg-main' => '#fdfdfd',
                '--color-text-primary' => '#121212',
                '--color-text-subtitle' => '#adadad',
                '--color-success' => '#2aaf2f',
                '--color-error' => '#de1709',
            ],
            'elora:v5' => [
                '--color-primary' => '#132092',
                '--color-secondary' => '#ff522c',
                '--color-bg-main' => '#fdfdfd',
                '--color-black' => '#121212',
                '--color-subtitle' => '#adadad',
                '--color-success' => '#2aaf2f',
                '--color-error' => '#de1709',
            ],
            'elora:v6' => [
                '--color-primary' => '#ff4d00',
                '--color-secondary' => '#ff522c',
                '--color-bg-main' => '#fdfdfd',
                '--color-text-primary' => '#121212',
                '--color-text-subtitle' => '#adadad',
                '--color-success' => '#2aaf2f',
                '--color-error' => '#de1709',
            ],
            'souqify:v1' => [
                '--color-primary' => '#111827',
                '--color-secondary' => '#4b5563',
            ],
            'souqify:v2' => [
                '--color-primary' => '#ff4d00',
                '--color-secondary' => '#ff522c',
                '--color-bg-main' => '#fdfdfd',
                '--color-text-primary' => '#121212',
                '--color-text-subtitle' => '#adadad',
                '--color-success' => '#2aaf2f',
                '--color-error' => '#de1709',
            ],
            'ecommet:v1' => [
                '--color-primary' => '#111827',
                '--color-secondary' => '#4b5563',
            ],
        ];

        foreach ($palettes as $identifier => $colors) {
            [$slug, $key] = explode(':', $identifier);

            HomeVariant::query()
                ->where('theme_slug', $slug)
                ->where('key', $key)
                ->whereNull('colors')
                ->update(['colors' => json_encode($colors)]);
        }

        // Variants without a matching CSS file yet (souqify v3-v6) reuse the
        // closest available palette so tenants still get working color pickers.
        HomeVariant::query()
            ->where('theme_slug', 'souqify')
            ->whereIn('key', ['v3', 'v4', 'v5', 'v6'])
            ->whereNull('colors')
            ->update(['colors' => json_encode($palettes['souqify:v2'])]);
    }

    public function down(): void
    {
        // Data backfill only; not reversed.
    }
};
