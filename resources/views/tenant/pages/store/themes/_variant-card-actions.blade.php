                                @if($theme['action_method'] === 'activateVariant')
                                    <button type="button" class="{{ $theme['action_class'] }}"
                                        data-action-url="{{ route('tenant.store.themes.variants.activate', ['theme' => $theme['theme_id'], 'variant' => $theme['variant_id']]) }}"
                                        data-action-method="POST"
                                        data-success="emit:tenant:setup-progress:refresh reload-page">
                                        {{ $theme['action_label'] }}
                                    </button>
                                @elseif($theme['action_method'] === 'activateTheme')
                                    <button type="button" class="{{ $theme['action_class'] }}"
                                        data-action-url="{{ route('tenant.store.themes.activate', $theme['theme_id']) }}"
                                        data-action-method="POST"
                                        data-success="emit:tenant:setup-progress:refresh reload-page">
                                        {{ $theme['action_label'] }}
                                    </button>
                                @elseif($theme['action_method'] === 'deactivateTheme')
                                    <button type="button" class="{{ $theme['action_class'] }}"
                                        data-action-url="{{ route('tenant.store.themes.deactivate', $theme['theme_id']) }}"
                                        data-action-method="POST"
                                        data-success="emit:tenant:setup-progress:refresh reload-page">
                                        {{ $theme['action_label'] }}
                                    </button>
                                @else
                                    <button type="button" class="{{ $theme['action_class'] }}" disabled>
                                        {{ $theme['action_label'] }}
                                    </button>
                                @endif

                                <a href="{{ $theme['preview_path'] }}" target="_blank" rel="noopener noreferrer" class="theme-pill-btn">Preview</a>

                                @if(!empty($theme['storefront_url']))
                                    <a href="{{ $theme['storefront_url'] }}" target="_blank" rel="noopener noreferrer" class="theme-pill-btn theme-pill-btn-live">View Live</a>
                                @endif
