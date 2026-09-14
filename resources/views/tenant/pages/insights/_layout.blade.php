@extends('tenant.layouts.app')

@section('title', $title)

@section('content')
    @php
        $cardsGridClass = $cardsGridClass ?? (count($cards ?? []) > 3 ? 'g-stats4' : 'g-stats3');
    @endphp

    @if($chartPayload ?? null)
        <script id="dashboard-chart-data" type="application/json">@json($chartPayload)</script>
    @endif

    <x-tenant::page-header :title="$title" :badge="$badge ?? null" :description="$description" />

    @if($cards ?? [])
        <x-tenant::stats-grid :stats="$cards" :columns="$cardsGridClass === 'g-stats4' ? 4 : 3" />
    @endif

    <div class="content-grid section-gap">
        <div class="card fu d1">
            <h3 class="panel-title">Tenant Scope</h3>
            <p class="panel-copy panel-copy-spaced">{{ $contentIntro }}</p>
            <div class="content-list">
                @foreach($bullets ?? [] as $bullet)
                    <div class="content-list-item">
                        <span class="dot dot-cyan"></span>
                        <span>{{ $bullet }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card fu d2">
            <h3 class="panel-title">Implementation Notes</h3>
            <div class="content-note-stack">
                <div class="content-note"><strong>Tenant only.</strong> This page works inside the current tenant database and vendor shell.</div>
                <div class="content-note"><strong>Next step.</strong> Connect additional charts, alerts, and approval flows as needed.</div>
            </div>
        </div>
    </div>

    @foreach($chartSections ?? [] as $section)
        <div class="{{ $section['layoutClass'] ?? 'g-r2' }} section-gap">
            @foreach($section['cards'] as $chartCard)
                <section class="card {{ $chartCard['wrapperClass'] ?? '' }}">
                    <div class="panel-head">
                        <div>
                            <h3 class="panel-title">{{ $chartCard['title'] }}</h3>
                            @if(!empty($chartCard['description']))
                                <p class="panel-copy">{{ $chartCard['description'] }}</p>
                            @endif
                        </div>
                    </div>

                    @if(!empty($chartCard['canvas']))
                        <canvas id="{{ $chartCard['canvas'] }}" @if(!empty($chartCard['height'])) height="{{ $chartCard['height'] }}" @endif></canvas>
                    @endif

                    @if(!empty($chartCard['legend']))
                        <div class="legend-list">
                            @foreach($chartCard['legend'] as $legend)
                                <div class="legend-row">
                                    <div class="legend-meta">
                                        <span class="dot {{ $legend['dot'] ?? 'dot-cyan' }}"></span>
                                        <span class="text-t2">{{ $legend['label'] }}</span>
                                    </div>
                                    <span class="legend-value">{{ $legend['value'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if(!empty($chartCard['metrics']))
                        <div class="metric-list">
                            @foreach($chartCard['metrics'] as $metric)
                                <div class="metric-row">
                                    <span class="metric-label">{{ $metric['label'] }}</span>
                                    <span class="metric-value">{{ $metric['value'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    @endforeach

    @foreach($tables ?? [] as $table)
        <x-tenant::datatable
            :id="$table['id']"
            :url="$table['url']"
            :columns="$table['columns']"
            :order="$table['order'] ?? []"
            :page-length="$table['pageLength'] ?? 10"
            :title="$table['title']"
            :description="$table['description'] ?? null"
            :paging="$table['paging'] ?? true"
        />
    @endforeach
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/insights/index.js')
@endpush
