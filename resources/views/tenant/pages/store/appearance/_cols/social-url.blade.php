{{-- @var \App\Models\Tenant\SocialLink $link --}}
<a href="{{ e($link->url) }}" target="_blank" rel="noopener" class="panel-copy t-social-url">
    {{ e(\Illuminate\Support\Str::limit($link->url, 50)) }}
</a>
