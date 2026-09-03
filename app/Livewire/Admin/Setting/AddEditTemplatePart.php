<?php

namespace App\Livewire\Admin\Setting;

use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\Template;
use App\Models\TemplatePart;
use App\Services\TemplatePartService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

class AddEditTemplatePart extends Component
{
    use AuthorizesAdminPermissions;
    use InteractsWithAdminUi;
    use WithFileUploads;

    // ── Route / query params ──────────────────────────────────────────────────
    public ?int $partId = null;
    public ?int $templateId = null;

    #[Url(as: 'type', except: '')]
    public string $type = 'header';

    // ── Form fields ───────────────────────────────────────────────────────────
    public string $name = '';
    public string $content = '';
    public ?string $imagePath = null; // stored URL
    public $imageUpload = null; // temporary Livewire upload
    public bool $isActive = true;
    public bool $isDefault = false;

    public function mount(?int $templateId = null, ?int $partId = null): void
    {
        $this->authorizePermission('settings.templates.manage');

        if ($partId !== null) {
            // Edit mode — load from DB.
            $part = TemplatePart::with('template')->findOrFail($partId);
            $this->partId = $part->id;
            $this->templateId = $part->template_id;
            $this->type = $part->type;
            $this->name = $part->name;
            $this->content = $part->content ?? '';
            $this->imagePath = $part->image;
            $this->isActive = (bool) $part->is_active;
            $this->isDefault = (bool) $part->is_default;
            return;
        }

        // Create mode — templateId may come from the route/query, or be picked in the form below.
        if ($templateId !== null) {
            $this->templateId = $templateId;
        } else {
            $this->templateId = Template::where('is_default', true)->value('id')
                ?? Template::orderBy('name')->value('id');
        }

        if ($this->templateId) {
            Template::findOrFail($this->templateId);
        }
    }

    public function updatedImageUpload(): void
    {
        $this->validate(['imageUpload' => ['nullable', 'image', 'max:2048']]);
    }

    public function removeImage(): void
    {
        $this->imagePath = null;
        $this->imageUpload = null;
    }

    public function save(TemplatePartService $service)
    {
        $this->authorizePermission('settings.templates.manage');

        $this->validate([
            'templateId' => ['required', 'exists:templates,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:' . implode(',', TemplatePart::TYPES)],
            'content' => ['nullable', 'string'],
            'imageUpload' => ['nullable', 'image', 'max:2048'],
            'isActive' => ['boolean'],
            'isDefault' => ['boolean'],
        ]);

        // Handle new image upload.
        $imagePath = $this->imagePath;
        if ($this->imageUpload) {
            $stored = $this->imageUpload->store('template-parts/images', 'public');
            $imagePath = asset('storage/' . $stored);
        } elseif ($this->imagePath === null) {
            $imagePath = null;
        }

        $payload = [
            'name' => $this->name,
            'type' => $this->type,
            'content' => $this->content,
            'image' => $imagePath,
            'is_active' => $this->isActive,
            'is_default' => $this->isDefault,
        ];

        if ($this->partId) {
            $part = TemplatePart::findOrFail($this->partId);
            $service->update($part, $payload);
            session()->flash('status', 'Template part updated successfully.');
        } else {
            $template = Template::findOrFail($this->templateId);
            $service->create($template, $payload);
            session()->flash('status', 'Template part created successfully.');
        }

        return redirect()->route('admin.settings.template-parts', ['template' => $this->templateId]);
    }

    public function render()
    {
        $template = $this->templateId ? Template::find($this->templateId) : null;

        return view('livewire.admin.setting.add-edit-template-part', [
            'isEditing' => $this->partId !== null,
            'template' => $template,
            'templates' => Template::orderBy('name')->get(),
            'typeOptions' => array_combine(TemplatePart::TYPES, array_map('ucfirst', TemplatePart::TYPES)),
        ]);
    }
}
