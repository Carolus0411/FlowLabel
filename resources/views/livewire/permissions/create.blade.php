<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Spatie\Permission\Models\Permission;

new class extends Component {
    use Toast;

    public string $resource = '';
    public string $name = '';
    public string $guard_name = '';

    public function mount(): void
    {
        Gate::authorize('create permissions');
    }

    public function save(): void
    {
        $data = $this->validate([
            'resource' => 'required',
            'name' => 'required',
            'guard_name' => 'required',
        ]);

        $permission = Permission::create($data);

        $this->success('Permission has been created.', redirectTo: route('permissions.index'));
    }
}; ?>

<div>
    <x-header title="Create New Permission" separator />
    <div class="lg:w-[70%]">
        <x-form wire:submit="save">
            <x-card>
                <div class="space-y-4">
                    <x-input label="Resource" wire:model="resource" />
                    <x-input label="Name" wire:model="name" />
                    <x-input label="Guard" wire:model="guard_name" />
                </div>
            </x-card>
            <x-slot:actions>
                <x-button label="Cancel" link="{{ route('permissions.index') }}" />
                <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </div>
</div>
