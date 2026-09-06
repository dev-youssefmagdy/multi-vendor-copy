<main id="mn">
    <form wire:submit="save" class="page-stack">
        <div class="page-head fu d0">
            <div>
                <div class="eyebrow">Blog Posts</div>
                <h1 class="D page-title">{{ $pageTitle }}</h1>
                <p class="page-copy">Manage blog post content, category assignment, and publishing state.</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('admin.blog.posts') }}" class="btn btn-secondary">Back to Posts</a>
                <x-btn type="submit">Save Post</x-btn>
            </div>
        </div>

        <div class="grid gap-2">
            <x-card-collapse class="span-4" title="Post Settings" subtitle="Category, status, and publish date."
                :start-open="true">
                <div class="form-grid">
                    <div>
                        <label class="field-label">Category</label>
                        <x-select wire:model.defer="blogCategoryId">
                            @foreach ($categoryOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <div>
                        <label class="field-label">Status</label>
                        <x-select wire:model.defer="status">
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-select>
                        @error('status')<div class="text-red-500 text-sm mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="field-label">Published At</label>
                        <x-input type="datetime-local" wire:model.defer="publishedAt" />
                    </div>
                </div>
            </x-card-collapse>

            <x-card-collapse class="span-4" title="Featured Image" subtitle="Cover image shown in the blog listing and featured post card." :start-open="true">
                @if ($image)
                    <div class="media-preview-wrap">
                        <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="media-preview-image">
                    </div>
                @elseif ($existingImageUrl)
                    <div class="media-preview-wrap">
                        <img src="{{ $existingImageUrl }}" alt="Current image" class="media-preview-image">
                    </div>
                @endif
                <x-dropzone :multiple="false" id="blog-image" model="image" remove-action="removeImage" accept="image/*" />
                @error('image') <p class="field-error">{{ $message }}</p> @enderror
            </x-card-collapse>

            {{-- ── Translations ──────────────────────────────────────────────────── --}}
            <x-card-collapse title="Translations" subtitle="Localized title, URL slug, excerpt, and full content per language." class="form-card span-8" :start-open="true">

                @if ($languages->count())
                    <div class="locale-tabs">
                        @foreach ($languages as $language)
                            <button type="button"
                                class="locale-tab {{ $activeLocale === $language->code ? 'is-active' : '' }}"
                                wire:click="setActiveLocale('{{ $language->code }}')">
                                <span>{{ strtoupper($language->code) }}</span>
                                <small>{{ $language->native_name }}</small>
                            </button>
                        @endforeach
                    </div>

                    @foreach ($languages as $language)
                        <div class="locale-panel {{ $activeLocale === $language->code ? 'is-active' : '' }}">
                            <div class="form-grid form-grid-2">
                                <div class="span-2">
                                    <label class="field-label">Title ({{ strtoupper($language->code) }}) @if($language->is_default)*@endif</label>
                                    <x-input type="text"
                                        :class="$errors->has('translations.' . $language->code . '.title') ? 'is-invalid' : ''"
                                        wire:model.defer="translations.{{ $language->code }}.title"
                                        placeholder="Post title" />
                                    @error("translations.{$language->code}.title") <p class="field-error">{{ $message }}</p> @enderror
                                </div>

                                <div class="span-2">
                                    <label class="field-label">Slug ({{ strtoupper($language->code) }})</label>
                                    <x-input type="text"
                                        :class="$errors->has('translations.' . $language->code . '.slug') ? 'is-invalid' : ''"
                                        wire:model.defer="translations.{{ $language->code }}.slug"
                                        placeholder="auto-generated-from-title" />
                                    @error("translations.{$language->code}.slug") <p class="field-error">{{ $message }}</p> @enderror
                                </div>

                                <div class="span-2">
                                    <label class="field-label">Excerpt ({{ strtoupper($language->code) }})</label>
                                    <textarea rows="3"
                                        class="field-control field-textarea {{ $errors->has('translations.' . $language->code . '.excerpt') ? 'is-invalid' : '' }}"
                                        wire:model.defer="translations.{{ $language->code }}.excerpt"
                                        placeholder="Short summary shown in listing cards"></textarea>
                                    @error("translations.{$language->code}.excerpt") <p class="field-error">{{ $message }}</p> @enderror
                                </div>

                                <div class="span-2">
                                    <label class="field-label">Content ({{ strtoupper($language->code) }})</label>
                                    <x-editor
                                        model="translations.{{ $language->code }}.content"
                                        :value="$translations[$language->code]['content'] ?? ''"
                                        :height="500" />
                                    @error("translations.{$language->code}.content") <p class="field-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="notice-muted">Create at least one active language before managing translated post content.</div>
                @endif

            </x-card-collapse>
        </div>

        {{-- ── Bottom Actions ───────────────────────────────────────────────── --}}
        <div class="page-actions" style="display:flex;justify-content:flex-end;gap:8px;padding-top:8px">
            <a href="{{ route('admin.blog.posts') }}" class="btn btn-secondary">Back to Posts</a>
            <x-btn type="submit">Save Post</x-btn>
        </div>
    </form>
</main>
