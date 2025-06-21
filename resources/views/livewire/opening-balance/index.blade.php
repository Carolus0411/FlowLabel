<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public ?string $opening_balance_period = '';

    public function mount(): void
    {
        Gate::authorize('view general setting');

        $this->opening_balance_period = settings('opening_balance_period');
    }

    public function save(): void
    {
        $data = $this->validate([
            'opening_balance_period' => 'required|numeric:0,2',
        ]);

        settings($data);

        $this->success('General setting successfully updated.');
    }
}; ?>

<div>
    <x-header title="Opening Balance" separator>
        {{-- <x-slot:actions>
            <x-button label="Back" link="{{ route('brand.index') }}" icon="o-arrow-uturn-left" />
        </x-slot:actions> --}}
    </x-header>

    <x-form wire:submit="save">
        <x-card title="Opening Balance">
            <div class="space-y-4">
                <x-input label="Opening Balance Period" wire:model="opening_balance_period" />
            </div>
        </x-card>
        <x-slot:actions>
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
