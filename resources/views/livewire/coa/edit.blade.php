<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Models\Coa;

new class extends Component {
    use Toast;

    public Coa $coa;

    public $code = '';
    public $name = '';
    public $normal_balance = '';
    public $report_type = '';
    public bool $is_active = false;

    public function mount(): void
    {
        Gate::authorize('update coa');
        $this->fill($this->coa);
    }

    public function save(): void
    {
        $data = $this->validate([
            'code' => 'required|unique:coa,code,'.$this->coa->id,
            'name' => 'required',
            'normal_balance' => 'required',
            'report_type' => 'required',
            'is_active' => 'boolean',
        ]);

        $this->coa->update($data);

        $this->success('Coa successfully updated.', redirectTo: route('coa.index'));
    }
}; ?>

<div>
    <x-header title="Update Coa" separator>
        <x-slot:actions>
            <x-button label="Back" link="{{ route('coa.index') }}" icon="o-arrow-uturn-left" />
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="save">
        <x-card>
            <div class="space-y-4">
                <x-input label="Code" wire:model="code" />
                <x-input label="Name" wire:model="name" />
                <x-select label="Normal Balance" wire:model="normal_balance" :options="\App\Enums\DC::toSelect()" placeholder="-- Select --" />
                <x-select label="Report Type" wire:model="report_type" :options="\App\Enums\ReportType::toSelect()" placeholder="-- Select --" />
                <x-toggle label="Active" wire:model="is_active" />
            </div>
        </x-card>
        <x-slot:actions>
            <x-button label="Cancel" link="{{ route('coa.index') }}" />
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
