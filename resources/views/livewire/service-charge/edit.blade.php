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
    public $transport = '';
    public $service_charge_group_id = '';
    public $type = '';
    public $buying_coa_id = '';
    public $selling_coa_id = '';
    public bool $is_active = false;

    public Collection $buyingCoaSearchable;
    public Collection $sellingCoaSearchable;
    public Collection $serviceChargeGroups;

    public function mount(): void
    {
        Gate::authorize('update service-charge');
        $this->fill($this->serviceCharge);

        $this->searchBuyingCoa();
        $this->searchSellingCoa();
        $this->searchGroup();
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

    public function searchGroup(string $value = ''): void
    {
        $selected = \App\Models\ServiceChargeGroup::where('id', intval($this->service_charge_group_id))->get();
        $this->serviceChargeGroups = \App\Models\ServiceChargeGroup::query()
            ->filterLike('name', $value)
            ->take(50)
            ->get()
            ->merge($selected);
    }

    public function save(): void
    {
        $data = $this->validate([
            'code' => 'required|unique:service_charge,code,'.$this->serviceCharge->id,
            'name' => 'required',
            'transport' => 'nullable',
            'service_charge_group_id' => 'nullable|exists:service_charge_group,id',
            'type' => 'nullable',
            'buying_coa_id' => 'required',
            'selling_coa_id' => 'required',
            'is_active' => 'boolean',
        ]);

        $this->serviceCharge->update($data);

        $this->success('Items Master successfully updated.', redirectTo: route('items-master.index'));
    }
}; ?>

<div>
    <x-header title="Update Items Master" separator>
        <x-slot:actions>
            <x-button label="Back" link="{{ route('items-master.index') }}" icon="o-arrow-uturn-left" />
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="save">
        <x-card>
            <div class="space-y-4">
                <x-input label="Code" wire:model="code" />
                <x-input label="Name" wire:model="name" />
                <x-select label="Transport" wire:model="transport" :options="\App\Enums\Transport::toSelect()" placeholder="-- Select --" />
                <x-choices
                    label="Group"
                    wire:model="service_charge_group_id"
                    :options="$serviceChargeGroups"
                    search-function="searchGroup"
                    option-label="name"
                    single
                    searchable
                    clearable
                    placeholder="-- Select --"
                />
                <x-select label="Type" wire:model="type" :options="\App\Enums\ServiceType::toSelect()" placeholder="-- Select --" />
                <x-choices label="Buying Coa" wire:model="buying_coa_id" :options="$buyingCoaSearchable" search-function="searchBuyingCoa" option-label="full_name" single searchable values-as-string placeholder="-- Select --" />
                <x-choices label="Selling Coa" wire:model="selling_coa_id" :options="$sellingCoaSearchable" search-function="searchSellingCoa" option-label="full_name" single searchable values-as-string placeholder="-- Select --" />
                <x-toggle label="Active" wire:model="is_active" />
            </div>
        </x-card>
        <x-slot:actions>
            <x-button label="Cancel" link="{{ route('items-master.index') }}" />
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
        {{-- <div class="lg:bottom-0 lg:sticky p-5 bg-base-300 flex justify-center items-center gap-3 border-t-2 border-t-base-300">
            <x-button label="Cancel" link="{{ route('service-charge.index') }}" />
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </div> --}}
    </x-form>
</div>
