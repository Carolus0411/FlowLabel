<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

new class extends Component {
    use Toast;

    public Role $role;

    public $name = '';
    public $guard_name = '';
    public $roleToPermission = [];
    public $permissions = [];

    public function mount(): void
    {
        Gate::authorize('update roles');
        $this->fill($this->role);

        $permissions = Permission::where('guard_name', $this->role->guard_name)->get();
        $tmp = [];
        foreach ($permissions as $permission) {
            $tmp[$permission->resource][] = $permission;
        }

        $stored = $this->role->getAllPermissions();
        foreach ($stored as $permission) {
            $this->roleToPermission[] = $permission->name;
        }

        $this->permissions = $tmp;
    }

    public function with(): array
    {
        return [
            'resources' => Permission::select('resource')->where('guard_name', $this->role->guard_name)->groupBy('resource')->orderBy('resource')->get(),
            'permissions' => $this->permissions,
        ];
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required|unique:roles,name,'.$this->role->id,
            'guard_name' => 'required',
        ]);

        $this->role->update($data);

        $this->success('Role has been updated.', redirectTo: route('roles.index'));
    }

    public function savePermission(): void
    {
        $data = $this->validate([
            'roleToPermission' => 'nullable',
        ]);

        $this->role->syncPermissions($this->roleToPermission);

        $this->success('Role has been updated.');
    }

    public function checkAll(): void
    {
        $this->roleToPermission = Permission::select('name')->get()->pluck('name');
    }

    public function clearAll(): void
    {
        $this->roleToPermission = [];
    }
}; ?>

<div>
    <x-header title="Update Role" separator />
    <div class="space-y-8">
        <x-form wire:submit="save">
            <x-card title="Role" separator>
                <div class="space-y-4">
                    @if ($role->name == 'admin')
                    <div class="mb-8 text-sm border border-sky-300 dark:border-sky-800 bg-sky-100/50 dark:bg-sky-950 py-3 px-4 space-y-3 rounded-lg">
                        <p>Admin has all access rights, so it does not require selecting permissions.</p>
                    </div>
                    @endif

                    @if ($role->name == 'admin')
                    <x-input label="Name" wire:model="name" readonly class="bg-gray-100" />
                    <x-input label="Guard" wire:model="guard_name" readonly class="bg-gray-100" />
                    @else
                    <x-input label="Name" wire:model="name" />
                    <x-input label="Guard" wire:model="guard_name" readonly />
                    @endif
                </div>
                <x-slot:actions>
                    <x-button label="Cancel" link="{{ route('roles.index') }}" />
                    <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
                </x-slot:actions>
            </x-card>
        </x-form>

        @if ($role->name != 'admin')
        <x-form wire:submit="savePermission">
            <x-card title="Permissions" separator>
                <x-slot:menu>
                    <x-button label="Check All" wire:click="checkAll" spinner="checkAll" class="btn" />
                    <x-button label="Clear All" wire:click="clearAll" spinner="clearAll" class="btn" />
                    <x-button label="Apply Permissions" icon="o-paper-airplane" spinner="savePermission" type="submit" class="btn-primary" />
                </x-slot:menu>
                <div class="overflow-x-auto">
                    <table class="table">
                    <tbody>
                    @forelse ( $resources as $resource)
                    <tr>
                        <td class="lg:w-[200px]">{{ $resource->resource }}</td>
                        <td>
                            <div class="flex flex-wrap gap-4">
                                @foreach ($permissions[$resource->resource] ?? [] as $permission)
                                <x-checkbox label="{{ str_replace($resource->resource,'',$permission->name) }}" wire:model="roleToPermission" value="{{ $permission->name }}" id="{{ $permission->id }}" />
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @empty
                    <p>No permission specified.</p>
                    @endforelse
                    </tbody>
                    </table>
                </div>
                <x-slot:actions>
                    <x-button label="Check All" wire:click="checkAll" spinner="checkAll" class="btn" />
                    <x-button label="Clear All" wire:click="clearAll" spinner="clearAll" class="btn" />
                    <x-button label="Apply Permissions" icon="o-paper-airplane" spinner="savePermission" type="submit" class="btn-primary" />
                </x-slot:actions>
            </x-card>
        </x-form>
        @endif
    </div>
</div>
