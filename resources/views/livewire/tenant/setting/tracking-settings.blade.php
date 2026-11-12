<main id="mn">

    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">{{ __('Tracking') }}</h1>
                <span class="page-badge">{{ __('Settings') }}</span>
            </div>
            <p class="page-copy">{{ __('Connect analytics and ad pixels so events fire automatically across your storefront.') }}</p>
        </div>
    </div>

    <section class="card form-card fu d1 gs-card">
        <div class="gs-section-head">
            <h3 class="panel-title">{{ __('Pixels & Analytics') }}</h3>
            <p class="panel-copy">
                {{ __('Leave a field blank to disable that integration. Once saved, the relevant base code and PageView/page_view events fire automatically on every storefront page, plus ViewContent, AddToCart, InitiateCheckout, and Purchase events at the matching storefront touchpoints.') }}
            </p>
        </div>

        <div class="form-grid gs-grid">
            <div>
                <label class="field-label">{{ __('Facebook Pixel ID') }}</label>
                <x-input type="text" :placeholder="__('e.g. 123456789012345')" wire:model.defer="fbPixelId" :error="$errors->has('fbPixelId')" />
                @error('fbPixelId')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="field-label">{{ __('TikTok Pixel ID') }}</label>
                <x-input type="text" :placeholder="__('e.g. CXXXXXXXXXXXXXXXXXXX')" wire:model.defer="tiktokPixelId" :error="$errors->has('tiktokPixelId')" />
                @error('tiktokPixelId')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="field-label">{{ __('Snapchat Pixel ID') }}</label>
                <x-input type="text" :placeholder="__('e.g. xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')" wire:model.defer="snapchatPixelId" :error="$errors->has('snapchatPixelId')" />
                @error('snapchatPixelId')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="field-label">{{ __('Google Analytics (GA4) Measurement ID') }}</label>
                <x-input type="text" :placeholder="__('G-XXXXXXXXXX')" wire:model.defer="gaMeasurementId" :error="$errors->has('gaMeasurementId')" />
                @error('gaMeasurementId')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="gs-actions">
                <x-btn type="button" wire:click="save">{{ __('Save tracking settings') }}</x-btn>
            </div>
        </div>
    </section>

</main>
