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
use App\Models\Pph;
use App\Models\PurchaseInvoice;
use App\Events\PurchaseInvoiceClosed;

new class extends Component {
    use Toast;

    public PurchaseInvoice $purchaseInvoice;

    public $code = '';
    public $invoice_date = '';
    public $due_date = '';
    public $transport = '';
    public $service_type = '';
    public $invoice_type = '';
    public $note = '';
    public $supplier_id = '';
    public $top = '';
    public $ppn_id = '';
    public $pph_id = '';
    public $dpp_amount = 0;
    public $ppn_amount = 0;
    public $pph_amount = 0;
    public $stamp_amount = 0;
    public $invoice_amount = 0;

    public $open = true;
    public $closeConfirm = false;
    public $details;
    public Collection $suppliers;
    public Collection $ppns;
    public Collection $pphs;

    public function mount(): void
    {
        Gate::authorize('update purchase-invoice');
        $this->fill($this->purchaseInvoice);
        $this->searchSupplier();
        $this->searchPpn();
        $this->searchPph();
        $this->calculate();
    }

    public function with(): array
    {
        $this->open = $this->purchaseInvoice->status == 'open';
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

    public function searchPph(string $value = ''): void
    {
        $selected = Pph::where('id', intval($this->pph_id))->get();
        $this->pphs = Pph::query()
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
            'transport' => 'required',
            'service_type' => 'required',
            'invoice_type' => 'required',
            'note' => 'nullable',
            'supplier_id' => 'required',
            'top' => 'required|integer|gt:0',
            'ppn_id' => 'required',
            'pph_id' => 'required',
            'stamp_amount' => 'nullable',
        ]);

        if ($this->purchaseInvoice->saved == '0') {
            $data['code'] = Code::auto($this->invoice_type);
            $data['saved'] = 1;
        }

        $this->calculate();

        $data['dpp_amount'] = Cast::number($this->dpp_amount);
        $data['ppn_amount'] = Cast::number($this->ppn_amount);
        $data['pph_amount'] = Cast::number($this->pph_amount);
        $data['stamp_amount'] = Cast::number($this->stamp_amount);
        $data['invoice_amount'] = Cast::number($this->invoice_amount);

        $this->purchaseInvoice->update($data);

        if ($close) {
            $this->close();
        }

        $this->success('Invoice successfully updated.', redirectTo: route('purchase-invoice.index'));
    }

    #[On('detail-updated')]
    public function detailUpdated(array $data = [])
    {
        $this->dpp_amount = Cast::money($data['dpp_amount'] ?? 0);
        $this->calculate();
    }

    public function updated($property, $value): void
    {
        if (in_array($property, ['ppn_id','pph_id','stamp_amount']))
        {
            $this->calculate();
        }

        if (in_array($property, ['transport']))
        {
            $this->dispatch('transport-changed', value: $value);
        }

        if (in_array($property, ['service_type']))
        {
            $this->dispatch('service-type-changed', value: $value);
        }
    }

    public function calculate()
    {
        $ppn = Ppn::find($this->ppn_id);
        $pph = Pph::find($this->pph_id);
        $ppn_value = $ppn->value ?? 0;
        $pph_value = $pph->value ?? 0;
        $dpp_amount = Cast::number($this->dpp_amount);
        $stamp_amount = Cast::number($this->stamp_amount);

        $ppn_amount = round(($ppn_value/100) * $dpp_amount, 2);
        $pph_amount = round(($pph_value/100) * $dpp_amount, 2);
        $invoice_amount = $dpp_amount + $ppn_amount + $stamp_amount;

        $this->ppn_amount = Cast::money($ppn_amount);
        $this->pph_amount = Cast::money($pph_amount);
        $this->invoice_amount = Cast::money($invoice_amount);
    }

    public function delete(PurchaseInvoice $purchaseInvoice): void
    {
        Gate::authorize('delete purchase-invoice');
        $purchaseInvoice->details()->delete();
        $purchaseInvoice->delete();
        $this->success('Invoice successfully deleted.', redirectTo: route('purchase-invoice.index'));
    }

    public function void(PurchaseInvoice $purchaseInvoice): void
    {
        Gate::authorize('void purchase-invoice');
        $purchaseInvoice->update(['status' => 'void']);
        $this->success('Invoice successfully voided.', redirectTo: route('purchase-invoice.index'));
    }

    public function close(): void
    {
        Gate::authorize('close purchase-invoice');
        \App\Events\PurchaseInvoiceClosed::dispatch($this->purchaseInvoice);
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
                    <span>Update Purchase Invoice</span>
                    <x-status-badge :status="$purchaseInvoice->status" class="uppercase !text-sm" />
                    @if ($purchaseInvoice->status == 'close')
                    <x-payment-status-badge :data="$purchaseInvoice" class="uppercase !text-sm" />
                    @endif
                </div>
            </x-slot:title>
            <x-slot:actions>
                <x-button label="Back" link="{{ route('purchase-invoice.index') }}" icon="o-arrow-uturn-left" class="btn-soft" responsive />

                @if ($purchaseInvoice->status == 'close')
                <x-button label="Journal" icon="o-document-text" class="btn-accent" onclick="popupWindow('{{ route('print.journal', ['PurchaseInvoice', base64_encode($purchaseInvoice->code)]) }}', 'journal', '1000', '460', 'yes', 'center')" responsive />
                @endif
                @if ( $purchaseInvoice->status == 'open')
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
                        <x-select label="Transport" wire:model.live="transport" :options="\App\Enums\Transport::toSelect()" placeholder="-- Select --" :disabled="!$open" wire:loading.attr="disabled" />
                        <x-select label="Service Type" wire:model.live="service_type" :options="\App\Enums\ServiceType::toSelect()" placeholder="-- Select --" :disabled="!$open" wire:loading.attr="disabled" />
                        <x-select label="Invoice Type" wire:model="invoice_type" :options="[['id' => 'AP','name' => 'AP']]" placeholder="-- Select --" :disabled="!$open" />
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
                        <x-choices
                            label="PPH"
                            wire:model.live="pph_id"
                            :options="$pphs"
                            search-function="searchPph"
                            option-label="name"
                            single
                            searchable
                            clearable
                            placeholder="-- Select --"
                            :disabled="!$open"
                        />
                        <x-input label="Stamp" wire:model.live.debounce.400ms="stamp_amount" class="money" :disabled="!$open" />
                        <x-input label="PPN Amount" wire:model="ppn_amount" readonly class="bg-base-200" />
                        <x-input label="PPH Amount" wire:model="pph_amount" readonly class="bg-base-200" />
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
            <livewire:purchase-invoice.detail
                :id="$purchaseInvoice->id"
                :transport="$transport"
                :service_type="$service_type"
            />
        </div>

        @if ($purchaseInvoice->saved == '1')
        <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
            <x-card>
                <div class="space-y-4">
                    <x-other-info :data="$purchaseInvoice" />

                    <h2 class="text-lg font-semibold">Danger Zone</h2>
                    @can('void purchase-invoice')
                    @if ($purchaseInvoice->status != 'void')
                    <div class="text-xs">
                        <p>You can cancel a transaction without destroying it with void.</p>
                    </div>
                    <div>
                        <x-button
                            label="Void"
                            icon="o-archive-box-x-mark"
                            wire:click="void('{{ $purchaseInvoice->id }}')"
                            spinner="void('{{ $purchaseInvoice->id }}')"
                            wire:confirm="Are you sure you want to void this invoice?"
                            class="btn-error btn-soft"
                        />
                    </div>
                    @endif
                    @endcan

                    @if ($purchaseInvoice->status == 'void')
                    @can('delete purchase-invoice')
                    <div class="text-xs">
                        <p>Once you delete a invoice, there is no going back. Please be certain.</p>
                    </div>
                    <div>
                        <x-button
                            label="Delete Permanently"
                            icon="o-trash"
                            wire:click="delete('{{ $purchaseInvoice->id }}')"
                            spinner="delete('{{ $purchaseInvoice->id }}')"
                            wire:confirm="Are you sure you want to delete this invoice?"
                            class="btn-error btn-soft"
                        />
                    </div>
                    @endcan
                    @endif
                </div>
            </x-card>
            <x-logs :data="$purchaseInvoice" />
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
