<button wire:click="add" wire:loading.attr="disabled" wire:target="add" class="vendor-add-to-cart">
    <span wire:loading.remove wire:target="add">{{ $label }}</span>
    <span wire:loading wire:target="add">Adding…</span>
</button>
