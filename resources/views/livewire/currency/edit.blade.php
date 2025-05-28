<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Models\Currency;

new class extends Component {
    use Toast;

    public Currency $currency;

    public $code = '';
    public $name = '';
    public bool $is_active = false;

    public function mount(): void
    {
        Gate::authorize('update currency');
        $this->fill($this->currency);
    }

    public function save(): void
    {
        $data = $this->validate([
            'code' => 'required|unique:currency,code,'.$this->currency->id,
            'name' => 'required',
            'is_active' => 'boolean',
        ]);

        $this->currency->update($data);

        $this->success('Currency successfully updated.', redirectTo: route('currency.index'));
    }
}; ?>

<div>
    <x-header title="Update Currency" separator>
        <x-slot:actions>
            <x-button label="Back" link="{{ route('currency.index') }}" icon="o-arrow-uturn-left" />
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="save">
        <x-card>
            <div class="space-y-4">
                <x-input label="Code" wire:model="code" />
                <x-input label="Name" wire:model="name" />
                <x-toggle label="Active" wire:model="is_active" />
            </div>
        </x-card>
        <x-slot:actions>
            <x-button label="Cancel" link="{{ route('currency.index') }}" />
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
