@extends('layouts.tenant')

@section('content')
<main id="mn">
    <x-tenant::page-header title="UI Kit" badge="QA" description="Every x-tenant:: component in every state. Local environment only.">
        <x-slot:actions>
            <button type="button" class="btn btn-secondary" id="ui-kit-rtl-toggle">Toggle RTL</button>
            <button type="button" class="btn btn-secondary" data-action="toggle-theme">Toggle theme</button>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::card title="Form building blocks">
        <x-tenant::form action="{{ route('tenant.ui-kit.store') }}" method="POST" validate="{{ route('tenant.ui-kit.validate') }}" success="none">
            <div class="form-grid">
                <x-tenant::input name="text" label="Text input" required placeholder="Type something" help="Blur to validate." />
                <x-tenant::input name="text_error" label="Text input (error state)" error value="" />
                <x-tenant::input name="text_disabled" label="Disabled" disabled value="Can't touch this" />
                <x-tenant::input name="password" type="password" label="Password" toggle />
                <x-tenant::input name="email" type="email" label="Email" required />
                <x-tenant::input name="slug" label="Slug" slug-from="text" />
                <x-tenant::input name="counted" label="Counter" maxlength="20" counter />
                <x-tenant::textarea name="textarea" label="Textarea" rows="3" counter maxlength="200" />
                <x-tenant::select name="select" label="Native select" required :options="['one' => 'One', 'two' => 'Two', 'three' => 'Three']" placeholder="Choose" />
                <x-tenant::select2 name="select2" label="Select2" :options="['one' => 'One', 'two' => 'Two', 'three' => 'Three']" placeholder="Choose" />
                <x-tenant::select2 name="select2_multi" label="Select2 (multiple)" multiple :options="['a' => 'Alpha', 'b' => 'Beta', 'c' => 'Gamma']" />
                <x-tenant::checkbox name="checkbox" label="Checkbox" />
                <x-tenant::switch name="switch" label="Switch (form field)" />
                <x-tenant::date name="date" label="Date" />
                <x-tenant::time name="time" label="Time" />
                <x-tenant::color name="color" label="Color" :swatches="['#06b6d4', '#8b5cf6', '#22c55e', '#f59e0b', '#ef4444']" />
                <x-tenant::phone name="phone" label="Phone" />
                <x-tenant::file name="file" label="File" />
                <x-tenant::image-upload name="image" label="Image upload" expected-width="600" expected-height="400" />
            </div>

            <x-tenant::checkbox-group name="checkbox_group" label="Checkbox group" select-all :options="['x' => 'X', 'y' => 'Y', 'z' => 'Z']" />
            <x-tenant::radio-group name="radio_group" label="Radio group" variant="cards" :options="[['value'=>'basic','label'=>'Basic','description'=>'Simple plan'],['value'=>'pro','label'=>'Pro','description'=>'Advanced plan']]" />

            <x-tenant::editor name="editor" label="Rich text" height="240" />

            <x-tenant::dropzone name="gallery" label="Drop images here" sublabel="PNG or JPG" multiple sortable />

            <x-tenant::locale-tabs :languages="$languages">
                <x-tenant::locale-pane code="en" dir="ltr" :active="true">
                    <x-tenant::input name="translations[en][name]" label="Name (English)" />
                </x-tenant::locale-pane>
                <x-tenant::locale-pane code="ar" dir="rtl">
                    <x-tenant::input name="translations[ar][name]" label="Name (Arabic)" />
                </x-tenant::locale-pane>
            </x-tenant::locale-tabs>

            <div class="page-actions compact-actions justify-end">
                <x-tenant::submit>Save demo form</x-tenant::submit>
            </div>
        </x-tenant::form>
    </x-tenant::card>

    <x-tenant::filters-card target="ui-kit-table" title="Filters">
        <x-tenant::input name="search" label="Search" placeholder="Search rows" />
        <x-tenant::select2 name="status" label="Status" :options="['active' => 'Active', 'pending' => 'Pending', 'rejected' => 'Rejected']" placeholder="All" />
    </x-tenant::filters-card>

    <x-tenant::datatable id="ui-kit-table" :url="route('tenant.ui-kit.data')" :columns="$columns" title="Demo records" quick-search selectable bulk-url="#" />

    <div class="g-stats3 section-gap">
        <x-tenant::card title="Badges">
            <x-tenant::badge color="cyan">Cyan</x-tenant::badge>
            <x-tenant::badge color="green">Green</x-tenant::badge>
            <x-tenant::badge color="amber">Amber</x-tenant::badge>
            <x-tenant::badge color="red">Red</x-tenant::badge>
            <x-tenant::badge color="violet">Violet</x-tenant::badge>
            <x-tenant::badge color="gray">Gray</x-tenant::badge>
        </x-tenant::card>

        <x-tenant::card title="Avatars">
            <x-tenant::avatar name="Ada Lovelace" />
            <x-tenant::avatar name="Grace Hopper" size="40" />
        </x-tenant::card>

        <x-tenant::card title="Alerts">
            <x-tenant::alert type="info" title="Heads up">Informational message.</x-tenant::alert>
            <x-tenant::alert type="success" title="Saved">Success message.</x-tenant::alert>
            <x-tenant::alert type="warning" title="Careful" dismissible>Warning message.</x-tenant::alert>
        </x-tenant::card>
    </div>

    <x-tenant::card title="Progress">
        <x-tenant::progress :value="42" label="Storage used" show-value />
    </x-tenant::card>

    <x-tenant::card title="Modal, dropdown, tabs">
        <button type="button" class="btn btn-primary" data-modal-open="ui-kit-modal">Open modal</button>

        <x-tenant::dropdown label="Actions">
            <x-tenant::dropdown-item>Edit</x-tenant::dropdown-item>
            <x-tenant::dropdown-item danger>Delete</x-tenant::dropdown-item>
        </x-tenant::dropdown>

        <x-tenant::tabs :tabs="['one' => 'Tab one', 'two' => 'Tab two']" mode="local">
            <x-tenant::tab-panel key="one" :active="true">Panel one content.</x-tenant::tab-panel>
            <x-tenant::tab-panel key="two">Panel two content.</x-tenant::tab-panel>
        </x-tenant::tabs>
    </x-tenant::card>

    <x-tenant::modal id="ui-kit-modal" title="Demo modal" size="md">
        <p class="panel-copy">Modal body content.</p>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" data-modal-close>Close</button>
        </x-slot:footer>
    </x-tenant::modal>
</main>
@endsection

{{-- The shared layout still loads the legacy app.css/app.js bundle; the
     switch to the tenant bundle happens in prompt 03. Until then, this page
     (and every other converted page) pulls the tenant bundle in explicitly. --}}
@push('styles')
    @vite(['resources/css/tenant/app.css'])
@endpush
@push('scripts')
    @vite(['resources/js/tenant/app.js', 'resources/js/tenant/pages/ui-kit.js'])
@endpush
