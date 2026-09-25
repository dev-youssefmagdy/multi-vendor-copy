{{--
    BACKEND TODO: sample cards shown while the store has no own products.
    Remove this include from own-products/index.blade.php once real data exists.
--}}

@php
    $photos = [
        'photo-1590874103328-eac38a683ce7', 'photo-1505740420928-5e560c06d30e', 'photo-1542291026-7eec264c27ff',
        'photo-1523275335684-37898b6baf30', 'photo-1608571423902-eed4a5ad8108', 'photo-1572635196237-14b3f281503f',
        'photo-1541643600914-78b084683601', 'photo-1606107557195-0e29a4b5b4aa', 'photo-1525966222134-fcfa99b8ae77',
        'photo-1543163521-1bf539c55dd2', 'photo-1524592094714-0f0654e20314', 'photo-1549298916-b41d501d3772',
    ];
    $idea = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 17.5c0-1.4.9-2.5 1.8-3.6A7 7 0 1 0 5 9.25c0 1.8.7 3.4 1.8 4.6.9 1.1 1.8 2.2 1.8 3.6"/><path d="M9 21.25h6M9.5 17.5h5"/><path d="M16.5 2.25l.4 1.1 1.1.4-1.1.4-.4 1.1-.4-1.1-1.1-.4 1.1-.4z"/></svg>';
@endphp

<div class="pm-grid fu d1">
    @foreach($photos as $photo)
        <article class="db-opp pm-card is-added">
            <div class="db-opp-media" style="background-image:url('https://images.unsplash.com/{{ $photo }}?w=800&q=70&auto=format&fit=crop')">
                <span class="db-opp-added">Already added</span>
                <span class="db-opp-cheaper">
                    <small>Cheaper than market by</small>
                    <strong>dummy</strong>
                </span>
            </div>

            <div class="db-opp-body">
                <div>
                    <h3 class="db-opp-title pm-card-title">Sports smart watch</h3>
                    <p class="db-opp-desc pm-card-desc">Rapid demand, easy advertising content, and a margin that leaves you with a strong competitive advantage.</p>
                </div>

                <div class="db-opp-stats pm-card-stats">
                    <div class="db-opp-stat is-dark is-half">
                        <span class="db-opp-stat-label">Cost to your customer's door</span>
                        <strong>dummy</strong>
                        <small>Product + International Shipping</small>
                    </div>
                    <div class="db-opp-stat">
                        <span class="db-opp-stat-label">Average Cheaper than market by</span>
                        <strong>dummy</strong>
                        <small>Through global stores</small>
                    </div>
                </div>

                <button type="button" class="btn btn-lg db-opp-cta is-added">
                    {!! $idea !!}
                    Create your Ad
                </button>
            </div>
        </article>
    @endforeach
</div>
