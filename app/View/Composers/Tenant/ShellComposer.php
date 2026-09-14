<?php

declare(strict_types=1);

namespace App\View\Composers\Tenant;

use App\Enums\Tenant\SubscriptionStatus;
use App\Helpers\TenantNavigation;
use App\Models\Package;
use App\Models\SupportTicket;
use App\Models\Tenant\AdminUser;
use App\Models\Tenant\Subscription;
use App\Models\Tenant\TenantNotification;
use App\Services\Tenant\ComplianceService;
use Illuminate\View\View;

final class ShellComposer
{
    private ?array $shell = null;

    public function __construct(private readonly ComplianceService $compliance)
    {
    }

    public function compose(View $view): void
    {
        $view->with('shell', $this->shell());
    }

    private function shell(): array
    {
        if ($this->shell !== null) {
            return $this->shell;
        }

        /** @var AdminUser|null $user */
        $user = auth('tenant')->user();
        $currentRoute = request()->route()?->getName();

        $currentTenantKey = tenant()?->getTenantKey();
        $supportUnread = $currentTenantKey
            ? tenancy()->central(fn () => SupportTicket::forTenant($currentTenantKey)->where('tenant_has_unread', true)->count())
            : 0;

        $plan = $this->plan();
        $onboardingProgress = TenantNavigation::onboardingSetupProgress();
        $setupProgress = TenantNavigation::setupProgress();

        $compliancePagesExist = $this->compliance->compliancePagesExist();
        $complianceRequired = $compliancePagesExist && !$this->compliance->hasAcceptedCurrentCompliance();

        $setupBanner = $user
            && $user->tour_seen_at !== null
            && $currentRoute !== 'tenant.onboarding'
            && !$complianceRequired
            && $onboardingProgress['done'] < $onboardingProgress['total'];

        return $this->shell = [
            'sections' => TenantNavigation::visibleSections(),
            'currentRoute' => $currentRoute,
            'routeInfo' => TenantNavigation::routeInfo($currentRoute),
            'supportUnread' => $supportUnread ?? 0,
            'onboardingProgress' => $onboardingProgress,
            'user' => $user,
            'role' => $user?->role?->name ?? 'Tenant Admin',
            'plan' => $plan,
            'unreadNotifications' => TenantNotification::unread()->count(),
            'setupProgress' => $setupProgress,
            'tenantName' => tenant('name'),
            'tenantId' => tenant('id'),
            'compliance' => [
                'required' => $complianceRequired,
                'pages' => $complianceRequired ? $this->compliance->compliancePages() : collect(),
            ],
            'setupBanner' => $setupBanner,
        ];
    }

    private function plan(): array
    {
        $activeSub = Subscription::query()
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trial->value])
            ->latest('end_date')
            ->first();

        if (!$activeSub) {
            return ['name' => null, 'expiry' => null, 'expired' => false];
        }

        $package = Package::find($activeSub->package_id);
        $expiry = $activeSub->end_date;

        return [
            'name' => $package?->name ?? 'Plan',
            'expiry' => $expiry,
            'expired' => (bool) ($expiry && $expiry->isPast()),
        ];
    }
}
