<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Helpers\Code;
use App\Models\SalesOrder;
use App\Models\Contact;
use App\Models\Ppn;
use App\Models\Pph;

new class extends Component {
    use Toast;

    public SalesOrder $salesOrder;

    public $code = '';
    public $order_date = '';
    public $due_date = '';
    public $invoice_type = '';
    public $note = '';
    public $contact_id = '';
    public $top = '';
    public $ppn_id = '';
    public $pph_id = '';
    public $dpp_amount = 0;
    public $ppn_amount = 0;
    public $pph_amount = 0;
    public $stamp_amount = 0;
    public $order_amount = 0;
    public bool $is_void = false;

    public bool $closeConfirm = false;

    public Collection $contacts;
    public Collection $ppns;
    public Collection $pphs;

    public function mount(SalesOrder $salesOrder): void
    {
        // Gate::authorize('update sales-order');

        $this->salesOrder = $salesOrder->load(['contact', 'ppn', 'pph']);

        if (!$this->salesOrder || !$this->salesOrder->exists) {
            $this->error('Sales order not found');
            return;
        }

        $this->fill($this->salesOrder);
        $this->searchContact();
        $this->searchPpn();
        $this->searchPph();
    }

    public function searchContact(string $value = ''): void
    {
        $selected = Contact::where('id', intval($this->contact_id))->get();
        $this->contacts = Contact::query()
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

    public function save(): void
    {
        $data = $this->validate([
            'code' => 'required',
            'order_date' => 'required',
            'due_date' => 'required',
            'invoice_type' => 'required',
            'note' => 'nullable',
            'contact_id' => 'nullable',
            'top' => 'nullable|integer|gt:0',
            'ppn_id' => 'nullable',
            'pph_id' => 'nullable',
            'stamp_amount' => 'nullable',
        ]);

        $this->calculate();

        if ($this->salesOrder->saved == 0 || $this->salesOrder->saved == '0') {
            $data['code'] = Code::auto($this->invoice_type);
            $data['saved'] = 1;
        }

        $data['dpp_amount'] = Cast::number($this->dpp_amount);
        $data['ppn_amount'] = Cast::number($this->ppn_amount);
        $data['pph_amount'] = Cast::number($this->pph_amount);
        $data['stamp_amount'] = Cast::number($this->stamp_amount);
        $data['order_amount'] = Cast::number($this->order_amount);
        $data['saved'] = 1;
        $data['updated_by'] = auth()->user()->id ?? 1;

        try {
            $this->salesOrder->update($data);
            $this->success('Order successfully updated.', redirectTo: route('sales-order.index'));
        } catch (\Exception $e) {
            $this->error('Failed to save order: ' . $e->getMessage());
        }
    }

    public function voidOrder(): void
    {
        $this->salesOrder->update(['status' => 'void']);
        $this->success('Order successfully voided.', redirectTo: route('sales-order.index'));
    }

    public function close(): void
    {
        // Gate::authorize('close sales-order');
        $this->salesOrder->update(['status' => 'close']);
        $this->closeConfirm = false;
        $this->success('Order successfully approved.', redirectTo: route('sales-order.index'));
    }

    public function delete(): void
    {
        $this->salesOrder->delete();
        $this->success('Order successfully deleted.', redirectTo: route('sales-order.index'));
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
        $order_amount = $dpp_amount + $ppn_amount + $stamp_amount;

        $this->ppn_amount = Cast::money($ppn_amount);
        $this->pph_amount = Cast::money($pph_amount);
        $this->order_amount = Cast::money($order_amount);
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
}; ?>

<div>
    <div class="lg:top-[65px] lg:sticky z-10 bg-base-200 pb-0 pt-3">
        <x-header separator>
            <x-slot:title>
                <div class="flex items-center gap-4">
                    <span>Update Sales Order</span>
                    <x-status-badge :status="$salesOrder->status" class="uppercase text-sm!" />
                </div>
            </x-slot:title>
            <x-slot:actions>
                <x-button label="Back" link="{{ route('sales-order.index') }}" icon="o-arrow-uturn-left" class="btn-soft" responsive />
                @if ($salesOrder->status == 'open')
                <x-button label="Approve" icon="o-check" @click="$wire.closeConfirm=true" class="btn-success" responsive />
                @endif
                <x-button label="Save" icon="o-paper-airplane" wire:click.prevent="save" spinner="save" class="btn-primary" responsive />
            </x-slot:actions>
        </x-header>
    </div>

    <div class="space-y-4">
        <x-card>
            <div class="space-y-4">
                <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-3 gap-4">
                    <x-input label="Code" wire:model="code" placeholder="Auto" readonly class="bg-base-200" />
                    <x-datetime label="Order Date" wire:model="order_date" />
                    <x-datetime label="Due Date" wire:model="due_date" />
                    <x-select label="Order Type" wire:model="invoice_type" :options="[['id' => 'SO','name' => 'SO']]" placeholder="-- Select --" />
                    <x-choices
                        label="Contact"
                        wire:model="contact_id"
                        :options="$contacts"
                        search-function="searchContact"
                        option-label="name"
                        single
                        searchable
                        clearable
                        placeholder="-- Select --"
                    />
                    <x-input label="Top" wire:model="top" />
                    <x-input label="Note" wire:model="note" />
                    <x-input label="DPP" wire:model="dpp_amount" readonly class="bg-base-200" x-mask:dynamic="$money($input,'.',',')" />
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
                    />
                    <x-input label="Stamp" wire:model.live.debounce.400ms="stamp_amount" class="money" />
                    <x-input label="PPN Amount" wire:model="ppn_amount" readonly class="bg-base-200" />
                    <x-input label="PPH Amount" wire:model="pph_amount" readonly class="bg-base-200" />
                    <x-input label="Order Amount" wire:model="order_amount" readonly class="bg-base-200" />
                </div>
            </div>
        </x-card>

        <div class="overflow-x-auto">
            <livewire:sales-order.detail
                :id="$salesOrder->id ?? 'new'"
            />
        </div>
    </div>

    <x-modal wire:model="closeConfirm" title="Closing Confirmation" persistent>
        <div class="flex pb-2">
            Are you sure you want to approve this order?
        </div>
        <x-slot:actions>
            <div class="flex items-center gap-4">
                <x-button label="Cancel" icon="o-x-mark" @click="$wire.closeConfirm = false" class="" />
                <x-button label="Yes, I am sure" icon="o-check" wire:click="close" spinner="close" class="" />
            </div>
        </x-slot:actions>
    </x-modal>
</div>
