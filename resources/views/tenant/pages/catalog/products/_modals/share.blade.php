<x-tenant::modal id="product-share-modal" title="Share Product" size="lg">
    <div class="share-modal" data-share-root>
        <div class="share-preview">
            <img data-share-image class="share-preview-image" hidden alt="">
            <div class="share-preview-body">
                <div class="entity-title" data-share-title></div>
                <span class="badge badge-cyan share-ai-badge" data-share-ai-badge hidden>AI Caption</span>
                <p class="entity-subtitle share-caption" data-share-caption></p>
            </div>
        </div>

        <div class="share-url-row" data-share-url-row hidden>
            <code class="share-url" data-share-url></code>
            <x-tenant::copy value="" label="Copy Link" data-share-copy />
        </div>

        <div class="share-platforms" data-share-platforms></div>

        <p class="entity-subtitle" data-share-note></p>
    </div>
</x-tenant::modal>
