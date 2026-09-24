@extends('tenant.layouts.app')

@section('title', 'UI Kit')

@section('content')
    <x-tenant::page-header title="UI Kit" badge="QA" description="Every x-tenant:: component in every state. Local environment only.">
        <x-slot:actions>
            <button type="button" class="btn btn-secondary" id="ui-kit-rtl-toggle">Toggle RTL</button>
            <button type="button" class="btn btn-secondary" data-action="toggle-theme">Toggle theme</button>
        </x-slot:actions>
    </x-tenant::page-header>

    @php
        $zap = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"><path d="M13.5 2.25L4.75 13.5h7l-1.25 8.25L19.25 10.5h-7l1.25-8.25z"/></svg>';
        $play = '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M7 4.5v15l12-7.5-12-7.5z"/></svg>';
    @endphp

    <x-tenant::card title="Design system — buttons">
        <div class="ds-kit-grid">
            @foreach(['primary' => 'Default', 'secondary' => 'Secondary'] as $variant => $caption)
                @foreach([null => 'Small', 'md' => 'Mid', 'lg' => 'Large'] as $size => $sizeLabel)
                    <x-tenant::btn :variant="$variant" :size="$size ?: null" :icon="$play">Button</x-tenant::btn>
                @endforeach
            @endforeach
            @foreach([null, 'md', 'lg'] as $size)
                <x-tenant::btn :size="$size" :icon="$play" disabled>Button</x-tenant::btn>
            @endforeach
            <x-tenant::btn variant="ghost">Ghost</x-tenant::btn>
            <x-tenant::btn variant="danger">Danger</x-tenant::btn>
            <x-tenant::btn size="sm">Compact (tables)</x-tenant::btn>
        </div>
        <p class="t-help">Hover and press a button to see its hover (#E66504) and pressed (#8B3D02) states.</p>

        <div class="ds-kit-grid ds-kit-tiles">
            <x-tenant::btn variant="tile" :icon="$zap">Add to Flash Sale</x-tenant::btn>
            <x-tenant::btn variant="tile" :icon="$zap" class="btn-tile-soft">Add to Flash Sale</x-tenant::btn>
            <x-tenant::btn variant="tile" :icon="$zap" aria-pressed="true">Add to Flash Sale</x-tenant::btn>
        </div>
    </x-tenant::card>

    <div class="g-stats3 section-gap">
        <x-tenant::card title="Design system — switch">
            <x-tenant::switch name="ds_switch_off" label="Off" />
            <x-tenant::switch name="ds_switch_on" label="On" :checked="true" />
        </x-tenant::card>

        <x-tenant::card title="Design system — status &amp; trend">
            <div class="ds-kit-stack">
                <x-tenant::status-badge status="delivered" />
                <x-tenant::status-badge status="processing" />
                <x-tenant::status-badge status="pending" />
                <x-tenant::status-badge status="shipped" />
            </div>
            <div class="ds-kit-stack">
                <x-tenant::trend :value="12" />
                <x-tenant::trend :value="-12" />
            </div>
        </x-tenant::card>

        <x-tenant::card title="Design system — dropdown">
            <x-tenant::select-menu name="ds_period" value="today" icon="calendar" label="Period"
                :options="['today' => 'Today', 'weekly' => 'weekly', 'monthly' => 'Monthly', 'yearly' => 'yearly']" />
        </x-tenant::card>
    </div>

    <x-tenant::card title="Design system — inputs">
        <div class="form-grid">
            <x-tenant::select name="ds_product" label="Product name" required placeholder="Ex, Nike shoes" :options="['nike' => 'Nike shoes', 'adidas' => 'Adidas']" />
            <div dir="rtl">
                <x-tenant::select name="ds_product_ar" label="اسم المنتج" required placeholder="مثال: احذية" :options="['shoes' => 'احذية']" />
            </div>
            <x-tenant::select name="ds_product_active" label="Product name (active)" required placeholder="Ex, Nike shoes" class="is-active" :options="['nike' => 'Nike shoes']" />
            <div dir="rtl">
                <x-tenant::select name="ds_product_ar_active" label="اسم المنتج" required placeholder="مثال: احذية" class="is-active" :options="['shoes' => 'احذية']" />
            </div>
        </div>
    </x-tenant::card>

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
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/ui-kit.js')
@endpush
