<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Schema;
use Mary\Traits\Toast;
use App\Models\ThreePl;

new class extends Component {
    use Toast;

    public string $code = '';
    public string $name = '';
    public bool $is_active = true;

    public function mount(): void
    {
        Gate::authorize('create three-pl');
    }

    public function save(): void
    {
        if (! Schema::hasTable('three_pls')) {
            $this->error('Database table `three_pls` does not exist. Please run migrations.');
            return;
        }
        $data = $this->validate([
            'code' => 'required|unique:three_pls,code',
            'name' => 'required',
            'is_active' => 'boolean',
        ]);

        ThreePl::create($data);

        $this->success('3PL successfully created.', redirectTo: route('three-pl.index'));
    }
}; ?>

<div>
    <x-header title="Create 3PL" separator>
        <x-slot:actions>
            <x-button label="Back" link="{{ route('three-pl.index') }}" icon="o-arrow-uturn-left" />
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="save">
        <x-card>
            <div class="space-y-4">
                <x-input label="Kode 3PL" wire:model="code" />
                <x-input label="Nama 3PL" wire:model="name" />
                <div class="mt-2">
                    <x-toggle label="Active" wire:model="is_active" />
                </div>
            </div>
        </x-card>
        <x-slot:actions>
            <x-button label="Cancel" link="{{ route('three-pl.index') }}" />
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
