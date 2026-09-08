<?php

namespace App\Livewire\Tenant\Setting;

use App\Enums\EmailTemplateAction;
use App\Models\Tenant\EmailTemplate;
use App\Models\Tenant\Language;
use App\Services\Tenant\TenantCatalogSyncService;
use App\Services\Tenant\TenantPanelService;
use Livewire\Attributes\Layout;
use Livewire\Component;

class AddEditEmailTemplate extends Component
{
    public int $templateId;
    public string $name = '';
    public string $action = '';
    public string $subject = '';
    public string $body = '';
    public bool $isActive = true;

    /** @var array<string, array{subject: string, body: string}> Keyed by locale */
    public array $translations = [];

    public string $activeLocaleTab = '';

    public function mount(EmailTemplate $emailTemplate): void
    {
        $this->templateId = $emailTemplate->id;
        $this->name = $emailTemplate->name;
        $this->action = (string) ($emailTemplate->action ?? '');
        $this->subject = $emailTemplate->subject;
        $this->body = $emailTemplate->body ?? '';
        $this->isActive = (bool) $emailTemplate->is_active;

        $emailTemplate->load('translations');
        foreach ($emailTemplate->translations as $translation) {
            $this->translations[$translation->locale] = [
                'subject' => $translation->subject,
                'body' => $translation->body ?? '',
            ];
        }
    }

    public function save(TenantPanelService $service)
    {
        $validated = $this->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'isActive' => ['boolean'],
            'translations.*.subject' => ['nullable', 'string', 'max:255'],
            'translations.*.body' => ['nullable', 'string'],
        ]);

        $template = $service->saveEmailTemplate([
            'subject' => $validated['subject'],
            'body' => $validated['body'] ?? null,
            'is_active' => $validated['isActive'] ?? false,
            'translations' => $this->translations,
        ], EmailTemplate::query()->findOrFail($this->templateId));

        session()->flash('status', __('Email template updated successfully.'));

        return redirect()->route('tenant.settings.email-templates.edit', $template);
    }

    public function resyncFromCentral(TenantCatalogSyncService $syncService): void
    {
        $tenant = tenant();

        if (!$tenant) {
            return;
        }

        $syncService->syncForTenant($tenant, ['email-templates']);

        // Reload the updated template data
        $template = EmailTemplate::query()->findOrFail($this->templateId);
        $this->subject = $template->subject;
        $this->body = $template->body ?? '';
        $this->isActive = (bool) $template->is_active;

        $template->load('translations');
        $this->translations = [];
        foreach ($template->translations as $translation) {
            $this->translations[$translation->locale] = [
                'subject' => $translation->subject,
                'body' => $translation->body ?? '',
            ];
        }

        session()->flash('status', __('Email template re-synced from central successfully.'));
    }

    public function setActiveLocaleTab(string $locale): void
    {
        $this->activeLocaleTab = $locale;
    }

    #[Layout('layouts.tenant')]
    public function render()
    {
        $languages = Language::query()
            ->where('is_active', true)
            ->orderBy('sort_order')->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        if ($this->activeLocaleTab === '' && $languages->isNotEmpty()) {
            $this->activeLocaleTab = $languages->first()->code;
        }

        foreach ($languages as $lang) {
            if (!isset($this->translations[$lang->code])) {
                $this->translations[$lang->code] = ['subject' => '', 'body' => ''];
            }
        }

        return view('livewire.tenant.setting.add-edit-email-template', [
            'pageTitle' => __('Edit Email Template'),
            'pageDescription' => __('Update the subject, body, and activation state for this tenant-specific email template.'),
            'actionLabel' => EmailTemplateAction::tryFrom($this->action)?->label() ?? $this->action,
            'bodyContent' => $this->body,
            'languages' => $languages,
            'activeLocaleTab' => $this->activeLocaleTab,
        ]);
    }
}
