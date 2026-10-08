{{-- ── Footer ────────────────────────────────────────────
  $footerText        — string (HTML)
  $footerCopyright   — string
  $socialLinks       — Collection<SocialLink> (->url, ->icon enum)
─────────────────────────────────────────────────────── --}}
<footer>
    @if ($footerText)
        <div class="footer-text">{!! $footerText !!}</div>
    @endif

    @if ($socialLinks->isNotEmpty())
        <div class="social-links">
            @foreach ($socialLinks as $link)
                <a href="{{ $link->url }}" target="_blank" rel="noopener">{{ $link->icon?->value ?? $link->icon }}</a>
            @endforeach
        </div>
    @endif

    @if ($footerCopyright)
        <p class="footer-copyright">{{ $footerCopyright }}</p>
    @endif
</footer>
