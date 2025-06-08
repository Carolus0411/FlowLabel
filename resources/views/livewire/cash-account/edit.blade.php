<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Models\Currency;
use App\Models\Coa;
use App\Models\CashAccount;

new class extends Component {
    use Toast;

    public CashAccount $cashAccount;

    public $name = '';
    public $currency_id = '';
    public $coa_code = '';
    public bool $is_active = false;

    public Collection $currencies;
    public Collection $coas;

    public function mount(): void
    {
        Gate::authorize('update cash-account');
        $this->fill($this->cashAccount);
        $this->searchCurrency();
        $this->searchCoa();
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required',
            'currency_id' => 'required',
            'coa_code' => 'required',
            'is_active' => 'boolean',
        ]);

        $this->cashAccount->update($data);

        $this->success('Cash account successfully updated.', redirectTo: route('cash-account.index'));
    }

    public function searchCurrency(string $value = ''): void
    {
        $selected = Currency::where('id', intval($this->currency_id))->get();
        $this->currencies = Currency::query()
            ->filterLike('name', $value)
            ->isActive()
            ->take(20)
            ->get()
            ->merge($selected);
    }

    public function searchCoa(string $value = ''): void
    {
        $selected = Coa::where('code', $this->coa_code)->get();
        $this->coas = Coa::query()
            ->filterLike('name', $value)
            ->isActive()
            ->take(20)
            ->get()
            ->merge($selected);
    }
}; ?>

<div>
    <x-header title="Update Cash Account" separator>
        <x-slot:actions>
            <x-button label="Back" link="{{ route('cash-account.index') }}" icon="o-arrow-uturn-left" />
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="save">
        <x-card>
            <div class="space-y-4">
                <x-input label="Name" wire:model="name" />
                <x-choices
                    label="Currency"
                    wire:model="currency_id"
                    :options="$currencies"
                    search-function="searchCurrency"
                    option-label="code"
                    single
                    searchable
                    placeholder="-- Select --"
                />
                <x-choices
                    label="Coa"
                    wire:model="coa_code"
                    :options="$coas"
                    search-function="searchCoa"
                    option-value="code"
                    option-label="full_name"
                    single
                    searchable
                    placeholder="-- Select --"
                />
                <x-toggle label="Active" wire:model="is_active" />
            </div>
        </x-card>
        <x-slot:actions>
            <x-button label="Cancel" link="{{ route('cash-account.index') }}" />
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
