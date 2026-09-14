<button type="button" class="btn btn-sm btn-primary" data-translation-ai
        data-translation-key="{{ $row['key'] }}"
        @if(!$aiTranslationEnabled || $row['locked']) disabled @endif>
    AI
</button>
