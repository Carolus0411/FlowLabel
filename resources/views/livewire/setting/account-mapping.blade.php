<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Models\Coa;

new class extends Component {
    use Toast;

    public $account_receivable_code = '';
    public $account_payable_code = '';
    public $vat_out_code = '';
    public $stamp_code = '';

    public Collection $coaAR, $coaAP, $coaVatOut, $coaStamp;

    public function mount(): void
    {
        Gate::authorize('view account mapping');

        $this->account_receivable_code = settings('account_receivable_code');
        $this->account_payable_code = settings('account_payable_code');
        $this->vat_out_code = settings('vat_out_code');
        $this->stamp_code = settings('stamp_code');

        $this->searchCoaAR();
        $this->searchCoaAP();
        $this->searchCoaVatOut();
        $this->searchCoaStamp();
    }

    public function save(): void
    {
        $data = $this->validate([
            'account_receivable_code' => 'required',
            'account_payable_code' => 'required',
            'vat_out_code' => 'required',
            'stamp_code' => 'required',
        ]);

        settings($data);

        $this->success('Account successfully updated.');
    }

    public function searchCoaAR(string $value = ''): void
    {
        $selected = Coa::where('code', $this->account_receivable_code)->get();
        $this->coaAR = Coa::query()
            ->filterLike('name', $value)
            ->isActive()
            ->take(20)
            ->get()
            ->merge($selected);
    }

    public function searchCoaAP(string $value = ''): void
    {
        $selected = Coa::where('code', $this->account_payable_code)->get();
        $this->coaAP = Coa::query()
            ->filterLike('name', $value)
            ->isActive()
            ->take(20)
            ->get()
            ->merge($selected);
    }

    public function searchCoaVatOut(string $value = ''): void
    {
        $selected = Coa::where('code', $this->vat_out_code)->get();
        $this->coaVatOut = Coa::query()
            ->filterLike('name', $value)
            ->isActive()
            ->take(20)
            ->get()
            ->merge($selected);
    }

    public function searchCoaStamp(string $value = ''): void
    {
        $selected = Coa::where('code', $this->stamp_code)->get();
        $this->coaStamp = Coa::query()
            ->filterLike('name', $value)
            ->isActive()
            ->take(20)
            ->get()
            ->merge($selected);
    }
}; ?>

<div>
    <x-header title="Account Mapping" separator>
        {{-- <x-slot:actions>
            <x-button label="Back" link="{{ route('brand.index') }}" icon="o-arrow-uturn-left" />
        </x-slot:actions> --}}
    </x-header>

    <x-form wire:submit="save">
        <x-card>
            <div class="space-y-4">
                <x-choices
                    label="Account Receivable"
                    wire:model="account_receivable_code"
                    :options="$coaAR"
                    search-function="searchCoaAR"
                    option-value="code"
                    option-label="full_name"
                    single
                    searchable
                    placeholder="-- Select --"
                />
                <x-choices
                    label="Account Payable"
                    wire:model="account_payable_code"
                    :options="$coaAP"
                    search-function="searchCoaAP"
                    option-value="code"
                    option-label="full_name"
                    single
                    searchable
                    placeholder="-- Select --"
                />
                <x-choices
                    label="Vat Out"
                    wire:model="vat_out_code"
                    :options="$coaVatOut"
                    search-function="searchCoaVatOut"
                    option-value="code"
                    option-label="full_name"
                    single
                    searchable
                    placeholder="-- Select --"
                />
                <x-choices
                    label="Stamp"
                    wire:model="stamp_code"
                    :options="$coaStamp"
                    search-function="searchCoaStamp"
                    option-value="code"
                    option-label="full_name"
                    single
                    searchable
                    placeholder="-- Select --"
                />
            </div>
        </x-card>
        <x-slot:actions>
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
