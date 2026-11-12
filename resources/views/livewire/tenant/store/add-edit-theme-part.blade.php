<main id="mn">
    <form wire:submit="save" class="page-stack">
        <div class="page-head fu d0">
            <div>
                <div class="eyebrow">{{ __('Theme Parts') }}</div>
                <h1 class="D page-title">{{ $isEditing ? __('Edit Theme Part') : __('New Theme Part') }}</h1>
                <p class="page-copy">
                    @if ($theme ?? null)
                        {{ $isEditing ? __('Update this part for') : __('Add a new header or footer to') }}
                        <strong>{{ $theme->name }}</strong>.
                    @endif
                </p>
            </div>
            <div class="page-actions">
                <a href="{{ route('tenant.store.theme-parts', ['theme' => $themeId]) }}" class="btn btn-secondary">{{ __('← Back') }}</a>
                <x-btn type="submit">{{ $isEditing ? __('Save Changes') : __('Create Part') }}</x-btn>
            </div>
        </div>

        @if (session('status'))
            <div class="card section-gap notice-success">{{ session('status') }}</div>
        @endif

        @if ($isEditing && $centralPartId)
            <div class="card section-gap" style="border-left:3px solid var(--amber);padding-left:12px;">
                <p class="panel-copy">
                    <strong>{{ __('Central-linked part.') }}</strong> {{ __('Saving will mark this part as customised — future central syncs will no longer overwrite it. Use') }} <em>{{ __('Revert') }}</em> {{ __('on the list page to undo.') }}
                </p>
            </div>
        @endif

        <div class="grid gap-2">
            {{-- Part Details --}}
            <x-card-collapse class="span-4" :title="__('Part Details')"
                :subtitle="__('Set the part type, name, and activation flags.')" :start-open="true">
                <div class="form-grid">
                    <div class="span-4">
                        <label class="field-label">{{ __('Theme') }}</label>
                        <x-select wire:model.defer="themeId" :disabled="$isEditing">
                            <option value="">{{ __('— Select Theme —') }}</option>
                            @foreach ($themes as $thm)
                                <option value="{{ $thm->id }}">{{ $thm->name }}</option>
                            @endforeach
                        </x-select>
                        @error('themeId')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="span-2">
                        <label class="field-label">{{ __('Type') }}</label>
                        <x-select wire:model.defer="type" :disabled="$isEditing">
                            @foreach ($typeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-select>
                        @error('type')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="span-2">
                        <label class="field-label">{{ __('Name') }}</label>
                        <x-input type="text" wire:model.defer="name" :placeholder="__('e.g. Dark Header')" />
                        @error('name')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="span-2">
                        <label class="toggle-field">
                            <input type="checkbox" wire:model.defer="isActive">
                            <span>{{ __('Active — visible to storefront') }}</span>
                        </label>
                    </div>
                    <div class="span-2">
                        <label class="toggle-field">
                            <input type="checkbox" wire:model.defer="isDefault">
                            <span>{{ __('Set as default for this type') }}</span>
                        </label>
                    </div>
                </div>
            </x-card-collapse>

            {{-- Part Image --}}
            <x-card-collapse class="span-8" :title="__('Part Image')"
                :subtitle="__('Optional image accessible as :image in your part content.', ['image' => '$part->image'])" :start-open="true">
                <div class="page-stack">
                    @if ($imagePath && !$imageUpload)
                        <div style="display:flex;align-items:center;gap:12px;">
                            <img src="{{ $imagePath }}" alt="{{ __('Current image') }}"
                                style="max-height:80px;max-width:200px;object-fit:contain;border-radius:6px;border:1px solid var(--border2)">
                            <button type="button" class="btn btn-secondary btn-sm btn-danger"
                                wire:click="removeImage">{{ __('Remove') }}</button>
                        </div>
                    @endif
                    <x-dropzone id="theme-part-image-upload" model="imageUpload" :multiple="false" accept="image/*"
                        label="{{ $imagePath && !$imageUpload ? __('Replace image') : __('Upload an image') }}"
                        :sublabel="__('PNG, JPG, WebP — max 2 MB')" />
                    @if ($imageUpload)
                        <p class="panel-copy" style="color:var(--green)">{{ __('New image selected — will be saved on submit.') }}</p>
                    @endif
                    @error('imageUpload')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </x-card-collapse>

            {{-- Part Content --}}
            <x-card-collapse class="span-12" :title="__('Content')"
                :subtitle="__('Full Blade + PHP supported. Use :image to reference the image above.', ['image' => '$part->image'])"
                :start-open="true">
                <div>
                    <x-editor model="content" :value="$content" :height="520" />
                    @error('content')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </x-card-collapse>
        </div>
    </form>
</main>