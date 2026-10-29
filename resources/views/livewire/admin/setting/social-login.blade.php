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
            <x-btn type="submit" form="social-login-form">{{ $submitLabel }}</x-btn>
        </div>
    </div>

    <form id="social-login-form" wire:submit="save" class="section-gap page-stack">
        @foreach ($providers as $key => $p)
            <section class="card form-card fu d1" wire:key="provider-{{ $key }}">
                <div class="panel-head mb-5">
                    <div>
                        <h3 class="panel-title">{{ $p['name'] }} Sign-In</h3>
                        <p class="panel-copy">Credentials used for login and registration on the central site, owner panel and all storefronts.</p>
                    </div>
                    <x-badge :color="$p['state'][1]">{{ $p['state'][0] }}</x-badge>
                </div>

                <div class="form-grid form-grid-2">
                    <div style="grid-column: 1 / -1">
                        <x-checkbox wire:model.defer="{{ $key }}Enabled" label="Enable {{ $p['name'] }} sign-in" />
                    </div>
                    <div>
                        <label class="field-label">{{ $p['idLabel'] }}</label>
                        <x-input type="text" wire:model.defer="{{ $key }}ClientId" :error="$errors->has($key.'ClientId')" />
                        @error($key.'ClientId')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="field-label">
                            {{ $p['secretLabel'] }}
                            @if ($p['hasSecret'])<span class="panel-copy" style="margin-left:6px">· stored ••••••••</span>@endif
                        </label>
                        <x-input type="password" autocomplete="new-password" placeholder="{{ $p['hasSecret'] ? 'Leave blank to keep current' : '' }}"
                            wire:model.defer="{{ $key }}ClientSecret" :error="$errors->has($key.'ClientSecret')" />
                        <p class="panel-copy" style="margin-top:6px">{{ $p['secretHelp'] }}</p>
                        @error($key.'ClientSecret')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="content-note-stack" style="margin-top:20px">
                    <div class="eyebrow">{{ $p['uriLabel'] }} — register these with {{ $p['name'] }}</div>
                    @foreach ($p['uris'] as $label => $uri)
                        <div class="content-note">
                            <strong>{{ $label }}</strong>
                            <div style="display:flex; gap:8px; align-items:center; margin-top:6px">
                                <input type="text" readonly value="{{ $uri }}" class="input" style="flex:1; font-family:monospace; font-size:12px"
                                    onclick="this.select()">
                                <x-btn type="button" variant="secondary"
                                    onclick="navigator.clipboard.writeText(@js($uri)); this.textContent='Copied'; setTimeout(() => this.textContent='Copy', 1500)">Copy</x-btn>
                            </div>
                        </div>
                    @endforeach
                </div>

                <details class="collapse-card" style="margin-top:20px">
                    <summary class="cursor-pointer select-none" style="font-weight:600; font-size:13px">
                        Setup guide — {{ $p['name'] }}
                    </summary>
                    <ol class="content-list" style="list-style:decimal; padding-left:20px; align-items:stretch">
                        @foreach ($p['steps'] as $step)
                            <li style="font-size:13px; color:var(--t2); display:list-item">{{ $step }}</li>
                        @endforeach
                    </ol>
                </details>
            </section>
        @endforeach
    </form>
</main>
