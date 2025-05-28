<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Helpers\Code;
use App\Models\Contact;
use App\Models\SalesInvoice;

new class extends Component {
    use Toast;

    public SalesInvoice $salesInvoice;

    public $code = '';
    public $invoice_date = '';
    public $due_date = '';
    public $contact_id = '';
    public $top = '';

    public $details;
    public Collection $contacts;

    public function mount(): void
    {
        Gate::authorize('update sales invoice');
        $this->fill($this->salesInvoice);
        $this->searchContact();
    }

    public function searchContact(string $value = ''): void
    {
        $selected = Contact::where('id', intval($this->contact_id))->get();
        $this->contacts = Contact::query()
            ->filterLike('name', $value)
            ->isActive()
            ->take(50)
            ->get()
            ->merge($selected);
    }

    public function save(): void
    {
        $data = $this->validate([
            'code' => 'required',
            'invoice_date' => 'required',
            'due_date' => 'required',
            'contact_id' => 'required|integer|gt:0',
            'top' => 'required|integer|gt:0',
            'details' => new \App\Rules\SalesOrderDetailCheck($this->salesInvoice),
        ]);

        unset($data['details']);

        if ($this->salesInvoice->saved == '0') {
            $data['code'] = Code::auto('SINV');
            $data['saved'] = 1;
        }

        $this->salesInvoice->update($data);

        $this->success('Invoice successfully updated.', redirectTo: route('sales-invoice.index'));
    }
}; ?>

<div>
    <x-header title="Update Sales Invoice" separator>
        <x-slot:actions>
            <x-button label="Back" link="{{ route('sales-invoice.index') }}" icon="o-arrow-uturn-left" />
        </x-slot:actions>
    </x-header>

    <x-card>
        <x-form wire:submit="save">
            <div class="space-y-4">
                <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-3 gap-4">
                    <x-input label="Code" wire:model="code" readonly class="bg-base-200" />
                    <x-datetime label="Invoice Date" wire:model="invoice_date" />
                    <x-datetime label="Due Date" wire:model="due_date" />
                    <x-choices label="Customer" wire:model="contact_id" :options="$contacts" search-function="searchContact" option-label="name" single searchable placeholder="-- Select --" />
                    <x-input label="Top" wire:model="top" />
                    <x-input label="DPP" wire:model="dpp" readonly class="bg-base-200" />
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
