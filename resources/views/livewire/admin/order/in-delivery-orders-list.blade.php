<div>
    @include('livewire.admin.pages.list-page')

    <x-modal wire:model="showOrderStatusModal" title="Change Order Status" maxWidth="sm"
        closeAction="closeOrderStatusModal">
        <div class="page-stack">
            @if (!empty($selectedOrderForStatus))
                <div class="details-kv">
                    <span class="details-label">Order</span>
                    <span class="details-value">{{ $selectedOrderForStatus['uuid'] ?? '-' }}</span>
                </div>
                <div class="details-kv">
                    <span class="details-label">Store</span>
                    <span class="details-value">{{ $selectedOrderForStatus['tenant']['name'] ?? '-' }}</span>
                </div>
                <div class="details-kv">
                    <span class="details-label">Current Status</span>
                    <span class="details-value">{{ $selectedOrderForStatus['status'] ?? '-' }}</span>
                </div>
            @endif
            <div>
                <label class="field-label">New Order Status</label>
                <x-select wire:model.defer="selectedOrderStatus">
                    @foreach ($orderStatusOptions as $statusValue => $statusLabel)
                        <option value="{{ $statusValue }}">{{ $statusLabel }}</option>
                    @endforeach
                </x-select>
            </div>
            <div class="page-actions compact-actions justify-end">
                <x-btn type="button" variant="secondary" wire:click="closeOrderStatusModal">Cancel</x-btn>
                <x-btn type="button" wire:click="updateSelectedOrderStatus">Update Status</x-btn>
            </div>
        </div>
    </x-modal>
</div>