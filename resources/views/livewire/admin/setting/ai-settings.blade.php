<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">{{ $title }}</h1>
                <span class="page-badge">{{ $badge }}</span>
            </div>
            <p class="page-copy">{{ $description }}</p>
        </div>
        <div class="page-actions">
            <x-btn type="button" variant="secondary" wire:click="testConnection" wire:loading.attr="disabled" wire:target="testConnection">
                <span wire:loading.remove wire:target="testConnection">Test connection</span>
                <span wire:loading wire:target="testConnection">Testing…</span>
            </x-btn>
            <x-btn type="submit" form="ai-settings-form">Save AI Settings</x-btn>
        </div>
    </div>

    @if (!empty($testResult))
        <div class="card form-card fu d1" style="border-color:{{ $testResult['ok'] ? 'var(--green)' : 'var(--red)' }}">
            <strong style="color:{{ $testResult['ok'] ? 'var(--green)' : 'var(--red)' }}">{{ $testResult['ok'] ? 'Connection OK' : 'Connection failed' }}</strong>
            <p class="panel-copy" style="margin-top:6px; word-break:break-word">{{ $testResult['message'] }}</p>
        </div>
    @endif

    <form id="ai-settings-form" wire:submit="save" class="section-gap page-stack">
        <section class="card form-card fu d1">
            <div class="panel-head mb-5">
                <div>
                    <h3 class="panel-title">OpenAI API Key</h3>
                    <p class="panel-copy">Used by all AI features. Stored encrypted; never shown again after saving.</p>
                </div>
                @if ($hasKey)
                    <x-badge :color="$keySource === 'database' ? 'green' : 'amber'">{{ $keySource === 'database' ? 'Stored in database' : 'Using .env fallback' }}</x-badge>
                @else
                    <x-badge color="red">Not configured</x-badge>
                @endif
            </div>

            <div class="form-grid form-grid-2">
                <div style="grid-column: 1 / -1">
                    <label class="field-label">
                        API key
                        @if ($hasKey)<span class="panel-copy" style="margin-left:6px">· current: <code>{{ $maskedKey }}</code></span>@endif
                    </label>
                    <x-input type="password" autocomplete="new-password" placeholder="{{ $hasKey ? 'Leave blank to keep the current key' : 'sk-...' }}"
                        wire:model.defer="apiKey" :error="$errors->has('apiKey')" />
                    @error('apiKey')<div class="field-error">{{ $message }}</div>@enderror
                    <p class="panel-copy" style="margin-top:6px">
                        Create it at platform.openai.com → API keys, in the <strong>same organization/project that has the credits</strong>.
                        A key from an unfunded project fails with "insufficient_quota" even if another project is funded.
                    </p>
                    @if ($keySource === 'database')
                        <x-btn type="button" variant="secondary" style="margin-top:10px" wire:click="removeKey"
                            wire:confirm="Remove the stored API key? AI features will fall back to the .env key (if any).">Remove stored key</x-btn>
                    @endif
                </div>
            </div>
        </section>

        <section class="card form-card fu d2">
            <div class="panel-head mb-5">
                <div>
                    <h3 class="panel-title">Endpoint &amp; Models</h3>
                    <p class="panel-copy">Defaults work for most accounts. A model your key cannot access makes that feature fail.</p>
                </div>
            </div>
            <div class="form-grid form-grid-2">
                @foreach ($fields as $f)
                    <div wire:key="ai-field-{{ $f['prop'] }}">
                        <label class="field-label">
                            {{ $f['label'] }}
                            @if ($f['source'] !== 'database')<span class="panel-copy" style="margin-left:6px">· {{ $f['source'] === 'env' ? 'from .env' : 'default' }}</span>@endif
                        </label>
                        <x-input type="text" wire:model.defer="{{ $f['prop'] }}" :error="$errors->has($f['prop'])" />
                        @error($f['prop'])<div class="field-error">{{ $message }}</div>@enderror
                        <p class="panel-copy" style="margin-top:6px">{{ $f['hint'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </form>
</main>
