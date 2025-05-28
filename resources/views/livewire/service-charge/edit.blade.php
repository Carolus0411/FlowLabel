<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Models\Coa;
use App\Models\ServiceCharge;

new class extends Component {
    use Toast;

    public ServiceCharge $serviceCharge;

    public $code = '';
    public $name = '';
    public $type = '';
    public $buying_coa_id = '';
    public $selling_coa_id = '';
    public bool $is_active = false;

    public Collection $buyingCoaSearchable;
    public Collection $sellingCoaSearchable;

    public function mount(): void
    {
        Gate::authorize('update service charge');
        $this->fill($this->serviceCharge);

        $this->searchBuyingCoa();
        $this->searchSellingCoa();
    }

    public function searchBuyingCoa(string $value = ''): void
    {
        $selectedOption = Coa::where('id', intval($this->buying_coa_id))->get();
        $this->buyingCoaSearchable = Coa::query()
            ->search($value)
            ->isActive()
            ->take(50)
            ->get()
            ->merge($selectedOption);
    }

    public function searchSellingCoa(string $value = ''): void
    {
        $selectedOption = Coa::where('id', intval($this->selling_coa_id))->get();
        $this->sellingCoaSearchable = Coa::query()
            ->search($value)
            ->isActive()
            ->take(50)
            ->get()
            ->merge($selectedOption);
    }

    public function save(): void
    {
        $data = $this->validate([
            'code' => 'required|unique:service_charge,code,'.$this->serviceCharge->id,
            'name' => 'required',
            'type' => 'required',
            'buying_coa_id' => 'required',
            'selling_coa_id' => 'required',
            'is_active' => 'boolean',
        ]);

        $this->serviceCharge->update($data);

        $this->success('Service charge successfully updated.', redirectTo: route('service-charge.index'));
    }
}; ?>

<div>
    <x-header title="Update Service Charge" separator>
        <x-slot:actions>
            <x-button label="Back" link="{{ route('service-charge.index') }}" icon="o-arrow-uturn-left" />
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="save">
        <x-card>
            <div class="space-y-4">
                <x-input label="Code" wire:model="code" />
                <x-input label="Name" wire:model="name" />
                <x-select label="Type" wire:model="type" :options="\App\Enums\ServiceType::toSelect()" placeholder="-- Select --" />
                <x-choices label="Buying Coa" wire:model="buying_coa_id" :options="$buyingCoaSearchable" search-function="searchBuyingCoa" option-label="full_name" single searchable values-as-string placeholder="-- Select --" />
                <x-choices label="Selling Coa" wire:model="selling_coa_id" :options="$sellingCoaSearchable" search-function="searchSellingCoa" option-label="full_name" single searchable values-as-string placeholder="-- Select --" />
                <x-toggle label="Active" wire:model="is_active" />
            </div>
        </x-card>
        <x-slot:actions>
            <x-button label="Cancel" link="{{ route('service-charge.index') }}" />
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
        {{-- <div class="lg:bottom-0 lg:sticky p-5 bg-base-300 flex justify-center items-center gap-3 border-t-2 border-t-base-300">
            <x-button label="Cancel" link="{{ route('service-charge.index') }}" />
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </div> --}}
    </x-form>
</div>
