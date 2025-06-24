<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public $active_period = '';
    public $opening_balance_period = '';

    public function mount(): void
    {
        Gate::authorize('view general setting');

        $this->opening_balance_period = settings('opening_balance_period');
        $this->active_period = settings('active_period');
    }

    public function save(): void
    {
        $data = $this->validate([
            'opening_balance_period' => 'required|size:4',
            'active_period' => 'required|size:4',
        ]);

        settings($data);

        $this->success('General setting successfully updated.');
    }
}; ?>

<div>
    <x-header title="General Setting" separator>
        {{-- <x-slot:actions>
            <x-button label="Back" link="{{ route('brand.index') }}" icon="o-arrow-uturn-left" />
        </x-slot:actions> --}}
    </x-header>

    <x-form wire:submit="save">
        <div class="space-y-6">
            <x-card title="Active Period">
                <div class="space-y-4">
                    <x-input label="Active Period" wire:model="active_period" />
                </div>
            </x-card>

            <x-card title="Opening Balance">
                <div class="space-y-4">
                    <x-input label="Opening Balance Period" wire:model="opening_balance_period" />
                </div>
            </x-card>
        </div>
        <x-slot:actions>
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
