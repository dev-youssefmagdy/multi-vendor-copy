<?php

namespace App\Services;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Renders header/footer parts that are stored in the database as Blade source.
 *
 * Storage format: the `content` column holds Blade/HTML exactly as a view file
 * would — so `{{ $var }}`, `@if`, `@foreach`, `@livewire(...)`, `route(...)`,
 * `__(...)`, and every built-in Laravel helper work the same as an on-disk
 * template.
 *
 * SECURITY NOTE
 * ─────────────
 * Blade allows arbitrary PHP, so editing a part effectively ships PHP code.
 * This is by design — the user asked for a "PHP engine" in parts — but the
 * edit UIs in this project are ONLY reachable by admins who already have
 * filesystem-level trust through the permission system. Never expose these
 * editors to end-customers.
 */
class TemplatePartRenderer
{
    /**
     * Render stored Blade source with the supplied variables.
     *
     * @param  array<string, mixed>  $data  Variables exposed to the snippet.
     */
    public function render(string $content, array $data = []): string
    {
        $content = (string) $content;

        if ($content === '') {
            return '';
        }

        try {
            // remove &amp; and other HTML entities that might have been double-encoded when stored
            $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5);
            return Blade::render($content, $data, deleteCachedView: true);
        } catch (Throwable $e) {
            // Don't let a broken part bring down the whole page. Log loudly so
            // operators notice, and render an HTML comment in its place.
            Log::error('Template part render failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);


            return sprintf(
                '<!-- template part render error: %s -->',
                e($e->getMessage())
            );
        }
    }
}
