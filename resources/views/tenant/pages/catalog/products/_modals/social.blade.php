<x-tenant::modal id="product-social-modal" :title="__('Social Media Posts')" size="xl">
    <div class="social-modal" data-social-root>
        <div class="social-error" data-social-error hidden></div>

        <div class="social-controls">
            <div class="social-control">
                <label class="field-label" for="social-language">{{ __('Language') }}</label>
                <select id="social-language" class="field-control" data-social-language>
                    <option value="all">{{ __('All enabled languages') }}</option>
                </select>
            </div>
            <div class="social-control">
                <label class="field-label" for="social-include-image">{{ __('Image option') }}</label>
                <select id="social-include-image" class="field-control" data-social-include-image>
                    <option value="on">{{ __('Generate captions with image') }}</option>
                    <option value="off">{{ __('Generate captions only') }}</option>
                </select>
            </div>
        </div>

        <div class="social-platforms" data-social-platforms></div>

        <div class="social-generate-row">
            <p class="entity-subtitle" data-social-hint></p>
            <button type="button" class="btn btn-primary btn-sm" data-social-generate>{{ __('Generate') }}</button>
        </div>

        <div class="social-loading" data-social-loading hidden>
            <span class="t-spinner"></span>
            <p class="entity-subtitle">{{ __('Generating posts…') }}</p>
        </div>

        <div class="social-tabs" data-social-tabs></div>
        <div class="social-posts" data-social-posts></div>
        <div class="social-image" data-social-image hidden>
            <div class="social-image-header">
                <span class="entity-title">{{ __('AI-Generated Product Image') }}</span>
            </div>
            <img data-social-image-el alt="{{ __('AI-generated product image') }}">
        </div>
        <p class="entity-subtitle" data-social-empty hidden>{{ __('No posts generated yet. Click Generate above to get started.') }}</p>
    </div>
</x-tenant::modal>
