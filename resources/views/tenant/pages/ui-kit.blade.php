@extends('tenant.layouts.app')

@section('title', __('UI Kit'))

@section('content')
    <x-tenant::page-header :title="__('UI Kit')" badge="QA" :description="__('Every x-tenant:: component in every state. Local environment only.')">
        <x-slot:actions>
            <button type="button" class="btn btn-secondary" id="ui-kit-rtl-toggle">{{ __('Toggle RTL') }}</button>
            <button type="button" class="btn btn-secondary" data-action="toggle-theme">{{ __('Toggle theme') }}</button>
        </x-slot:actions>
    </x-tenant::page-header>

    @php
        $zap = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"><path d="M13.5 2.25L4.75 13.5h7l-1.25 8.25L19.25 10.5h-7l1.25-8.25z"/></svg>';
        $play = '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M7 4.5v15l12-7.5-12-7.5z"/></svg>';
    @endphp

    <x-tenant::card :title="__('Design system — buttons')">
        <div class="ds-kit-grid">
            @foreach(['primary' => __('Default'), 'secondary' => __('Secondary')] as $variant => $caption)
                @foreach([null => __('Small'), 'md' => __('Mid'), 'lg' => __('Large')] as $size => $sizeLabel)
                    <x-tenant::btn :variant="$variant" :size="$size ?: null" :icon="$play">{{ __('Button') }}</x-tenant::btn>
                @endforeach
            @endforeach
            @foreach([null, 'md', 'lg'] as $size)
                <x-tenant::btn :size="$size" :icon="$play" disabled>{{ __('Button') }}</x-tenant::btn>
            @endforeach
            <x-tenant::btn variant="ghost">{{ __('Ghost') }}</x-tenant::btn>
            <x-tenant::btn variant="danger">{{ __('Danger') }}</x-tenant::btn>
            <x-tenant::btn size="sm">{{ __('Compact (tables)') }}</x-tenant::btn>
        </div>
        <p class="t-help">{{ __('Hover and press a button to see its hover (#E66504) and pressed (#8B3D02) states.') }}</p>

        <div class="ds-kit-grid ds-kit-tiles">
            <x-tenant::btn variant="tile" :icon="$zap">{{ __('Add to Flash Sale') }}</x-tenant::btn>
            <x-tenant::btn variant="tile" :icon="$zap" class="btn-tile-soft">{{ __('Add to Flash Sale') }}</x-tenant::btn>
            <x-tenant::btn variant="tile" :icon="$zap" aria-pressed="true">{{ __('Add to Flash Sale') }}</x-tenant::btn>
        </div>
    </x-tenant::card>

    <div class="g-stats3 section-gap">
        <x-tenant::card :title="__('Design system — switch')">
            <x-tenant::switch name="ds_switch_off" :label="__('Off')" />
            <x-tenant::switch name="ds_switch_on" :label="__('On')" :checked="true" />
        </x-tenant::card>

        <x-tenant::card :title="__('Design system — status & trend')">
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

        <x-tenant::card :title="__('Design system — dropdown')">
            <x-tenant::select-menu name="ds_period" value="today" icon="calendar" :label="__('Period')"
                :options="['today' => __('Today'), 'weekly' => __('Weekly'), 'monthly' => __('Monthly'), 'yearly' => __('Yearly')]" />
        </x-tenant::card>
    </div>

    <x-tenant::card :title="__('Design system — inputs')">
        <div class="form-grid">
            <x-tenant::select name="ds_product" :label="__('Product name')" required :placeholder="__('Ex, Nike shoes')" :options="['nike' => __('Nike shoes'), 'adidas' => __('Adidas')]" />
            <div dir="rtl">
                <x-tenant::select name="ds_product_ar" :label="__('اسم المنتج')" required :placeholder="__('مثال: احذية')" :options="['shoes' => 'احذية']" />
            </div>
            <x-tenant::select name="ds_product_active" :label="__('Product name (active)')" required :placeholder="__('Ex, Nike shoes')" class="is-active" :options="['nike' => __('Nike shoes')]" />
            <div dir="rtl">
                <x-tenant::select name="ds_product_ar_active" :label="__('اسم المنتج')" required :placeholder="__('مثال: احذية')" class="is-active" :options="['shoes' => 'احذية']" />
            </div>
        </div>
    </x-tenant::card>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::card :title="__('Form building blocks')">
        <x-tenant::form action="{{ route('tenant.ui-kit.store') }}" method="POST" validate="{{ route('tenant.ui-kit.validate') }}" success="none">
            <div class="form-grid">
                <x-tenant::input name="text" :label="__('Text input')" required :placeholder="__('Type something')" :help="__('Blur to validate.')" />
                <x-tenant::input name="text_error" :label="__('Text input (error state)')" error value="" />
                <x-tenant::input name="text_disabled" :label="__('Disabled')" disabled value="Can't touch this" />
                <x-tenant::input name="password" type="password" :label="__('Password')" toggle />
                <x-tenant::input name="email" type="email" :label="__('Email')" required />
                <x-tenant::input name="slug" :label="__('Slug')" slug-from="text" />
                <x-tenant::input name="counted" :label="__('Counter')" maxlength="20" counter />
                <x-tenant::textarea name="textarea" :label="__('Textarea')" rows="3" counter maxlength="200" />
                <x-tenant::select name="select" :label="__('Native select')" required :options="['one' => __('One'), 'two' => __('Two'), 'three' => __('Three')]" :placeholder="__('Choose')" />
                <x-tenant::select2 name="select2" :label="__('Select2')" :options="['one' => __('One'), 'two' => __('Two'), 'three' => __('Three')]" :placeholder="__('Choose')" />
                <x-tenant::select2 name="select2_multi" :label="__('Select2 (multiple)')" multiple :options="['a' => __('Alpha'), 'b' => __('Beta'), 'c' => __('Gamma')]" />
                <x-tenant::checkbox name="checkbox" :label="__('Checkbox')" />
                <x-tenant::switch name="switch" :label="__('Switch (form field)')" />
                <x-tenant::date name="date" :label="__('Date')" />
                <x-tenant::time name="time" :label="__('Time')" />
                <x-tenant::color name="color" :label="__('Color')" :swatches="['#06b6d4', '#8b5cf6', '#22c55e', '#f59e0b', '#ef4444']" />
                <x-tenant::phone name="phone" :label="__('Phone')" />
                <x-tenant::file name="file" :label="__('File')" />
                <x-tenant::image-upload name="image" :label="__('Image upload')" expected-width="600" expected-height="400" />
            </div>

            <x-tenant::checkbox-group name="checkbox_group" :label="__('Checkbox group')" select-all :options="['x' => 'X', 'y' => 'Y', 'z' => 'Z']" />
            <x-tenant::radio-group name="radio_group" :label="__('Radio group')" variant="cards" :options="[['value'=>'basic','label'=>__('Basic'),'description'=>__('Simple plan')],['value'=>'pro','label'=>__('Pro'),'description'=>__('Advanced plan')]]" />

            <x-tenant::editor name="editor" :label="__('Rich text')" height="240" />

            <x-tenant::dropzone name="gallery" :label="__('Drop images here')" :sublabel="__('PNG or JPG')" multiple sortable />

            <x-tenant::locale-tabs :languages="$languages">
                <x-tenant::locale-pane code="en" dir="ltr" :active="true">
                    <x-tenant::input name="translations[en][name]" :label="__('Name (English)')" />
                </x-tenant::locale-pane>
                <x-tenant::locale-pane code="ar" dir="rtl">
                    <x-tenant::input name="translations[ar][name]" :label="__('Name (Arabic)')" />
                </x-tenant::locale-pane>
            </x-tenant::locale-tabs>

            <div class="page-actions compact-actions justify-end">
                <x-tenant::submit>{{ __('Save demo form') }}</x-tenant::submit>
            </div>
        </x-tenant::form>
    </x-tenant::card>

    <x-tenant::datatable id="ui-kit-table" :url="route('tenant.ui-kit.data')" :columns="$columns" :title="__('Demo records')" quick-search selectable bulk-url="#">
        <x-slot:toolbar>
            <x-tenant::filters-card target="ui-kit-table" :title="__('Filters')">
                <x-tenant::select2 name="status" :label="__('Status')" :options="['active' => __('Active'), 'pending' => __('Pending'), 'rejected' => __('Rejected')]" :placeholder="__('All')" />
            </x-tenant::filters-card>
        </x-slot:toolbar>
    </x-tenant::datatable>

    <div class="g-stats3 section-gap">
        <x-tenant::card :title="__('Badges')">
            <x-tenant::badge color="cyan">{{ __('Cyan') }}</x-tenant::badge>
            <x-tenant::badge color="green">{{ __('Green') }}</x-tenant::badge>
            <x-tenant::badge color="amber">{{ __('Amber') }}</x-tenant::badge>
            <x-tenant::badge color="red">{{ __('Red') }}</x-tenant::badge>
            <x-tenant::badge color="violet">{{ __('Violet') }}</x-tenant::badge>
            <x-tenant::badge color="gray">{{ __('Gray') }}</x-tenant::badge>
        </x-tenant::card>

        <x-tenant::card :title="__('Avatars')">
            <x-tenant::avatar name="Ada Lovelace" />
            <x-tenant::avatar name="Grace Hopper" size="40" />
        </x-tenant::card>

        <x-tenant::card :title="__('Alerts')">
            <x-tenant::alert type="info" :title="__('Heads up')">{{ __('Informational message.') }}</x-tenant::alert>
            <x-tenant::alert type="success" :title="__('Saved')">{{ __('Success message.') }}</x-tenant::alert>
            <x-tenant::alert type="warning" :title="__('Careful')" dismissible>{{ __('Warning message.') }}</x-tenant::alert>
        </x-tenant::card>
    </div>

    <x-tenant::card :title="__('Progress')">
        <x-tenant::progress :value="42" :label="__('Storage used')" show-value />
    </x-tenant::card>

    <x-tenant::card :title="__('Modal, dropdown, tabs')">
        <button type="button" class="btn btn-primary" data-modal-open="ui-kit-modal">{{ __('Open modal') }}</button>

        <x-tenant::dropdown :label="__('Actions')">
            <x-tenant::dropdown-item>{{ __('Edit') }}</x-tenant::dropdown-item>
            <x-tenant::dropdown-item danger>{{ __('Delete') }}</x-tenant::dropdown-item>
        </x-tenant::dropdown>

        <x-tenant::tabs :tabs="['one' => __('Tab one'), 'two' => __('Tab two')]" mode="local">
            <x-tenant::tab-panel key="one" :active="true">{{ __('Panel one content.') }}</x-tenant::tab-panel>
            <x-tenant::tab-panel key="two">{{ __('Panel two content.') }}</x-tenant::tab-panel>
        </x-tenant::tabs>
    </x-tenant::card>

    <x-tenant::modal id="ui-kit-modal" :title="__('Demo modal')" size="md">
        <p class="panel-copy">{{ __('Modal body content.') }}</p>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" data-modal-close>{{ __('Close') }}</button>
        </x-slot:footer>
    </x-tenant::modal>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/ui-kit.js')
@endpush
