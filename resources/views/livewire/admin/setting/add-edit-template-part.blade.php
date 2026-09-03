<main id="mn">
    <form wire:submit="save" class="page-stack">
        <div class="page-head fu d0">
            <div>
                <div class="eyebrow">Template Parts</div>
                <h1 class="D page-title">{{ $isEditing ? 'Edit Template Part' : 'New Template Part' }}</h1>
                <p class="page-copy">
                    @if ($template)
                        {{ $isEditing ? 'Update this part for' : 'Add a new header or footer to' }}
                        <strong>{{ $template->name }}</strong>.
                    @endif
                </p>
            </div>
            <div class="page-actions">
                <a href="{{ route('admin.settings.template-parts', ['template' => $templateId]) }}"
                    class="btn btn-secondary">← Back</a>
                <x-btn type="submit">{{ $isEditing ? 'Save Changes' : 'Create Part' }}</x-btn>
            </div>
        </div>

        @if (session('status'))
            <div class="card section-gap notice-success">{{ session('status') }}</div>
        @endif

        <div class="grid gap-2">
            {{-- Part Details --}}
            <x-card-collapse class="span-4" title="Part Details"
                subtitle="Set the part type, name, and activation flags." :start-open="true">
                <div class="form-grid">
                    <div class="span-2">
                        <label class="field-label">Template</label>
                        <x-select wire:model.defer="templateId" :disabled="$isEditing">
                            @foreach ($templates as $tpl)
                                <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                            @endforeach
                        </x-select>
                        @error('templateId')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="span-2">
                        <label class="field-label">Type</label>
                        <x-select wire:model.defer="type" :disabled="$isEditing">
                            @foreach ($typeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-select>
                        @error('type')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="span-2">
                        <label class="field-label">Name</label>
                        <x-input type="text" wire:model.defer="name" placeholder="e.g. Dark Header" />
                        @error('name')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="span-2">
                        <label class="toggle-field">
                            <input type="checkbox" wire:model.defer="isActive">
                            <span>Active — visible to storefront</span>
                        </label>
                    </div>
                    <div class="span-2">
                        <label class="toggle-field">
                            <input type="checkbox" wire:model.defer="isDefault">
                            <span>Set as default for this type</span>
                        </label>
                    </div>
                </div>
            </x-card-collapse>

            {{-- Part Image --}}
            <x-card-collapse class="span-8" title="Part Image"
                subtitle="Optional image (e.g. logo or background) accessible as {{ '$part->image' }} in content."
                :start-open="true">
                <div class="page-stack">
                    @if ($imagePath && !$imageUpload)
                        <div style="display:flex;align-items:center;gap:12px;">
                            <img src="{{ $imagePath }}" alt="Current image"
                                style="max-height:80px;max-width:200px;object-fit:contain;border-radius:6px;border:1px solid var(--border2)">
                            <button type="button" class="btn btn-secondary btn-sm btn-danger"
                                wire:click="removeImage">Remove</button>
                        </div>
                    @endif
                    <x-dropzone id="part-image-upload" model="imageUpload" :multiple="false" accept="image/*"
                        label="{{ $imagePath && !$imageUpload ? 'Replace image' : 'Upload an image' }}"
                        sublabel="PNG, JPG, WebP — max 2 MB" />
                    @if ($imageUpload)
                        <p class="panel-copy" style="color:var(--green)">New image selected — will be saved on submit.</p>
                    @endif
                    @error('imageUpload')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </x-card-collapse>

            {{-- Part Content --}}
            <x-card-collapse class="span-12" title="Content"
                subtitle="Full Blade + PHP supported. Use {{ '$part->image' }} to reference the image above."
                :start-open="true">
                <div>
                    <x-editor model="content" :value="$content" :height="520" />
                    @error('content')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </x-card-collapse>
        </div>
    </form>
</main>