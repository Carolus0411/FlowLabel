<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Mary\Traits\Toast;
use App\Traits\ContactChoice;
use App\Helpers\Cast;
use App\Helpers\Code;
use App\Models\Contact;
use App\Models\SalesSettlement;

new class extends Component {
    use Toast, ContactChoice;

    public SalesSettlement $salesSettlement;

    public $code = '';
    public $date = '';
    public $note = '';
    public $contact_id = '';
    public $status = '';
    public $source_amount = 0;
    public $paid_amount = 0;
    public $balance_amount = 0;

    public $open = true;
    public $closeConfirm = false;
    public $validityStatus = false;
    public $validityMessage = '';

    public $details;

    public function mount(): void
    {
        Gate::authorize('update sales-settlement');
        $this->fill($this->salesSettlement);
        $this->validity();
    }

    public function with(): array
    {
        $this->open = $this->salesSettlement->status == 'open';
        $this->contact_id = $this->contact_id ?? '';
        return [];
    }

    public function save($close = false): void
    {
        $this->closeConfirm = false;

        $data = $this->validate([
            'code' => 'required',
            'date' => 'required',
            'note' => 'nullable',
            'contact_id' => 'required',
            'details' => new \App\Rules\SalesSettlementDetailCheck($this->salesSettlement),
        ]);

        unset($data['details']);

        $data['source_amount'] = $this->salesSettlement->sources()->sum('amount');
        $data['paid_amount'] = $this->salesSettlement->details()->sum('amount');

        if ($this->salesSettlement->saved == '0') {
            $code = Code::auto('SS');
            $data['code'] = $code;
            $data['saved'] = 1;
            $this->salesSettlement->sources()->update(['sales_settlement_code' => $code]);
            $this->salesSettlement->details()->update(['sales_settlement_code' => $code]);
        }

        // refresh model
        // $this->salesSettlement = SalesSettlement::find($this->salesSettlement->id);
        // $data['source_amount'] = $this->salesSettlement->sources()->sum('amount');
        // $data['paid_amount'] = $this->salesSettlement->details()->sum('amount');

        $this->salesSettlement->update($data);

        if ($close) {
            $this->close();
        }

        $this->success('Settlement successfully updated.', redirectTo: route('sales-settlement.index'));
    }

    #[On('detail-updated')]
    public function detailUpdated(array $data = [])
    {
        $this->validity();
    }

    public function updated($property, $value): void
    {
        if ( in_array($property, ['contact_id']))
        {
            $this->dispatch('contact-changed', value: $value);
        }
    }

    public function delete(SalesSettlement $salesSettlement): void
    {
        Gate::authorize('delete sales-settlement');

        if ($salesSettlement->sources()->count() > 0) {
            foreach ($salesSettlement->sources as $source) {
                $source->settleable()->update([
                    'has_settlement' => '0'
                ]);
            }
        }

        $salesSettlement->sources()->delete();
        $salesSettlement->details()->delete();

        $salesSettlement->delete();
        $this->success('Settlement successfully deleted.', redirectTo: route('sales-settlement.index'));
    }

    public function validity(): void
    {
        $this->source_amount = Cast::money($this->salesSettlement->sources()->sum('amount'));
        $this->paid_amount = Cast::money($this->salesSettlement->details()->sum('amount'));
        $this->balance_amount = Cast::money(Cast::number($this->source_amount) - Cast::number($this->paid_amount));

        $this->validityStatus = true;
        $this->validityMessage = '';

        if (Cast::number($this->source_amount) == 0) {
            $this->validityStatus = false;
            $this->validityMessage = 'Source is required.';
        }

        if (Cast::number($this->source_amount) != Cast::number($this->paid_amount)) {
            $this->validityStatus = false;
            $this->validityMessage = 'Source amount and paid amount must be the same.';
        }
    }

    public function close(): void
    {
        $this->salesSettlement->update([
            'status' => 'close'
        ]);

        $this->closeConfirm = false;
        $this->success('Settlement successfully closed.', redirectTo: route('sales-settlement.index'));
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
                    <span>Update Sales Settlement</span>
                    <x-status-badge :status="$salesSettlement->status" class="uppercase !text-sm" />
                </div>
            </x-slot:title>
            <x-slot:actions>
                <x-button label="Back" link="{{ route('sales-settlement.index') }}" icon="o-arrow-uturn-left" />
                @if ($validityStatus AND $salesSettlement->saved == '1' AND $salesSettlement->status == 'open')
                <x-button label="Close" icon="o-check" @click="$wire.closeConfirm=true" class="btn-success" />
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
                        <x-input label="Code" wire:model="code" readonly class="bg-base-200" :disabled="!$open" />
                        <x-datetime label="Date" wire:model="date" :disabled="!$open" />
                        <x-choices
                            label="Contact"
                            wire:model.live="contact_id"
                            :options="$contactChoice"
                            search-function="searchContact"
                            option-label="name"
                            single
                            searchable
                            clearable
                            placeholder="-- Select --"
                            :disabled="!$open"
                        />
                        <x-input label="Source Amount" wire:model="source_amount" readonly class="bg-base-200" x-mask:dynamic="$money($input,'.',',')" />
                        <x-input label="Paid Amount" wire:model="paid_amount" readonly class="bg-base-200" x-mask:dynamic="$money($input,'.',',')" />
                        <x-input label="Balance Amount" wire:model="balance_amount" readonly class="bg-base-200" x-mask:dynamic="$money($input,'.',',')" />
                        <x-input label="Note" wire:model="note" :disabled="!$open" />
                    </div>
                </div>
                {{-- <x-slot:actions>
                    <x-button label="Cancel" link="{{ route('sales-settlement.index') }}" />
                    <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
                </x-slot:actions> --}}
            </x-form>
        </x-card>

        @error('details')
            <div class="flex justify-center">
                <span class="text-red-500 text-sm p-1">{{ $message }}</span>
            </div>
        @enderror

        @unless ($validityStatus)
        <div class="flex justify-center">
            <span class="text-red-500 text-sm p-1">{{ $validityMessage }}</span>
        </div>
        @endif

        <div class="overflow-x-auto">
            <livewire:sales-settlement.source :id="$salesSettlement->id" />
        </div>

        <div class="overflow-x-auto">
            <livewire:sales-settlement.detail :id="$salesSettlement->id" />
        </div>

        @if ($salesSettlement->saved == '1')
        <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
            <x-card>
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold">Histories</h2>
                    <table class="table table-sm">
                    <thead>
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>Time</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($salesSettlement->logs()->with('user')->latest()->limit(10)->get() as $log)
                    <tr>
                        <td>{{ $log->user->name }}</td>
                        <td>{{ $log->action }}</td>
                        <td>{{ $log->created_at->diffForHumans() }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3">No data found.</td></tr>
                    @endforelse
                    </tbody>
                    </table>
                </div>
            </x-card>
            <x-card>
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold">Danger Zone</h2>
                    <div class="text-xs">
                        <p>Once you delete a invoice, there is no going back. Please be certain.</p>
                    </div>
                    <div>
                        <x-button
                            label="Delete Permanently"
                            icon="o-trash"
                            wire:click="delete('{{ $salesSettlement->id }}')"
                            spinner="delete('{{ $salesSettlement->id }}')"
                            wire:confirm="Are you sure you want to delete this invoice?"
                            class="btn-error btn-soft"
                        />
                    </div>
                </div>
            </x-card>
        </div>
        @endif

    </div>

    <x-modal wire:model="closeConfirm" title="Closing Confirmation" persistent>
        <div class="flex pb-2">
            Are you sure you want to close this settlement?
        </div>
        <x-slot:actions>
            <div class="flex items-center gap-4">
                <x-button label="Cancel" icon="o-x-mark" @click="$wire.closeConfirm = false" class="" />
                <x-button label="Yes, I am sure" icon="o-check" wire:click="save(true)" spinner="save(true)" class="" />
            </div>
        </x-slot:actions>
    </x-modal>
</div>
