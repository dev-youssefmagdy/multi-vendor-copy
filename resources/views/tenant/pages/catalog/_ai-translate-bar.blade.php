{{--
    "Translate with AI" bar shown at the top of each non-source locale pane of the vendor product
    forms (own products + platform-catalog products). JS: resources/js/tenant/pages/catalog/product-ai-translate.js
    Expects: $language (this pane), $sourceLanguage, $aiTranslationEnabled, $product (nullable),
    $aiTranslationRemaining (AI calls left on the plan; null = unlimited). Each generation is paid
    (ProductAiTranslationService::chargeFor()).
--}}
@if($sourceLanguage && $language->code !== $sourceLanguage->code)
    <div class="t-ai-translate {{ $aiTranslationEnabled ? '' : 'is-locked' }}"
         data-ai-translate
         data-url="{{ route('tenant.products.ai-translate') }}"
         data-target-locale="{{ $language->code }}"
         data-source-locale="{{ $sourceLanguage->code }}"
         data-source-name="{{ $sourceLanguage->name }}"
         @if($product) data-product-id="{{ $product->id }}" @endif
         data-allowed="{{ $aiTranslationEnabled ? '1' : '0' }}"
         data-locked-message="{{ __('AI Translation is a paid add-on. Upgrade your plan to enable it.') }}">
        <div class="t-ai-translate-copy">
            <span class="t-ai-translate-title">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.9 4.6L18.5 9.5l-4.6 1.9L12 16l-1.9-4.6L5.5 9.5l4.6-1.9z"/><path d="M19 15l.8 1.9 1.9.8-1.9.8L19 20.4l-.8-1.9-1.9-.8 1.9-.8z"/></svg>
                {{ __('Translate with AI') }}
            </span>
            <span class="t-ai-translate-hint" data-ai-translate-hint>
                @if($aiTranslationEnabled)
                    {{ __('Fills the fields below from the :language text as a preview. Nothing is saved until you save the product.', ['language' => $sourceLanguage->name]) }}
                @else
                    {{ __('AI Translation is a paid add-on. Upgrade your plan to enable it.') }}
                @endif
            </span>
        </div>
        <div class="t-ai-translate-actions">
            @if($aiTranslationEnabled && ($aiTranslationRemaining ?? null) !== null)
                <span class="t-ai-translate-remaining" data-ai-translate-remaining>{{ __(':count AI calls left on your plan', ['count' => $aiTranslationRemaining]) }}</span>
            @endif
            <button type="button" class="btn btn-ghost btn-sm" data-ai-translate-undo hidden>{{ __('Undo') }}</button>
            <button type="button" class="btn btn-secondary btn-sm" data-ai-translate-run>{{ __('Translate with AI') }}</button>
        </div>
    </div>
@endif
