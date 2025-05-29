<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Helpers\Code;
use App\Models\Contact;
use App\Models\Ppn;
use App\Models\Pph;
use App\Models\SalesInvoice;

new class extends Component {
    use Toast;

    public SalesInvoice $salesInvoice;

    public $code = '';
    public $invoice_date = '';
    public $due_date = '';
    public $contact_id = '';
    public $top = '';
    public $ppn_id = '';
    public $pph_id = '';
    public $dpp_amount = 0;
    public $ppn_amount = 0;
    public $pph_amount = 0;
    public $stamp_amount = 0;
    public $invoice_amount = 0;

    public $details;
    public Collection $contacts;
    public Collection $ppns;
    public Collection $pphs;

    public function mount(): void
    {
        Gate::authorize('update sales invoice');
        $this->fill($this->salesInvoice);
        $this->searchContact();
        $this->searchPpn();
        $this->searchPph();
        $this->calculate();
    }

    public function searchContact(string $value = ''): void
    {
        $selected = Contact::where('id', intval($this->contact_id))->get();
        $this->contacts = Contact::query()
            ->filterLike('name', $value)
            ->isActive()
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
            ->take(20)
            ->get()
            ->merge($selected);
    }

    public function save(): void
    {
        $data = $this->validate([
            'code' => 'required',
            'invoice_date' => 'required',
            'due_date' => 'required',
            'contact_id' => 'required',
            'top' => 'required|integer|gt:0',
            'ppn_id' => 'required',
            'pph_id' => 'required',
            'stamp_amount' => 'nullable',
            'details' => new \App\Rules\SalesOrderDetailCheck($this->salesInvoice),
        ]);

        unset($data['details']);

        if ($this->salesInvoice->saved == '0') {
            $data['code'] = Code::auto('SINV');
            $data['saved'] = 1;
        }

        $this->calculate();

        $data['dpp_amount'] = Cast::number($this->dpp_amount);
        $data['ppn_amount'] = Cast::number($this->ppn_amount);
        $data['pph_amount'] = Cast::number($this->pph_amount);
        $data['stamp_amount'] = Cast::number($this->stamp_amount);
        $data['invoice_amount'] = Cast::number($this->invoice_amount);

        $this->salesInvoice->update($data);

        $this->success('Invoice successfully updated.', redirectTo: route('sales-invoice.index'));
    }

    #[On('detail-updated')]
    public function detailUpdated(array $data = [])
    {
        $this->dpp_amount = Cast::money($data['dpp_amount'] ?? 0);
        $this->calculate();
    }

    public function updated($property, $value): void
    {
        if ( in_array($property, ['ppn_id','pph_id','stamp_amount']))
        {
            $this->calculate();
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
}; ?>

<div>
    <x-header title="Update Sales Invoice" separator>
        <x-slot:actions>
            <x-button label="Back" link="{{ route('sales-invoice.index') }}" icon="o-arrow-uturn-left" />
        </x-slot:actions>
    </x-header>

    <div class="space-y-4">
        <x-card>
            <x-form wire:submit="save">
                <div class="space-y-4">
                    <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-3 gap-4">
                        <x-input label="Code" wire:model="code" readonly class="bg-base-200" />
                        <x-datetime label="Invoice Date" wire:model="invoice_date" />
                        <x-datetime label="Due Date" wire:model="due_date" />
                        <x-choices label="Customer" wire:model="contact_id" :options="$contacts" search-function="searchContact" option-label="name" single searchable placeholder="-- Select --" />
                        <x-input label="Top" wire:model="top" />
                        <x-input label="DPP" wire:model="dpp_amount" readonly class="bg-base-200" x-mask:dynamic="$money($input,'.',',')" />
                        <x-choices label="PPN" wire:model.live="ppn_id" :options="$ppns" search-function="searchPpn" option-label="name" single searchable placeholder="-- Select --" />
                        <x-choices label="PPH" wire:model.live="pph_id" :options="$pphs" search-function="searchPph" option-label="name" single searchable placeholder="-- Select --" />
                        <x-input label="Stamp" wire:model.live.debounce.400ms="stamp_amount" x-mask:dynamic="$money($input,'.',',')" />
                        <x-input label="PPN Amount" wire:model="ppn_amount" readonly class="bg-base-200" />
                        <x-input label="PPH Amount" wire:model="pph_amount" readonly class="bg-base-200" />
                        <x-input label="Invoice Amount" wire:model="invoice_amount" readonly class="bg-base-200" />
                    </div>
                </div>
                <x-slot:actions>
                    <x-button label="Cancel" link="{{ route('sales-invoice.index') }}" />
                    <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
                </x-slot:actions>
            </x-form>
        </x-card>

        @error('details')
            <div class="flex justify-center">
                <span class="text-red-500 text-sm p-1">{{ $message }}</span>
            </div>
        @enderror

        <div class="overflow-x-auto">
            <livewire:sales-invoice.detail :id="$salesInvoice->id" />
        </div>
    </div>
</div>
