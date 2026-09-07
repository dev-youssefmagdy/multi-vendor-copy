@php
    $grouped = collect($topics)->groupBy('group', preserveKeys: true);
@endphp

<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">{{ $title }}</h1>
                <span class="page-badge">{{ $badge }}</span>
            </div>
            <p class="page-copy">{{ $description }}</p>
        </div>
    </div>

    <div class="form-grid section-gap" style="grid-template-columns: 280px 1fr; gap:24px;">
        <aside class="card fu d1"
            style="position:sticky; top:90px; align-self:flex-start; max-height:calc(100vh - 110px); overflow:auto;">
            @foreach ($grouped as $group => $items)
                <div class="eyebrow" style="margin-top:16px;">{{ $group }}</div>
                <ul style="list-style:none; padding:0; margin:8px 0 0;">
                    @foreach ($items as $key => $meta)
                        <li>
                            <a href="{{ route('admin.dev-docs.show', $key) }}" class="docs-nav-link"
                                style="display:flex; gap:8px; align-items:center; padding:8px 10px; border-radius:6px; text-decoration:none; color:{{ $topic === $key ? 'var(--cyan)' : 'var(--t2)' }}; opacity:1; background:{{ $topic === $key ? 'rgba(14,165,233,.08)' : 'transparent' }};">
                                <span>{{ $meta['icon'] }}</span>
                                <span style="font-size:13px;">{{ $meta['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </aside>

        <article class="card fu d2 docs-article" style="padding:32px 36px;">
            @includeIf($partial)
            @if (!view()->exists($partial))
                <div class="empty-state">
                    <div class="empty-state-title">Topic not found</div>
                    <p class="empty-state-copy">{{ $partial }} does not exist yet.</p>
                </div>
            @endif
        </article>
    </div>

    <style>
        #mn .page-copy,
        #mn .eyebrow {
            color: var(--t2);
            opacity: 1;
        }

        .docs-article h2 {
            font-size: 22px;
            margin: 24px 0 12px;
            color: var(--t1);
            opacity: 1;
        }

        .docs-article h3 {
            font-size: 16px;
            margin: 20px 0 8px;
            color: var(--t1);
            opacity: 1;
        }

        .docs-article p,
        .docs-article li {
            font-size: 14px;
            line-height: 1.7;
            color: var(--t2);
            opacity: 1;
        }

        .docs-article ul {
            padding-left: 20px;
        }

        .docs-article code {
            background: rgba(148, 163, 184, .12);
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 12px;
            color: var(--t1);
            opacity: 1;
        }

        .docs-article pre {
            background: #0f172a;
            padding: 14px 16px;
            border-radius: 6px;
            overflow: auto;
            font-size: 12px;
            color: #e2e8f0;
            border: 1px solid rgba(148, 163, 184, .18);
        }

        .docs-article table {
            width: 100%;
            border-collapse: collapse;
            margin: 14px 0;
            font-size: 13px;
        }

        .docs-article table th,
        .docs-article table td {
            padding: 8px 10px;
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            text-align: left;
            color: var(--t2);
        }

        .docs-article table th {
            color: var(--t1);
            opacity: 1;
            font-weight: 600;
        }

        .docs-article hr {
            border: none;
            border-top: 1px solid rgba(148, 163, 184, .18);
            margin: 24px 0;
        }

        .docs-article blockquote {
            border-left: 3px solid var(--cyan);
            padding: 8px 14px;
            background: rgba(14, 165, 233, .06);
            margin: 14px 0;
            color: var(--t2);
            opacity: 1;
            font-size: 13px;
        }
    </style>
</main>