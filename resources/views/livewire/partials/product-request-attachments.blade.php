{{-- PANELS-UPDATES-PRODUCT-REQUESTS: image attachments render as thumbnails (click = full size); other files stay links.
     $files = [['url','name','image'], ...] built by App\Support\ProductRequestFiles. --}}
@if (!empty($files))
  <div style="display:flex;flex-wrap:wrap;gap:8px;{{ $style ?? '' }}">
    @foreach ($files as $file)
      @if ($file['image'])
        <a href="{{ $file['url'] }}" target="_blank" rel="noopener" title="{{ $file['name'] }}"
           style="display:block;width:132px;height:132px;border-radius:10px;overflow:hidden;border:1px solid rgba(128,128,128,.35);background:#0001;">
          <img src="{{ $file['url'] }}" alt="{{ $file['name'] }}" loading="lazy" style="width:100%;height:100%;object-fit:cover;display:block;">
        </a>
      @else
        <a href="{{ $file['url'] }}" target="_blank" rel="noopener"
           style="font-size:11px;padding:6px 10px;border-radius:8px;background:rgba(0,0,0,0.15);text-decoration:none;color:inherit;align-self:flex-start;">
          &#128206; {{ $file['name'] }}
        </a>
      @endif
    @endforeach
  </div>
@endif
