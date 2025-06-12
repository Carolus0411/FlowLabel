<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public $cash_in_code = '';
    public $cash_out_code = '';
    public $bank_in_code = '';
    public $bank_out_code = '';

    public function mount(): void
    {
        Gate::authorize('view setting-code');

        $this->cash_in_code = settings('cash_in_code');
        $this->cash_out_code = settings('cash_out_code');
        $this->bank_in_code = settings('bank_in_code');
        $this->bank_out_code = settings('bank_out_code');
    }

    public function save(): void
    {
        $data = $this->validate([
            'cash_in_code' => 'required',
            'cash_out_code' => 'required',
            'bank_in_code' => 'required',
            'bank_out_code' => 'required',
        ]);

        settings($data);

        $this->success('Code setting successfully updated.');
    }
}; ?>

<div>
    <x-header title="General Setting" separator>
        {{-- <x-slot:actions>
            <x-button label="Back" link="{{ route('brand.index') }}" icon="o-arrow-uturn-left" />
        </x-slot:actions> --}}
    </x-header>

    <x-form wire:submit="save">
        <x-card title="Cash And Bank">
            <div class="space-y-4">
                <x-input label="Cash In" wire:model="cash_in_code" />
                <x-input label="Cash Out" wire:model="cash_out_code" />
                <x-input label="Bank In" wire:model="bank_in_code" />
                <x-input label="Bank Out" wire:model="bank_out_code" />
            </div>
        </x-card>
        <x-slot:actions>
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
