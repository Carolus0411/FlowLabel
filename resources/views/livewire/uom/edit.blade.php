<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Models\Uom;

new class extends Component {
    use Toast;

    public Uom $uom;

    public $code = '';
    public $name = '';
    public bool $is_active = false;

    public function mount(): void
    {
        Gate::authorize('update uom');
        $this->fill($this->uom);
    }

    public function save(): void
    {
        $data = $this->validate([
            'code' => 'required|unique:uom,code,'.$this->uom->id,
            'name' => 'required',
            'is_active' => 'boolean',
        ]);

        $this->uom->update($data);

        $this->success('Uom successfully updated.', redirectTo: route('uom.index'));
    }
}; ?>

<div>
    <x-header title="Update Uom" separator>
        <x-slot:actions>
            <x-button label="Back" link="{{ route('uom.index') }}" icon="o-arrow-uturn-left" />
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
            <x-button label="Cancel" link="{{ route('uom.index') }}" />
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
