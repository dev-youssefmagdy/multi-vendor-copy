<div class="form-grid form-grid-2">

    <div>
        <label class="field-label">Full Name <span class="field-required">*</span></label>
        <x-input wire:model.defer="fullName" placeholder="Full name" :error="$errors->has('fullName')" />
        @error('fullName') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="field-label">Email <span class="field-required">*</span></label>
        <x-input type="email" wire:model.defer="email" placeholder="customer@example.com" :error="$errors->has('email')" />
        @error('email') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="field-label">Phone</label>
        <x-input wire:model.defer="phone" placeholder="+1 555 000 0000" />
        @error('phone') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="field-label">Country</label>
        <x-select wire:model.live="countryId" :error="$errors->has('countryId')">
            <option value="">— select country —</option>
            @foreach ($countries as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </x-select>
        @error('countryId') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="field-label">City</label>
        <x-select wire:model.defer="cityId" :disabled="!$countryId" :error="$errors->has('cityId')">
            <option value="">— select city —</option>
            @foreach ($cities as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </x-select>
        @error('cityId') <p class="field-error">{{ $message }}</p> @enderror
        @if (!$countryId)
            <p class="field-hint">Select a country first</p>
        @elseif ($cities->isEmpty())
            <p class="field-hint">No cities available for this country</p>
        @endif
    </div>

    <div>
        <label class="field-label">Password <span class="{{ ($customerId ?? null) ? 'field-optional' : 'field-required' }}">{{ ($customerId ?? null) ? '(leave blank to keep)' : '*' }}</span></label>
        <x-input type="password" wire:model.defer="password" placeholder="Min 6 characters" autocomplete="new-password" :error="$errors->has('password')" />
        @error('password') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="field-label">Confirm Password</label>
        <x-input type="password" wire:model.defer="passwordConfirmation" placeholder="Repeat password" autocomplete="new-password" />
        @error('passwordConfirmation') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="span-2">
        <label class="field-label">Address</label>
        <x-input wire:model.defer="address" placeholder="Short freeform address" />
        @error('address') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="span-2">
        <x-checkbox wire:model.defer="active" label="Active account" />
    </div>

</div>
