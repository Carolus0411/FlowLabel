<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Models\Bank;

new class extends Component {
    use Toast;

    public Bank $bank;

    public $name = '';
    public bool $is_active = false;

    public function mount(): void
    {
        Gate::authorize('update bank');
        $this->fill($this->bank);
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required',
            'is_active' => 'boolean',
        ]);

        $this->bank->update($data);

        $this->success('Bank successfully updated.', redirectTo: route('bank.index'));
    }
}; ?>

<div>
    <x-header title="Update Bank" separator>
        <x-slot:actions>
            <x-button label="Back" link="{{ route('bank.index') }}" icon="o-arrow-uturn-left" />
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="save">
        <x-card>
            <div class="space-y-4">
                <x-input label="Name" wire:model="name" />
                <x-toggle label="Active" wire:model="is_active" />
            </div>
        </x-card>
        <x-slot:actions>
            <x-button label="Cancel" link="{{ route('bank.index') }}" />
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
