<?php

namespace App\Http\Middleware;

use App\Helpers\TenantNavigation;
use App\Models\Tenant\PaymentGateway;
use App\Models\Tenant\Theme;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirects tenants away from pages that depend on an onboarding step
 * they haven't completed yet, sending them to the relevant setup tab.
 *
 * Usage: ->middleware('tenant.setup:theme') / ->middleware('tenant.setup:payment_gateway')
 * The parameter must match a key in self::STEPS.
 */
class SetupGuard
{
    /**
     * key => [condition, tab, page name, mandatory].
     * `mandatory` steps can't be skipped; non-mandatory ones can be dismissed for the session.
     */
    private const STEPS = [
        'theme' => [
            'check' => 'themeSelected',
            'tab' => 'theme',
            'page_name' => 'Products',
            'mandatory' => false,
        ],
        'payment_gateway' => [
            'check' => 'paymentGatewayConfigured',
            'tab' => 'payment_gateway',
            'page_name' => 'Orders',
            'mandatory' => false,
        ],
        'storefront' => [
            'check' => 'allCoreStepsComplete',
            'tab' => 'setup',
            'page_name' => 'Storefront',
            'mandatory' => true,
        ],
    ];

    public function handle(Request $request, Closure $next, string $step): Response
    {
        $definition = self::STEPS[$step] ?? null;

        if (!$definition || $this->{$definition['check']}()) {
            return $next($request);
        }

        if (!$definition['mandatory'] && $request->session()->get("setup_skip.{$step}")) {
            return $next($request);
        }

        if (!$definition['mandatory'] && $request->boolean('skip_setup')) {
            $request->session()->put("setup_skip.{$step}", true);

            return $next($request);
        }

        $message = "Please complete this step before accessing {$definition['page_name']}.";

        return redirect()
            ->route('tenant.onboarding', ['tab' => 'setup', 'item' => $definition['tab']])
            ->with($definition['mandatory'] ? 'setup_error' : 'setup_warning', $message)
            ->with('setup_return_url', $request->fullUrl());
    }

    private function themeSelected(): bool
    {
        return Theme::query()->where('is_active', true)->exists();
    }

    private function paymentGatewayConfigured(): bool
    {
        return PaymentGateway::query()->where('is_active', true)->whereNotNull('connection_status')->exists();
    }

    private function allCoreStepsComplete(): bool
    {
        return TenantNavigation::logoIsConfigured()
            && $this->themeSelected()
            && $this->paymentGatewayConfigured()
            && TenantNavigation::profileComplete()
            && TenantNavigation::storeDetailsComplete()
            && TenantNavigation::complianceComplete();
    }
}
