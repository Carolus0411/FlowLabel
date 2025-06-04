<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Illuminate\Support\Str;

new class extends Component {
    use Toast;

    public $account_receivable_code = '';
    public $account_payable_code = '';
    public $vat_out_code = '';
    public $stamp_code = '';

    public function mount(): void
    {
        Gate::authorize('view account mapping');

        $this->account_receivable_code = settings('account_receivable_code');
        $this->account_payable_code = settings('account_payable_code');
        $this->vat_out_code = settings('vat_out_code');
        $this->stamp_code = settings('stamp_code');
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
                <x-choices-offline
                    label="Account Receivable"
                    :options="\App\Models\Coa::query()->isActive()->get()"
                    wire:model="account_receivable_code"
                    option-value="code"
                    option-label="full_name"
                    single
                    searchable
                    placeholder="-- Select --"
                />
                <x-choices-offline
                    label="Account Payable"
                    :options="\App\Models\Coa::query()->isActive()->get()"
                    wire:model="account_payable_code"
                    option-value="code"
                    option-label="full_name"
                    single
                    searchable
                    placeholder="-- Select --"
                />
                <x-choices-offline
                    label="Vat Out"
                    :options="\App\Models\Coa::query()->isActive()->get()"
                    wire:model="vat_out_code"
                    option-value="code"
                    option-label="full_name"
                    single
                    searchable
                    placeholder="-- Select --"
                />
                <x-choices-offline
                    label="Stamp"
                    :options="\App\Models\Coa::query()->isActive()->get()"
                    wire:model="stamp_code"
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
