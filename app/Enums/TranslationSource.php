<?php

namespace App\Enums;

/**
 * Tracks how a `translations` / `translation_overrides` row's value got there,
 * so AiTranslation jobs know what is safe to regenerate on the next run:
 *
 *  - Default: nothing has ever reviewed this field — it's a placeholder
 *    (usually the source-locale text copied verbatim, or blank). Always
 *    fair game to overwrite.
 *  - Ai / CentralCopy: written by an AiTranslation job, either translated
 *    directly or copied from the central catalog's own AI translation.
 *    Left alone by default (already reviewed once) but still considered
 *    machine-owned, not a vendor's editorial choice.
 *  - Manual: a vendor/admin typed this value on purpose. Translation jobs
 *    must never overwrite a Manual row.
 */
enum TranslationSource: string
{
    case Default = 'default';
    case Ai = 'ai';
    case CentralCopy = 'central_copy';
    case Manual = 'manual';
}
