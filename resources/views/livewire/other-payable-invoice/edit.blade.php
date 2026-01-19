<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Helpers\Code;
use App\Models\Supplier;
use App\Models\Ppn;
use App\Models\OtherPayableInvoice;
use App\Events\OtherPayableInvoiceClosed;

new class extends Component {
    use Toast;

    public OtherPayableInvoice $otherPayableInvoice;

    public $code = '';
    public $invoice_date = '';
    public $due_date = '';
    public $invoice_type = '';
    public $note = '';
    public $supplier_id = '';
    public $top = '';
    public $ppn_id = '';
    public $dpp_amount = 0;
    public $ppn_amount = 0;
    public $stamp_amount = 0;
    public $invoice_amount = 0;

    public $open = true;
    public $closeConfirm = false;
    public $details;
    public Collection $suppliers;
    public Collection $ppns;

    public function mount(): void
    {
        Gate::authorize('update other-payable-invoice');
        $this->fill($this->otherPayableInvoice);
        $this->searchSupplier();
        $this->searchPpn();
        $this->calculate();
    }

    public function with(): array
    {
        $this->open = $this->otherPayableInvoice->status == 'open';
        return [];
    }

    public function searchSupplier(string $value = ''): void
    {
        $selected = Supplier::where('id', intval($this->supplier_id))->get();
        $this->suppliers = Supplier::query()
            ->filterLike('name', $value)
            ->isActive()
            ->orderBy('name')
            ->take(20)
            ->get()
            ->merge($selected);
    }

    public function searchPpn(string $value = ''): void
    {
        $selected = Ppn::where('id', intval($this->ppn_id))->get();
        $this->ppns = Ppn::query()
            ->filterLike('name', $value)
            ->isActive()
            ->orderBy('name')
            ->take(20)
            ->get()
            ->merge($selected);
    }

    public function save($close = false): void
    {
        $this->closeConfirm = false;

        $data = $this->validate([
            'code' => 'required',
            'invoice_date' => 'required',
            'due_date' => 'required',
            'invoice_type' => 'required',
            'note' => 'nullable',
            'supplier_id' => 'required',
            'top' => 'required|integer|gt:0',
            'ppn_id' => 'required',
            'stamp_amount' => 'nullable',
        ]);

        if ($this->otherPayableInvoice->saved == '0') {
            $data['code'] = Code::auto($this->invoice_type);
            $data['saved'] = 1;
        }

        $this->calculate();

        $data['dpp_amount'] = Cast::number($this->dpp_amount);
        $data['ppn_amount'] = Cast::number($this->ppn_amount);
        $data['stamp_amount'] = Cast::number($this->stamp_amount);
        $data['invoice_amount'] = Cast::number($this->invoice_amount);

        $this->otherPayableInvoice->update($data);

        if ($close) {
            $this->close();
        }

        $this->success('Invoice successfully updated.', redirectTo: route('other-payable-invoice.index'));
    }

    #[On('detail-updated')]
    public function detailUpdated(array $data = [])
    {
        $this->dpp_amount = Cast::money($data['dpp_amount'] ?? 0);
        $this->calculate();
    }

    public function updated($property, $value): void
    {
        if (in_array($property, ['ppn_id','stamp_amount']))
        {
            $this->calculate();
        }
    }

    public function calculate()
    {
        $ppn = Ppn::find($this->ppn_id);
        $ppn_value = $ppn->value ?? 0;
        $dpp_amount = Cast::number($this->dpp_amount);
        $stamp_amount = Cast::number($this->stamp_amount);

        $ppn_amount = round(($ppn_value/100) * $dpp_amount, 2);
        $invoice_amount = $dpp_amount + $ppn_amount + $stamp_amount;

        $this->ppn_amount = Cast::money($ppn_amount);
        $this->invoice_amount = Cast::money($invoice_amount);
    }

    public function delete(OtherPayableInvoice $invoice): void
    {
        Gate::authorize('delete other-payable-invoice');
        $invoice->details()->delete();
        $invoice->delete();
        $this->success('Invoice successfully deleted.', redirectTo: route('other-payable-invoice.index'));
    }

    public function void(OtherPayableInvoice $invoice): void
    {
        Gate::authorize('void other-payable-invoice');
        $invoice->update(['status' => 'void']);
        $this->success('Invoice successfully voided.', redirectTo: route('other-payable-invoice.index'));
    }

    public function close(): void
    {
        Gate::authorize('close other-payable-invoice');
        \App\Events\OtherPayableInvoiceClosed::dispatch($this->otherPayableInvoice);
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
                <div class="flex items-center gap-4">
                    <span>Update Other Payable Invoice</span>
                    <x-status-badge :status="$otherPayableInvoice->status" class="uppercase !text-sm" />
                    @if ($otherPayableInvoice->status == 'close')
                    <x-payment-status-badge :data="$otherPayableInvoice" class="uppercase !text-sm" />
                    @endif
                </div>
            </x-slot:title>
            <x-slot:actions>
                <x-button label="Back" link="{{ route('other-payable-invoice.index') }}" icon="o-arrow-uturn-left" class="btn-soft" responsive />

                @if ($otherPayableInvoice->status == 'close')
                <x-button label="Journal" icon="o-document-text" class="btn-accent" onclick="popupWindow('{{ route('print.journal', ['OtherPayableInvoice', base64_encode($otherPayableInvoice->code)]) }}', 'journal', '1000', '460', 'yes', 'center')" responsive />
                @endif
                @if ( $otherPayableInvoice->status == 'open')
                <x-button label="Approve" icon="o-check" @click="$wire.closeConfirm=true" class="btn-success" responsive />
                @endif
                @if ($open)
                <x-button label="Save" icon="o-paper-airplane" wire:click="save" spinner="save" class="btn-primary" responsive />
                @endif
            </x-slot:actions>
        </x-header>
    </div>

    <div class="space-y-4">
        <x-card>
            <x-form wire:submit="save">
                <div class="space-y-4">
                    <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-3 gap-4">
                        <x-input label="Code" wire:model="code" readonly class="bg-base-200" />
                        <x-datetime label="Invoice Date" wire:model="invoice_date" :disabled="!$open" />
                        <x-datetime label="Due Date" wire:model="due_date" :disabled="!$open" />
                        <x-select label="Invoice Type" wire:model="invoice_type" :options="[['id' => 'OP','name' => 'OP']]" placeholder="-- Select --" :disabled="!$open" />
                        <x-choices
                            label="Supplier"
                            wire:model="supplier_id"
                            :options="$suppliers"
                            search-function="searchSupplier"
                            option-label="name"
                            single
                            searchable
                            clearable
                            placeholder="-- Select --"
                            :disabled="!$open"
                        />
                        <x-input label="Top" wire:model="top" :disabled="!$open" />
                        <x-input label="Note" wire:model="note" :disabled="!$open" />
                        <x-input label="DPP" wire:model="dpp_amount" readonly class="bg-base-200" x-mask:dynamic="$money($input,'.',',')" :disabled="!$open" />
                        <x-choices
                            label="PPN"
                            wire:model.live="ppn_id"
                            :options="$ppns"
                            search-function="searchPpn"
                            option-label="name"
                            single
                            searchable
                            clearable
                            placeholder="-- Select --"
                            :disabled="!$open"
                        />
                        <x-input label="Stamp" wire:model.live.debounce.400ms="stamp_amount" class="money" :disabled="!$open" />
                        <x-input label="PPN Amount" wire:model="ppn_amount" readonly class="bg-base-200" />
                        <x-input label="Invoice Amount" wire:model="invoice_amount" readonly class="bg-base-200" />
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
            <livewire:other-payable-invoice.detail
                :id="$otherPayableInvoice->id"
            />
        </div>

        @if ($otherPayableInvoice->saved == '1')
        <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
            <x-card>
                <div class="space-y-4">
                    <x-other-info :data="$otherPayableInvoice" />

                    <h2 class="text-lg font-semibold">Danger Zone</h2>
                    @can('void other-payable-invoice')
                    @if ($otherPayableInvoice->status != 'void')
                    <div class="text-xs">
                        <p>You can cancel a transaction without destroying it with void.</p>
                    </div>
                    <div>
                        <x-button
                            label="Void"
                            icon="o-archive-box-x-mark"
                            wire:click="void('{{ $otherPayableInvoice->id }}')"
                            spinner="void('{{ $otherPayableInvoice->id }}')"
                            wire:confirm="Are you sure you want to void this invoice?"
                            class="btn-error btn-soft"
                        />
                    </div>
                    @endif
                    @endcan

                    @if ($otherPayableInvoice->status == 'void')
                    @can('delete other-payable-invoice')
                    <div class="text-xs">
                        <p>Once you delete a invoice, there is no going back. Please be certain.</p>
                    </div>
                    <div>
                        <x-button
                            label="Delete Permanently"
                            icon="o-trash"
                            wire:click="delete('{{ $otherPayableInvoice->id }}')"
                            spinner="delete('{{ $otherPayableInvoice->id }}')"
                            wire:confirm="Are you sure you want to delete this invoice?"
                            class="btn-error btn-soft"
                        />
                    </div>
                    @endcan
                    @endif
                </div>
            </x-card>
            <x-logs :data="$otherPayableInvoice" />
        </div>
        @endif
    </div>

    <x-modal wire:model="closeConfirm" title="Closing Confirmation" persistent>
        <div class="flex pb-2">
            Are you sure you want to approve this invoice?
        </div>
        <x-slot:actions>
            <div class="flex items-center gap-4">
                <x-button label="Cancel" icon="o-x-mark" @click="$wire.closeConfirm = false" class="" />
                <x-button label="Yes, I am sure" icon="o-check" wire:click="save(true)" spinner="save(true)" class="" />
            </div>
        </x-slot:actions>
    </x-modal>
</div>
