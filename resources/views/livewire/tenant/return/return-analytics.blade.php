<main id="mn">
    @php
        $cards = $cards ?? [];
        $chartSections = $chartSections ?? [];
        $tableSections = $tableSections ?? [];
        $cardsGridClass = $cardsGridClass ?? (count($cards) > 3 ? 'g-stats4' : 'g-stats3');
    @endphp

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

    @if ($cards)
        <div class="{{ $cardsGridClass }} section-gap">
            @foreach ($cards as $card)
                <div class="card {{ $card['glow'] ?? 'card-glow-cyan' }}">
                    <div class="stat-head">
                        <div>
                            <div class="eyebrow">{{ $card['label'] }}</div>
                            <div class="D stat-value">{{ $card['value'] }}</div>
                        </div>
                        <div class="mini-stat-dot {{ $card['dot'] ?? 'dot-cyan' }}"></div>
                    </div>
                    <p class="panel-copy">{{ $card['caption'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    @endif

    @foreach ($chartSections as $section)
        <div class="{{ $section['layoutClass'] ?? 'g-r2' }} section-gap">
            @foreach ($section['cards'] as $chartCard)
                <section class="card {{ $chartCard['wrapperClass'] ?? '' }}">
                    <div class="panel-head">
                        <div>
                            <h3 class="panel-title">{{ $chartCard['title'] }}</h3>
                            @if (!empty($chartCard['description']))
                                <p class="panel-copy">{{ $chartCard['description'] }}</p>
                            @endif
                        </div>
                    </div>

                    @if (!empty($chartCard['metrics']))
                        <div class="metric-list">
                            @forelse ($chartCard['metrics'] as $metric)
                                <div class="metric-row">
                                    <span class="metric-label">{{ $metric['label'] }}</span>
                                    <span class="metric-value">{{ $metric['value'] }}</span>
                                </div>
                            @empty
                                <div class="empty-state">
                                    <div class="empty-state-title">No data available</div>
                                </div>
                            @endforelse
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    @endforeach

    @if ($tableSections)
        @foreach ($tableSections as $section)
            <section class="card section-gap table-card-shell">
                <div class="table-header-shell">
                    <div>
                        <h3 class="panel-title">{{ $section['title'] }}</h3>
                        @if (!empty($section['description']))
                            <p class="panel-copy">{{ $section['description'] }}</p>
                        @endif
                    </div>
                </div>
                <x-table :headers="$section['headers']">
                    @forelse ($section['rows'] as $row)
                        <tr>
                            @foreach ($row as $cell)
                                <td>{!! $cell !!}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($section['headers']) }}">
                                <div class="empty-state">
                                    <div class="empty-state-title">No data available</div>
                                    <p class="empty-state-copy">Not enough return requests yet to populate this section.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </x-table>
            </section>
        @endforeach
    @endif
</main>
