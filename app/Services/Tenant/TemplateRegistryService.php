<?php

namespace App\Services\Tenant;

use App\Contracts\TemplateStrategy;
use App\Models\Tenant\Theme;
use App\Services\Tenant\Templates\EcommetTemplateStrategy;
use App\Services\Tenant\Templates\EloraTemplateStrategy;
use App\Services\Tenant\Templates\NexoTemplateStrategy;
use App\Services\Tenant\Templates\SouqifyTemplateStrategy;

/**
 * Resolves the active template strategy for the current tenant.
 *
 * To register a new template:
 *   TemplateRegistryService::register('my-slug', MyTemplateStrategy::class);
 *
 * Or extend $strategies before service boot by calling register() in a service provider.
 */
class TemplateRegistryService
{
    /** @var array<string, class-string<TemplateStrategy>> */
    private static array $strategies = [
        'ecommet' => EcommetTemplateStrategy::class,
        'elora' => EloraTemplateStrategy::class,
        'souqify' => SouqifyTemplateStrategy::class,
        'nexo' => NexoTemplateStrategy::class,
    ];

    private static string $fallback = 'ecommet';

    /**
     * A per-request override slug set by the preview middleware.
     * When non-null this bypasses the DB theme lookup.
     */
    private static ?string $forcedSlug = null;

    private static ?string $resolvedSlug = null;

    /**
     * Force a specific template slug for the current request (preview mode).
     */
    public static function force(string $slug): void
    {
        static::$forcedSlug = $slug;
        static::$resolvedSlug = null;
    }

    /**
     * Clear any forced slug (useful in tests).
     */
    public static function clearForce(): void
    {
        static::$forcedSlug = null;
        static::$resolvedSlug = null;
    }

    /**
     * Register a new template strategy class under a slug.
     * Call this from a service provider to add additional templates.
     */
    public static function register(string $slug, string $strategyClass): void
    {
        static::$strategies[$slug] = $strategyClass;
    }

    /**
     * All registered template slugs → class mappings.
     *
     * @return array<string, class-string<TemplateStrategy>>
     */
    public static function all(): array
    {
        return static::$strategies;
    }

    /**
     * Resolve the strategy for the currently active tenant theme.
     * Falls back to the 'ecommet' strategy if no theme is active or slug is unregistered.
     * A forced slug (set via `force()` by the preview middleware) takes priority.
     */
    public function active(): TemplateStrategy
    {
        static::$resolvedSlug ??= static::$forcedSlug
            ?? (Theme::query()->where('is_active', true)->value('slug') ?? static::$fallback);

        return $this->resolve(static::$resolvedSlug);
    }

    /**
     * Resolve a strategy by slug.
     */
    public function resolve(string $slug): TemplateStrategy
    {
        $class = static::$strategies[$slug] ?? static::$strategies[static::$fallback];

        return new $class();
    }
}
