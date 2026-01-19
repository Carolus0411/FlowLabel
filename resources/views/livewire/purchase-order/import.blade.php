<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\Schema;

new class extends Component {
    use Toast, WithFileUploads;

    public $file;

    public function mount(): void {
        Gate::authorize('import purchase-order');
    }

    public function save(): void {
        if (! Schema::hasTable('purchase_order')) {
            $this->error('Database table `purchase_order` does not exist. Please run migrations.');
            return;
        }
        $this->success('This is a placeholder import. Implement import logic later.');
    }
};
?>

<div>
    <x-header title="Import Purchase Order" separator />
    <x-card>
        <x-form wire:submit="save">
            <div class="mb-2 text-sm text-gray-600">Template can be created by exporting data from the Purchase Order page using the Export button.</div>
            <x-file wire:model="file" label="File" hint="xlsx or csv" wire:target="save" wire:loading.attr="disabled" />
            <x-slot:actions>
                <x-button label="Cancel" link="{{ route('purchase-order.index') }}" wire:target="save" wire:loading.attr="disabled" />
                <x-button label="Import" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>

