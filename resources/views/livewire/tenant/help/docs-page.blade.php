<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">Help & Documentation</h1>
                <span class="page-badge">Help</span>
            </div>
            <p class="page-copy">Guides for setting up and managing your store.</p>
        </div>
    </div>

    <div class="docs-layout">
        {{-- Sidebar nav --}}
        <nav class="docs-sidebar card">
            @php $categories = collect($articles)->groupBy('category'); @endphp
            @foreach ($categories as $cat => $items)
                <div class="docs-nav-group">
                    <div class="docs-nav-label">{{ $cat }}</div>
                    @foreach ($items as $slug => $article)
                        <a href="{{ route('tenant.help.article', $slug) }}"
                           class="docs-nav-link {{ $currentSlug === $slug ? 'docs-nav-link--active' : '' }}">
                            {{ $article['title'] }}
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>

        {{-- Article content --}}
        <article class="docs-content card">
            @if ($currentArticle)
                @include($currentArticle['view'])
            @else
                <p class="panel-copy">Article not found.</p>
            @endif
        </article>
    </div>
</main>

<style>
.docs-layout { display: grid; grid-template-columns: 240px 1fr; gap: 16px; align-items: start; }
@media (max-width: 768px) { .docs-layout { grid-template-columns: 1fr; } }
.docs-sidebar { padding: 16px; }
.docs-nav-group { margin-bottom: 20px; }
.docs-nav-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--t3); padding: 0 8px 6px; }
.docs-nav-link { display: block; padding: 8px 10px; border-radius: 8px; font-size: 13px; color: var(--t2); text-decoration: none; transition: background 0.15s; }
.docs-nav-link:hover { background: var(--surface-2); color: var(--t1); }
.docs-nav-link--active { background: var(--primary-faint, rgba(99,102,241,0.12)); color: var(--primary, #6366f1); font-weight: 600; }
.docs-content { padding: 32px; max-width: 760px; }
.docs-content h2 { font-size: 22px; font-weight: 800; color: var(--t1); margin: 0 0 8px; }
.docs-content h3 { font-size: 15px; font-weight: 700; color: var(--t1); margin: 24px 0 8px; }
.docs-content p { font-size: 14px; color: var(--t2); line-height: 1.7; margin: 0 0 14px; }
.docs-content ul { padding-left: 20px; margin: 0 0 14px; }
.docs-content li { font-size: 14px; color: var(--t2); line-height: 1.7; margin-bottom: 6px; }
.docs-content code { background: var(--surface-2); border-radius: 4px; padding: 2px 6px; font-size: 12px; font-family: monospace; color: var(--primary); }
.docs-step { display: flex; gap: 16px; margin-bottom: 20px; }
.docs-step-num { width: 28px; height: 28px; border-radius: 50%; background: var(--primary, #6366f1); color: #fff; font-size: 13px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px; }
.docs-step-body h3 { margin-top: 0; }
.docs-badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }
.docs-badge-pending  { background: rgba(245,158,11,0.15); color: #f59e0b; }
.docs-badge-approved { background: rgba(34,197,94,0.15);  color: #22c55e; }
.docs-badge-rejected { background: rgba(239,68,68,0.15);  color: #ef4444; }
</style>
