<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">{{ $title }}</h1>
                @if ($badge)
                    <span class="page-badge">{{ $badge }}</span>
                @endif
            </div>
            <p class="page-copy">{{ $description }}</p>
        </div>
        <div class="page-actions">
            <a class="btn btn-secondary" href="{{ route('tenant.support.index') }}">{{ __('← Tickets') }}</a>
        </div>
    </div>

    <form wire:submit="submit" class="page-stack section-gap">
        <section class="card form-card">
            <div class="panel-head mb-5">
                <div>
                    <h3 class="panel-title">{{ __('Ticket Details') }}</h3>
                    <p class="panel-copy">{{ __('Give as much context as possible so the admin team can help quickly.') }}</p>
                </div>
            </div>

            <div class="form-grid">
                <div>
                    <label class="field-label">{{ __('Subject') }}</label>
                    <x-input type="text" wire:model.defer="subject" :error="$errors->has('subject')" :placeholder="__('Short summary of your issue')" />
                    @error('subject')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="field-label">{{ __('Category') }}</label>
                    <x-select wire:model.defer="category" :error="$errors->has('category')">
                        @foreach ($categoryOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                    @error('category')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="field-label">{{ __('Priority') }}</label>
                    <x-select wire:model.defer="priority" :error="$errors->has('priority')">
                        @foreach ($priorityOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                    @error('priority')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-grid-full">
                    <label class="field-label">{{ __('Message') }}</label>
                    <x-textarea rows="6" wire:model.defer="body" :error="$errors->has('body')" :placeholder="__('Describe your issue in detail')" />
                    @error('body')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="page-actions compact-actions justify-end">
                <a class="btn btn-secondary" href="{{ route('tenant.support.index') }}">{{ __('Cancel') }}</a>
                <x-btn type="submit" wire:loading.attr="disabled" wire:target="submit">
                    <span wire:loading.remove wire:target="submit">{{ __('Submit Ticket') }}</span>
                    <span wire:loading wire:target="submit">{{ __('Submitting…') }}</span>
                </x-btn>
            </div>
        </section>
    </form>
</main>
