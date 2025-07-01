<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Models\Coa;

new class extends Component {
    use Toast;

    public $coa = [];
    public $cols = [];
    public $label = '';
    public $key = '';
    public $value = '';
    public bool $drawer = false;
    public Collection $coaAR, $coaAP, $coaVatOut, $coaStamp;

    public function mount(): void
    {
        Gate::authorize('view account mapping');

        // dd(array_diff(array_keys(get_class_vars(get_class($this))), array_keys(get_class_vars(get_parent_class($this)))));

        $this->cols = [
            'account_receivable_code' => 'Account Receivable',
            'account_payable_code' => 'Account Payable',
            'vat_out_code' => 'VAT Out',
            'stamp_code' => 'Stamp',
            'cash_account_code' => 'Cash Account',
            'bank_account_code' => 'Bank Account',
            'ar_prepaid_code' => 'AR Prepaid',
        ];

        foreach (Coa::get() as $coa) {
            $this->coa[$coa->code] = $coa->name;
        }
    }

    #[Computed]
    public function toggle()
    {
        return $this->key;
    }

    public function save(): void
    {
        $data = $this->validate([
            'key' => 'required',
            'value' => 'required',
        ]);

        settings([
            $this->key => $this->value
        ]);

        $this->drawer = false;
        $this->success('Account successfully updated.');
    }

    public function clearForm(): void
    {
        $this->label = '';
        $this->key = '';
        $this->value = '';
        $this->resetValidation();
    }

    public function edit($label, $key): void
    {
        $this->clearForm();
        $this->label = $label;
        $this->key = $key;
        $this->value = settings($key);
        $this->drawer = true;
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
            <table class="table table-zebra">
            <tbody>
            @foreach ($cols as $key => $label)
            @php
            $value = settings($key);
            @endphp
            <tr wire:key="{{ $key }}" wire:click="edit('{{ $label }}','{{ $key }}')" wire:loading.class="cursor-wait" class="divide-x divide-gray-200 dark:divide-gray-900 hover:bg-yellow-50 dark:hover:bg-gray-800 cursor-pointer">
                <td class="lg:w-[300px]">{{ $label }}</td>
                <td>{{ $value }}{{ isset($coa[$value]) ? ', ' . $coa[$value] : '' }}</td>
            </tr>
            @endforeach
            </tbody>
            </table>
        </x-card>
        <x-slot:actions>
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>

    {{-- FORM --}}
    <x-drawer wire:model="drawer" title="Create Item" right separator with-close-button class="lg:w-1/3">
        <x-form wire:submit="save">
            <div class="space-y-4">
                <x-input label="Description" wire:model="label" readonly />
                <x-input label="Key" wire:model="key" disabled />

                @if (in_array($this->toggle, ['cash_account_code','bank_account_code']))
                <x-textarea wire:model="value" rows="3" />
                @else
                <x-choices-offline
                    label="Value"
                    :options="\App\Models\Coa::query()->isActive()->orderBy('code')->get()"
                    wire:model="value"
                    option-value="code"
                    option-label="full_name"
                    single
                    searchable
                    placeholder="-- Select --"
                />
                @endif
            </div>
            <x-slot:actions>
                <x-button label="Save" icon="o-paper-airplane" type="submit" spinner="save" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-drawer>
</div>
