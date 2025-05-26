<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Models\Ppn;

new class extends Component {
    use Toast;

    public Ppn $ppn;

    public $name = '';
    public $value = 0;
    public bool $is_active = false;

    public function mount(): void
    {
        Gate::authorize('update ppn');
        $this->fill($this->ppn);
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required',
            'value' => 'required|decimal:0,2',
            'is_active' => 'boolean',
        ]);

        $this->ppn->update($data);

        $this->success('Ppn successfully updated.', redirectTo: route('ppn.index'));
    }
}; ?>

<div>
    <x-header title="Update Ppn" separator>
        <x-slot:actions>
            <x-button label="Back" link="{{ route('ppn.index') }}" icon="o-arrow-uturn-left" />
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="save">
        <x-card>
            <div class="space-y-4">
                <x-input label="Name" wire:model="name" />
                <x-input label="Value" wire:model="value" x-mask:dynamic="$money($input, '.', '')" />
                <x-toggle label="Active" wire:model="is_active" />
            </div>
        </x-card>
        <x-slot:actions>
            <x-button label="Cancel" link="{{ route('ppn.index') }}" />
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
