<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Models\Bank;
use App\Models\Currency;
use App\Models\Coa;
use App\Models\BankAccount;

new class extends Component {
    use Toast;

    public BankAccount $bankAccount;

    public $name = '';
    public $bank_id = '';
    public $currency_id = '';
    public $coa_code = '';
    public bool $is_active = false;

    public Collection $banks;
    public Collection $currencies;
    public Collection $coas;

    public function mount(): void
    {
        Gate::authorize('update bank-account');
        $this->fill($this->bankAccount);
        $this->searchBank();
        $this->searchCurrency();
        $this->searchCoa();
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required',
            'bank_id' => 'required',
            'currency_id' => 'required',
            'coa_code' => 'required',
            'is_active' => 'boolean',
        ]);

        $this->bankAccount->update($data);

        $this->success('Bank account successfully updated.', redirectTo: route('bank-account.index'));
    }

    public function searchBank(string $value = ''): void
    {
        $selected = Bank::where('id', intval($this->bank_id))->get();
        $this->banks = Bank::query()
            ->filterLike('name', $value)
            ->isActive()
            ->take(20)
            ->get()
            ->merge($selected);
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
    <x-header title="Update Bank Account" separator>
        <x-slot:actions>
            <x-button label="Back" link="{{ route('bank-account.index') }}" icon="o-arrow-uturn-left" />
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="save">
        <x-card>
            <div class="space-y-4">
                <x-input label="Name" wire:model="name" />
                <x-choices
                    label="Bank"
                    wire:model="bank_id"
                    :options="$banks"
                    search-function="searchBank"
                    option-label="name"
                    single
                    searchable
                    placeholder="-- Select --"
                />
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
            <x-button label="Cancel" link="{{ route('bank-account.index') }}" />
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
