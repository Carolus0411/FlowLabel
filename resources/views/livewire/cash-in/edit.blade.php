<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Mary\Traits\Toast;
use App\Traits\ContactChoice;
use App\Traits\CashAccountChoice;
use App\Helpers\Cast;
use App\Helpers\Code;
use App\Models\CashAccount;
use App\Models\CashIn;

new class extends Component {
    use Toast, ContactChoice, CashAccountChoice;

    public CashIn $cashIn;

    public $code = '';
    public $date = '';
    public $type = '';
    public $note = '';
    public $cash_account_id = '';
    public $contact_id = '';
    public $status = '';
    public $total_amount = 0;

    public $open = true;
    public $closeConfirm = false;
    public $details;

    public function mount(): void
    {
        Gate::authorize('update cash-in');
        $this->fill($this->cashIn);
    }

    public function with(): array
    {
        $this->open = $this->cashIn->status == 'open';
        $this->cash_account_id = $this->cash_account_id ?? '';
        $this->contact_id = $this->contact_id ?? '';
        return [];
    }

    public function save($close = false): void
    {
        $this->closeConfirm = false;

        $data = $this->validate([
            'code' => 'required',
            'date' => 'required',
            'type' => 'nullable',
            'note' => 'nullable',
            'cash_account_id' => 'required',
            'contact_id' => 'required',
            'details' => new \App\Rules\CashInDetailCheck($this->cashIn),
        ]);

        unset($data['details']);

        if ($this->cashIn->saved == '0') {
            $cashAccount = CashAccount::find($this->cash_account_id);
            $prefix = settings('cash_in_code') . $cashAccount->code;
            $code = Code::auto($prefix);
            $data['code'] = $code;
            $data['saved'] = 1;
        }

        $total_amount = $this->cashIn->details()->sum('amount');
        $data['total_amount'] = Cast::number($total_amount);

        $this->cashIn->update($data);

        if ($close) {
            $this->close();
        }

        $this->success('Cash successfully updated.', redirectTo: route('cash-in.index'));
    }

    #[On('detail-updated')]
    public function detailUpdated(array $data = [])
    {
        $this->total_amount = Cast::money($data['total_amount'] ?? 0);
    }

    public function updated($property, $value): void
    {

    }

    public function delete(CashIn $cashIn): void
    {
        Gate::authorize('delete cash-in');
        \App\Jobs\CashInDelete::dispatchSync($this->cashIn);
        $this->success('Cash successfully deleted.', redirectTo: route('cash-in.index'));
    }

    public function close(): void
    {
        Gate::authorize('close cash-in');
        $this->closeConfirm = false;
        \App\Jobs\CashInApprove::dispatchSync($this->cashIn);
    }

    public function void(): void
    {
        Gate::authorize('void cash-in');
        \App\Jobs\CashInVoid::dispatchSync($this->cashIn);
        $this->success('Cash successfully voided.', redirectTo: route('cash-in.index'));
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
                    <span>Update Cash In</span>
                    <x-status-badge :status="$cashIn->status" class="uppercase !text-sm" />
                </div>
            </x-slot:title>
            <x-slot:actions>
                <x-button label="Back" link="{{ route('cash-in.index') }}" icon="o-arrow-uturn-left" class="btn-soft" />
                @if ($cashIn->status == 'close')
                <x-button label="Journal" icon="o-document-text" class="btn-accent" onclick="popupWindow('{{ route('print.journal', ['CashIn', base64_encode($cashIn->code)]) }}', 'journal', '1000', '460', 'yes', 'center')" />
                @endif
                @if ($cashIn->status == 'open')
                <x-button label="Approve" icon="o-check" @click="$wire.closeConfirm=true" class="btn-success" />
                @endif
                @if ($open)
                <x-button label="Save" icon="o-paper-airplane" wire:click="save" spinner="save" class="btn-primary" />
                @endif
                {{-- @if ($open)
                <x-button label="Save & Close" icon="o-paper-airplane" wire:click="save(true)" spinner="save(true)" class="btn-primary" />
                @endif --}}
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
                        <x-select label="Type" wire:model="type" :options="\App\Enums\IncomeType::toSelect()" placeholder="-- Select --" :disabled="!$open" />
                        <x-choices
                            label="Account"
                            wire:model="cash_account_id"
                            :options="$cashAccountChoice"
                            search-function="searchCashAccount"
                            option-label="name"
                            option-sub-label="code"
                            single
                            searchable
                            clearable
                            placeholder="-- Select --"
                            :disabled="!$open"
                        />
                        <x-choices
                            label="Contact"
                            wire:model="contact_id"
                            :options="$contactChoice"
                            search-function="searchContact"
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
            <livewire:cash-in.detail :id="$cashIn->id" />
        </div>

        @if ($cashIn->saved == '1')
        <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
            <x-card>
                <div class="space-y-4">
                    <x-other-info :data="$cashIn" />

                    @can('create request')
                    <livewire:request.button :model="$cashIn" />
                    @endcan

                </div>
            </x-card>
            <x-logs :data="$cashIn" />
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
