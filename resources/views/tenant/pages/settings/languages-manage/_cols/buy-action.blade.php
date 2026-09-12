<button type="button" class="btn btn-primary btn-sm"
        data-buy-language
        data-language-id="{{ $language->id }}"
        data-language-name="{{ $language->name }}"
        data-language-code="{{ strtoupper($language->code) }}"
        data-language-price="{{ number_format((float) $language->price, 2) }}">
    Buy Language
</button>
