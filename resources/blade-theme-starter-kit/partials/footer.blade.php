{{-- ── Footer ────────────────────────────────────────────
  $footerCopyright   — string
  $socialLinks       — Collection<SocialLink> (->url, ->icon)
─────────────────────────────────────────────────────── --}}
<footer>
    <p>{{ $footerCopyright ?? '' }}</p>
    @foreach ($socialLinks as $link)
        <a href="{{ $link->url }}" target="_blank" rel="noopener">{{ $link->icon?->name }}</a>
    @endforeach
</footer>
