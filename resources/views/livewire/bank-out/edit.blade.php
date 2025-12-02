<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Mary\Traits\Toast;
use App\Traits\SupplierChoice;
use App\Traits\BankAccountChoice;
use App\Helpers\Cast;
use App\Helpers\Code;
use App\Models\BankAccount;
use App\Models\BankOut;

new class extends Component {
    use Toast, SupplierChoice, BankAccountChoice;

    public BankOut $bankOut;

    public $code = '';
    public $date = '';
    public $note = '';
    public $bank_account_id = '';
    public $supplier_id = '';
    public $status = '';
    public $total_amount = 0;

    public $open = true;
    public $closeConfirm = false;
    public $details;

    public function mount(): void
    {
        Gate::authorize('update bank-out');
        $this->fill($this->bankOut);
    }

    public function with(): array
    {
        $this->open = $this->bankOut->status == 'open';
        $this->bank_account_id = $this->bank_account_id ?? '';
        $this->supplier_id = $this->supplier_id ?? '';
        return [];
    }

    public function save($close = false): void
    {
        $this->closeConfirm = false;

        $data = $this->validate([
            'code' => 'required',
            'date' => 'required',
            'note' => 'nullable',
            'bank_account_id' => 'required',
            'supplier_id' => 'required',
            'details' => new \App\Rules\BankOutDetailCheck($this->bankOut),
        ]);

        unset($data['details']);

        if ($this->bankOut->saved == '0') {
            $bankAccount = BankAccount::find($this->bank_account_id);
            $prefix = settings('bank_out_code') . $bankAccount->code;
            $code = Code::auto($prefix);
            $data['code'] = $code;
            $data['saved'] = 1;
        }

        $total_amount = $this->bankOut->details()->sum('amount');
        $data['total_amount'] = Cast::number($total_amount);

        $this->bankOut->update($data);

        if ($close) {
            $this->close();
        }

        $this->success('Bank successfully updated.', redirectTo: route('bank-out.index'));
    }

    #[On('detail-updated')]
    public function detailUpdated(array $data = [])
    {
        $this->total_amount = Cast::money($data['total_amount'] ?? 0);
    }

    public function updated($property, $value): void
    {

    }

    public function delete(BankOut $bankOut): void
    {
        Gate::authorize('delete bank-out');
        \App\Jobs\BankOutDelete::dispatchSync($this->bankOut);
        $this->success('Bank successfully deleted.', redirectTo: route('bank-out.index'));
    }

    public function close(): void
    {
        Gate::authorize('close bank-out');
        $this->closeConfirm = false;
        \App\Jobs\BankOutApprove::dispatchSync($this->bankOut);
    }

    public function void(): void
    {
        Gate::authorize('void bank-out');
        \App\Jobs\BankOutVoid::dispatchSync($this->bankOut);
        $this->success('Bank successfully voided.', redirectTo: route('bank-out.index'));
    }
}; ?>

<div
    x-data="{
        init : function() {
            setTimeout(function () {
                mask()
            }, 100);
        }
    }"
>
    <div class="lg:top-[65px] lg:sticky z-10 bg-base-200 pb-0 pt-3">
        <x-header separator>
            <x-slot:title>
                <div class="flex items-center-safe gap-4">
                    <span>Update Bank Out</span>
                    <x-status-badge :status="$bankOut->status" class="uppercase !text-sm" />
                </div>
            </x-slot:title>
            <x-slot:actions>
                <x-button label="Back" link="{{ route('bank-out.index') }}" icon="o-arrow-uturn-left" class="btn-soft" />
                @if ($bankOut->status == 'close')
                <x-button label="Journal" icon="o-document-text" class="btn-accent" onclick="popupWindow('{{ route('print.journal', ['BankOut', base64_encode($bankOut->code)]) }}', 'journal', '1000', '460', 'yes', 'center')" />
                @endif
                @if ($bankOut->status == 'open')
                <x-button label="Approve" icon="o-check" @click="$wire.closeConfirm=true" class="btn-success" />
                @endif
                @if ($open)
                <x-button label="Save" icon="o-paper-airplane" wire:click="save" spinner="save" class="btn-primary" />
                @endif
            </x-slot:actions>
        </x-header>
    </div>

    <div class="space-y-4">
        <x-card>
            <x-form wire:submit="save">
                <div class="space-y-4">
                    <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-3 gap-4">
                        <x-input label="Code" wire:model="code" readonly :disabled="!$open" />
                        <x-datetime label="Date" wire:model="date" :disabled="!$open" />
                        <x-choices
                            label="Account"
                            wire:model="bank_account_id"
                            :options="$bankAccountChoice"
                            search-function="searchBankAccount"
                            option-label="name"
                            option-sub-label="code"
                            single
                            searchable
                            clearable
                            placeholder="-- Select --"
                            :disabled="!$open"
                        />
                        <x-choices
                            label="Supplier"
                            wire:model="supplier_id"
                            :options="$supplierChoice"
                            search-function="searchSupplier"
                            option-label="name"
                            single
                            searchable
                            clearable
                            placeholder="-- Select --"
                            :disabled="!$open"
                        />
                        <x-input label="Total Amount" wire:model="total_amount" readonly x-mask:dynamic="$money($input,'.',',')" />
                        <x-input label="Note" wire:model="note" :disabled="!$open" />
                    </div>
                </div>
            </x-form>
        </x-card>

        @error('details')
            <div class="flex justify-center">
                <span class="text-red-500 text-sm p-1">{{ $message }}</span>
            </div>
        @enderror

        <div class="overflow-x-auto">
            <livewire:bank-out.detail :id="$bankOut->id" />
        </div>

        @if ($bankOut->saved == '1')
        <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
            <x-card>
                <div class="space-y-4">
                    <x-other-info :data="$bankOut" />

                    @can('create request')
                    <livewire:request.button :model="$bankOut" />
                    @endcan

                </div>
            </x-card>
            <x-logs :data="$bankOut" />
        </div>
        @endif

    </div>

    <x-modal wire:model="closeConfirm" title="Closing Confirmation" persistent>
        <div class="flex pb-2">
            Are you sure you want to close this?
        </div>
        <x-slot:actions>
            <div class="flex items-center gap-4">
                <x-button label="Cancel" icon="o-x-mark" @click="$wire.closeConfirm = false" class="" />
                <x-button label="Yes, I am sure" icon="o-check" wire:click="save(true)" spinner="save(true)" class="" />
                {{-- <x-button label="Yes, I am sure" icon="o-check" wire:click="close" spinner="close" class="" /> --}}
            </div>
        </x-slot:actions>
    </x-modal>
</div>
