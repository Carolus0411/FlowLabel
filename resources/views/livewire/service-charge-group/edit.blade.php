<?php

use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\Gate;
use App\Models\ServiceChargeGroup;

new class extends Component {
    use Toast;

    public ServiceChargeGroup $group;

    public $code = '';
    public $name = '';
    public bool $is_active = true;

    public function mount(): void
    {
        Gate::authorize('update service-charge-group');
        $this->fill($this->group);
    }

    public function save(): void
    {
        $data = $this->validate([
            'code' => 'required',
            'name' => 'required',
            'is_active' => 'boolean',
        ]);

        $this->group->update($data);

        $this->success('Items Group successfully updated.', redirectTo: route('items-master-group.index'));
    }
}; ?>

<div>
    <x-header title="Update Items Group" separator>
        <x-slot:actions>
            <x-button label="Back" link="{{ route('items-master-group.index') }}" icon="o-arrow-uturn-left" />
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="save">
        <x-card>
            <div class="space-y-4">
                <x-input label="Code Items Group" wire:model="code" />
                <x-input label="Name Items Group" wire:model="name" />
                <x-toggle label="Active" wire:model="is_active" />
            </div>
        </x-card>

        <x-slot:actions>
            <x-button label="Cancel" link="{{ route('items-master-group.index') }}" />
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
