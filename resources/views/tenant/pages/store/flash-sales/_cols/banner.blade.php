@props(['flashSale'])

@if($flashSale->banner_url)
    <img src="{{ $flashSale->banner_url }}" alt="" style="height:40px;object-fit:cover;border-radius:6px" />
@else
    -
@endif
