@foreach($request->requested_translations as $locale => $fields)
    <div class="entity-subtitle" style="font-size:11px;text-transform:uppercase">{{ $locale }}</div>
    @if(isset($fields['name']))
        <div><span class="entity-subtitle">{{ __('Name:') }} </span><span style="font-size:12px">{{ \Illuminate\Support\Str::limit($fields['name'], 80) }}</span></div>
    @endif
    @if(isset($fields['description']))
        <div><span class="entity-subtitle">{{ __('Description:') }} </span><span style="font-size:12px">{{ \Illuminate\Support\Str::limit(strip_tags($fields['description']), 100) }}</span></div>
    @endif
@endforeach

@if($request->status->value === 'rejected' && $request->admin_note)
    <div class="notice-danger" style="margin-top:6px;padding:6px 10px;font-size:12px;">{{ __('Admin note: :adminnote', ['adminnote' => $request->admin_note]) }}</div>
@endif

@if($request->reviewed_at)
    <div class="entity-subtitle" style="font-size:11px;margin-top:4px">{{ __('Reviewed :diffForHumans', ['diffForHumans' => $request->reviewed_at->diffForHumans()]) }}</div>
@endif
