<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Schema;
use Mary\Traits\Toast;
use App\Models\Supplier;

new class extends Component {
    use Toast;

    public Supplier $supplier;

    public string $code = '';
    public string $name = '';
    public string $contact_name = '';
    public string $address_1 = '';
    public string $address_2 = '';
    public string $telephone = '';
    public string $mobile_phone = '';
    public string $email = '';
    public string $npwp = '';
    public string $information = '';
    public int $term_of_payment = 0;
    public bool $is_active = true;

    public function mount(): void
    {
        Gate::authorize('update supplier');
        $this->fill($this->supplier);
    }

    public function save(): void
    {
        if (! Schema::hasTable('supplier')) {
            $this->error('Database table `supplier` does not exist. Please run migrations.');
            return;
        }
        $data = $this->validate([
            'code' => 'required|unique:supplier,code,'.$this->supplier->id,
            'name' => 'required',
            'contact_name' => 'nullable',
            'address_1' => 'nullable',
            'address_2' => 'nullable',
            'telephone' => 'nullable',
            'mobile_phone' => 'nullable',
            'email' => 'nullable|email',
            'npwp' => 'nullable',
            'information' => 'nullable',
            'term_of_payment' => 'nullable|numeric',
            'is_active' => 'boolean',
        ]);

        $this->supplier->update($data);

        $this->success('Supplier successfully updated.', redirectTo: route('supplier.index'));
    }
}; ?>

<div>
    <x-header title="Update Supplier" separator>
        <x-slot:actions>
            <x-button label="Back" link="{{ route('supplier.index') }}" icon="o-arrow-uturn-left" />
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="save">
        <x-card>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div>
                    <x-input label="Supplier Code" wire:model="code" />
                    <x-input label="Supplier Name" wire:model="name" />
                    <x-input label="Contact Name" wire:model="contact_name" />
                </div>
                <div>
                    <x-input label="Address 1" wire:model="address_1" />
                    <x-input label="Address 2" wire:model="address_2" />
                    <x-input label="Telephone" wire:model="telephone" />
                    <x-input label="Mobile Phone" wire:model="mobile_phone" />
                    <x-input label="E-mail" wire:model="email" />
                </div>
                <div>
                    <x-input label="No. NPWP" wire:model="npwp" />
                    <x-input label="Term Of Payment (days)" wire:model="term_of_payment" />
                    <x-textarea label="Information" wire:model="information" />
                    <div class="mt-2">
                        <x-toggle label="Active" wire:model="is_active" />
                    </div>
                </div>
            </div>
        </x-card>
        <x-slot:actions>
            <x-button label="Cancel" link="{{ route('supplier.index') }}" />
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
