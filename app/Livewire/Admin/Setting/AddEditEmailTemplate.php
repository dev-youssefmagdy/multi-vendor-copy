<?php

namespace App\Livewire\Admin\Setting;

use App\Enums\ActivationStatus;
use App\Enums\EmailTemplateAction;
use App\Enums\EmailTemplateType;
use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Models\EmailTemplate;
use App\Models\Language;
use App\Services\EmailTemplateService;
use App\Services\Tenant\CentralCatalogTenantSyncService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AddEditEmailTemplate extends Component
{
    use AuthorizesAdminPermissions;

    public ?int $templateId = null;
    public string $name = '';
    public string $templateAction = '';
    public string $subject = '';
    public string $body = '';
    public string $templateType = 'admin';
    public string $status = 'active';

    /** @var array<string, array{subject: string, body: string}> Keyed by locale */
    public array $translations = [];

    public string $activeLocaleTab = '';

    public function mount(?EmailTemplate $emailTemplate = null): void
    {
        $this->authorizePermission('settings.email-templates.manage');

        if (!$emailTemplate?->exists) {
            $this->templateType = EmailTemplateType::Admin->value;
            $this->templateAction = EmailTemplateAction::defaultForType($this->templateType)?->value ?? '';
            $this->status = ActivationStatus::Active->value;

            return;
        }

        $this->templateId = $emailTemplate->id;
        $this->name = $emailTemplate->name;
        $this->templateAction = (string) ($emailTemplate->action ?? EmailTemplateAction::defaultForType($emailTemplate->type)?->value);
        $this->subject = $emailTemplate->subject;
        $this->body = $emailTemplate->body ?? '';
        $this->templateType = $emailTemplate->type->value;
        $this->status = $emailTemplate->status->value;

        $emailTemplate->load('translations');
        foreach ($emailTemplate->translations as $translation) {
            $this->translations[$translation->locale] = [
                'subject' => $translation->subject,
                'body' => $translation->body ?? '',
            ];
        }
    }

    public function updatedTemplateType(string $value): void
    {
        $defaultAction = EmailTemplateAction::defaultForType($value)?->value ?? '';

        if ($this->templateAction === '') {
            $this->templateAction = $defaultAction;

            return;
        }

        $selectedAction = EmailTemplateAction::tryFrom($this->templateAction);

        if (!$selectedAction || $selectedAction->templateType()->value !== $value) {
            $this->templateAction = $defaultAction;
        }
    }

    public function setActiveLocaleTab(string $locale): void
    {
        $this->activeLocaleTab = $locale;
    }

    public function save(EmailTemplateService $service)
    {
        $this->authorizePermission('settings.email-templates.manage');

        $templateId = $this->templateId;
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'templateAction' => ['required', Rule::enum(EmailTemplateAction::class), Rule::unique('email_templates', 'action')->ignore($templateId)],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'templateType' => ['required', Rule::enum(EmailTemplateType::class)],
            'status' => ['required', Rule::enum(ActivationStatus::class)],
            'translations.*.subject' => ['nullable', 'string', 'max:255'],
            'translations.*.body' => ['nullable', 'string'],
        ]);

        if (EmailTemplateAction::from($validated['templateAction'])->templateType()->value !== $validated['templateType']) {
            $this->addError('templateAction', 'Selected event does not belong to the chosen template type.');

            return;
        }

        $template = $service->save([
            'name' => $validated['name'],
            'action' => $validated['templateAction'],
            'subject' => $validated['subject'],
            'body' => $validated['body'] ?? null,
            'type' => $validated['templateType'],
            'status' => $validated['status'],
            'translations' => $this->translations,
        ], $templateId ? EmailTemplate::query()->findOrFail($templateId) : null);

        session()->flash('status', $templateId ? __('Email template updated successfully.') : __('Email template created successfully.'));

        return redirect()->route('admin.settings.email-templates.edit', $template);
    }

    public function syncToTenants(CentralCatalogTenantSyncService $syncService): void
    {
        $this->authorizePermission('settings.email-templates.manage');

        $syncService->syncAllTenants(['email-templates']);

        session()->flash('status', __('Email templates synced to all tenants successfully.'));
    }

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

        return view('livewire.admin.setting.add-edit-email-template', [
            'pageTitle' => $this->templateId ? __('Edit Email Template') : __('Add Email Template'),
            'pageDescription' => $this->templateId
                ? __('Update the template metadata, routing event, and message body for this central mail workflow.')
                : __('Create a new central email template for marketplace, billing, subscription, or tenant mail workflows.'),
            'templateTypeOptions' => ['admin' => 'Admin', 'tenant' => 'Tenant'],
            'templateActionOptions' => EmailTemplateAction::optionsForType($this->templateType),
            'statusOptions' => ['active' => 'Active', 'inactive' => 'Inactive'],
            'bodyContent' => $this->body,
            'languages' => $languages,
            'activeLocaleTab' => $this->activeLocaleTab,
        ]);
    }
}
