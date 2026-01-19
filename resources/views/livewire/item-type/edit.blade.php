<?php

use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Models\ItemType;

new class extends Component {
    use Toast;

    public ItemType $itemType;
    public string $name = '';
    public bool $is_active = true;
    public bool $is_stock = false;

    public function mount(ItemType $itemType): void
    {
        $this->itemType = $itemType;
        $this->fill($this->itemType);
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required|unique:item_type,name,' . $this->itemType->id,
            'is_active' => 'boolean',
            'is_stock' => 'boolean',
        ]);

        $this->itemType->update($data);

        $this->success('Item Type updated successfully.', redirectTo: route('item-type.index'));
    }
}; ?>

<div>
    <x-header title="Edit Item Type" separator />

    <div class="grid gap-5 lg:grid-cols-2">
        <x-form wire:submit="save">
            <x-input label="Name" wire:model="name" />
            <x-toggle label="Is Stock" wire:model="is_stock" />
            <x-toggle label="Active" wire:model="is_active" />

            <x-slot:actions>
                <x-button label="Cancel" link="{{ route('item-type.index') }}" />
                <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </div>
</div>
