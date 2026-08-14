<main id="mn">
<style>
    .pb-section-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border-radius: 12px;
        border: 1px solid var(--border2);
        background: rgba(255,255,255,0.02);
        margin-bottom: 8px;
        cursor: grab;
        transition: opacity 0.18s;
    }
    .pb-section-row.pb-hidden {
        opacity: 0.5;
    }
    .pb-section-label {
        flex: 1;
        font-size: 14px;
        font-weight: 500;
        color: var(--t1);
    }
    .pb-visibility-btn {
        border: none;
        background: transparent;
        color: var(--t2);
        cursor: pointer;
        display: flex;
        align-items: center;
        padding: 4px;
    }
</style>

    {{-- Page header --}}
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
    </div>

    {{-- Theme switcher --}}
    <div class="appearance-tabs fu d1 section-gap">
        @foreach ($themes as $theme)
            <button type="button"
                class="appearance-tab {{ $selectedThemeId === $theme->id ? 'act' : '' }}"
                wire:click="selectTheme({{ $theme->id }})">
                {{ $theme->name }}
            </button>
        @endforeach
    </div>

    <div class="card table-card-shell fu d2">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">Home Page Sections</h3>
                <p class="panel-copy">Drag to reorder sections. Click the eye icon to show or hide a section on the storefront.</p>
            </div>
        </div>

        <div id="page-builder-sections-sortable" wire:ignore.self>
            @forelse ($sections as $section)
                <div data-key="{{ $section['section_key'] }}"
                    class="sortable-row pb-section-row {{ $section['is_visible'] ? '' : 'pb-hidden' }}">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <line x1="4" y1="8" x2="20" y2="8" />
                        <line x1="4" y1="16" x2="20" y2="16" />
                    </svg>
                    <span class="pb-section-label">{{ $section['label'] }}</span>
                    <button type="button" class="pb-visibility-btn"
                        wire:click="toggleVisibility('{{ $section['section_key'] }}')"
                        title="{{ $section['is_visible'] ? 'Hide section' : 'Show section' }}">
                        @if ($section['is_visible'])
                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        @else
                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.6 18.6 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a18.6 18.6 0 0 1-2.16 3.19M14.12 14.12a3 3 0 1 1-4.24-4.24" />
                                <line x1="1" y1="1" x2="23" y2="23" />
                            </svg>
                        @endif
                    </button>
                </div>
            @empty
                <div class="empty-state">
                    <div class="empty-state-title">No sections available</div>
                    <p class="empty-state-copy">This theme has no registered Home page sections yet.</p>
                </div>
            @endforelse
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('livewire:init', () => {
                const list = document.getElementById('page-builder-sections-sortable');
                if (!list || typeof Sortable === 'undefined') {
                    return;
                }
                Sortable.create(list, {
                    animation: 150,
                    handle: '.sortable-row',
                    onEnd: () => {
                        const orderedKeys = Array.from(list.querySelectorAll('[data-key]')).map(row => row.dataset.key);
                        @this.call('updateOrder', orderedKeys);
                    },
                });
            });
        </script>
    @endpush

</main>
