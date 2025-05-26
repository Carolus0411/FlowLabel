<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Spatie\Permission\Models\Role;

new class extends Component {
    use Toast;

    public string $name = '';
    public string $guard_name = 'web';

    public function mount(): void
    {
        Gate::authorize('create roles');
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required|unique:roles,name',
            'guard_name' => 'required',
        ]);

        $role = Role::create($data);

        $this->success('Role has been created.', redirectTo: route('roles.index'));
    }
}; ?>

<div>
    <x-header title="Create New Role" separator />
    <x-form wire:submit="save">
        <x-card>
            <div class="space-y-4">
                <x-input label="Name" wire:model="name" />
                <x-input label="Guard" wire:model="guard_name" readonly />
            </div>
        </x-card>
        <x-slot:actions>
            <x-button label="Cancel" link="{{ route('roles.index') }}" />
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
