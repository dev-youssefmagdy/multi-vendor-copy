@if($shell['compliance']['required'])
    <x-tenant::modal id="tenant-compliance-modal" title="Review & Accept Compliance Documents" size="lg" static :closable="false" auto-open>
        <p class="panel-copy" style="margin-bottom:14px;">Before accessing your vendor panel, please read and accept the following documents.</p>

        @if($shell['compliance']['pages']->isNotEmpty())
            <div class="ob-compliance-pages">
                @foreach($shell['compliance']['pages'] as $cp)
                    <details class="ob-compliance-item">
                        <summary class="ob-compliance-summary">{{ $cp['title'] }}</summary>
                        <div class="ob-compliance-body prose prose-sm">
                            {!! $cp['content'] !!}
                        </div>
                    </details>
                @endforeach
            </div>
        @endif

        <x-tenant::form action="{{ route('tenant.compliance.accept') }}" method="POST"
            validate="{{ route('tenant.compliance.accept.validate') }}" success="redirect reload-page">
            <x-tenant::checkbox name="accept" required>
                I have read and agree to all the compliance documents listed above.
            </x-tenant::checkbox>

            <div class="page-actions compact-actions justify-end" style="margin-top:14px;">
                <x-tenant::submit>Accept &amp; Continue</x-tenant::submit>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
@endif
