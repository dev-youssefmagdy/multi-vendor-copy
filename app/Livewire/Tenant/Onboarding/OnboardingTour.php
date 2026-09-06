<?php

namespace App\Livewire\Tenant\Onboarding;

use App\Helpers\TenantNavigation;
use App\Models\Tenant\AdminUser;
use App\Models\StaticPage;
use App\Models\Tenant as TenantModel;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OnboardingTour extends Component
{
    // ── Compliance acceptance state ──────────────────────────────────────────
    public bool $showCompliance = false;
    public bool $complianceChecked = false;

    // ── Persistent setup-progress banner state ─────────────────────────────
    public bool $showSetupBanner = false;

    public function mount(): void
    {
        /** @var AdminUser|null $admin */
        $admin = Auth::guard('tenant')->user();

        if (!$admin) {
            return;
        }

        // Check compliance first — must accept before anything else
        if ($this->compliancePagesExist() && !$this->hasAcceptedCurrentCompliance()) {
            $this->showCompliance = true;
            return;
        }

        $this->maybeRedirectToOnboarding($admin);

        $this->showSetupBanner = $admin->tour_seen_at !== null
            && !request()->routeIs('tenant.onboarding')
            && TenantNavigation::onboardingSetupProgress()['done'] < TenantNavigation::onboardingSetupProgress()['total'];
    }

    /**
     * First-run visitors get sent to the onboarding page once (tour, then
     * setup); afterwards it's reachable anytime from the sidebar instead of
     * being forced.
     */
    private function maybeRedirectToOnboarding(AdminUser $admin): void
    {
        if (request()->routeIs('tenant.onboarding')) {
            return;
        }

        // Force the tour once — after that the admin can navigate freely.
        // The setup checklist is reachable from the sidebar "Get Started" link;
        // forcing it here blocks navigation from the setup action buttons themselves.
        if ($admin->tour_seen_at === null) {
            $this->redirectRoute('tenant.onboarding', ['tab' => 'tour'], navigate: true);
        }
    }

    // ── Compliance acceptance ────────────────────────────────────────────────

    public function acceptCompliance(): void
    {
        if (!$this->complianceChecked) {
            $this->dispatch('admin-toast', message: 'Please confirm you have read and accept the compliance documents.', type: 'error');
            return;
        }

        $version = $this->complianceVersion();

        TenantModel::saveData(tenant('id'), [
            'compliance_accepted_at' => now(),
            'compliance_version' => $version,
        ]);

        $this->showCompliance = false;
        $this->complianceChecked = true;

        /** @var AdminUser|null $admin */
        $admin = Auth::guard('tenant')->user();
        if ($admin) {
            $this->maybeRedirectToOnboarding($admin);
        }
    }

    /** Returns active compliance pages for display. */
    public function compliancePages(): \Illuminate\Support\Collection
    {
        return tenancy()->central(
            fn() => StaticPage::query()
                ->where('status', 'active')
                ->where('is_compliance', true)
                ->orderBy('id')
                ->get(['id', 'slug', 'updated_at'])
                ->map(fn(StaticPage $p) => [
                    'id' => $p->id,
                    'title' => $p->translationValue('title') ?? $p->slug,
                    'content' => $p->translationValue('content') ?? '',
                    'slug' => $p->slug,
                ])
        ) ?? collect();
    }

    /** True if any active compliance pages exist. */
    public function compliancePagesExist(): bool
    {
        return tenancy()->central(
            fn() => StaticPage::query()
                ->where('status', 'active')
                ->where('is_compliance', true)
                ->exists()
        ) ?? false;
    }

    /** Deterministic version string — hash of IDs + latest updated_at. */
    public function complianceVersion(): string
    {
        $rows = tenancy()->central(
            fn() => StaticPage::query()
                ->where('status', 'active')
                ->where('is_compliance', true)
                ->orderBy('id')
                ->get(['id', 'updated_at'])
                ->map(fn(StaticPage $p) => $p->id . ':' . $p->updated_at?->timestamp)
                ->implode('|')
        ) ?? '';

        return md5($rows);
    }

    /** True if the tenant has accepted the current compliance version. */
    public function hasAcceptedCurrentCompliance(): bool
    {
        $currentVersion = $this->complianceVersion();

        $accepted = TenantModel::query()->where('id', tenant('id'))->first()?->compliance_version;
        return $accepted === $currentVersion;
    }

    public function render()
    {
        $progress = TenantNavigation::onboardingSetupProgress();

        return view('livewire.tenant.onboarding.tour', [
            'compliancePagesList' => $this->showCompliance ? $this->compliancePages() : collect(),
            'setupDone' => $progress['done'],
            'setupTotal' => $progress['total'],
            'setupPct' => $progress['total'] > 0 ? (int) round(($progress['done'] / $progress['total']) * 100) : 100,
        ]);
    }
}
