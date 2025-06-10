<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Helpers\Code;
use App\Models\Contact;
use App\Models\CashAccount;
use App\Models\CashBook;

new class extends Component {
    use Toast;

    public CashBook $cashBook;

    public $type = '';
    public $code = '';
    public $date = '';
    public $note = '';
    public $cash_account_id = '';
    public $contact_id = '';
    public $status = '';
    public $total_amount = 0;

    public $open = true;
    public $closeConfirm = false;
    public $validityStatus = false;
    public $validityMessage = '';

    public $details;
    public Collection $contacts;
    public Collection $cashAccounts;

    public function mount(): void
    {
        Gate::authorize('update cash-book');
        $this->fill($this->cashBook);
        $this->searchCashAccount();
        $this->searchContact();
        $this->validity();
    }

    public function with(): array
    {
        $this->open = $this->cashBook->status == 'open';
        $this->cash_account_id = $this->cash_account_id ?? '';
        $this->contact_id = $this->contact_id ?? '';

        return [];
    }

    public function searchCashAccount(string $value = ''): void
    {
        $selected = CashAccount::where('id', intval($this->cash_account_id))->get();
        $this->cashAccounts = CashAccount::query()
            ->filterLike('name', $value)
            ->isActive()
            ->take(20)
            ->get()
            ->merge($selected);
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

    public function save(): void
    {
        $data = $this->validate([
            'type' => 'required',
            'code' => 'required',
            'date' => 'required',
            'note' => 'nullable',
            'cash_account_id' => 'required',
            'contact_id' => 'required',
            'details' => new \App\Rules\CashBookDetailCheck($this->cashBook),
        ]);

        unset($data['details']);

        if ($this->cashBook->saved == '0') {
            $prefix = $this->type == 'in' ? 'CI' : 'CO';
            $code = Code::auto($prefix);
            $data['code'] = $code;
            $data['saved'] = 1;
        }

        $data['total_amount'] = Cast::number($this->total_amount);

        $this->cashBook->update($data);

        $this->validity();
        $this->success('Cash successfully updated.', redirectTo: route('cash-book.index'));
    }

    #[On('detail-updated')]
    public function detailUpdated(array $data = [])
    {
        $this->total_amount = Cast::money($data['total_amount'] ?? 0);
        $this->validity();
    }

    public function updated($property, $value): void
    {

    }

    public function delete(CashBook $cashBook): void
    {
        Gate::authorize('delete cash-book');
        $cashBook->details()->delete();
        $cashBook->delete();
        $this->success('Cash successfully deleted.', redirectTo: route('cash-book.index'));
    }

    public function validity(): void
    {
        $this->total_amount = Cast::money($this->cashBook->details()->sum('amount'));

        $this->validityStatus = true;
        $this->validityMessage = '';

        // if (empty($this->debit_total) OR empty($this->credit_total)) {
        //     $this->validityStatus = false;
        //     $this->validityMessage = 'Debit or Credit cannot be zero';
        // }

        // if ($this->debit_total != $this->credit_total) {
        //     $this->validityStatus = false;
        //     $this->validityMessage = 'Debit and Credit must be same';
        // }
    }

    public function close(): void
    {
        Gate::authorize('close cash-book');
        $this->cashBook->update([
            'status' => 'close'
        ]);

        \App\Events\CashBookClosed::dispatch($this->cashBook);

        $this->closeConfirm = false;
        $this->success('Cash successfully closed.', redirectTo: route('cash-book.index'));
    }

    public function void(CashBook $cashBook): void
    {
        Gate::authorize('void cash-book');
        $cashBook->update([
            'status' => 'void'
        ]);

        \App\Events\CashBookVoided::dispatch($this->cashBook);

        $this->success('Cash successfully voided.', redirectTo: route('cash-book.index'));
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
                    <span>Update Cash Book</span>
                    @if ($cashBook->status == 'close')
                    <x-badge value="Close" class="badge-success uppercase" />
                    @elseif ($cashBook->status == 'void')
                    <x-badge value="Void" class="badge-error uppercase" />
                    @else
                    <x-badge value="Open" class="badge-primary uppercase" />
                    @endif
                </div>
            </x-slot:title>
            <x-slot:actions>
                <x-button label="Back" link="{{ route('cash-book.index') }}" icon="o-arrow-uturn-left" />
                @if ($validityStatus AND $cashBook->saved == '1' AND $cashBook->status == 'open')
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
                        <x-select label="Type" wire:model="type" :options="\App\Enums\InOut::toSelect()" placeholder="-- Select --" :disabled="!$open" />
                        <x-choices
                            label="Account"
                            wire:model="cash_account_id"
                            :options="$cashAccounts"
                            search-function="searchCashAccount"
                            option-label="name"
                            single
                            searchable
                            placeholder="-- Select --"
                            :disabled="!$open"
                        />
                        <x-choices
                            label="Contact"
                            wire:model="contact_id"
                            :options="$contacts"
                            search-function="searchContact"
                            option-label="name"
                            single
                            searchable
                            placeholder="-- Select --"
                            :disabled="!$open"
                        />
                        <x-input label="Total Amount" wire:model="total_amount" readonly class="bg-base-200" x-mask:dynamic="$money($input,'.',',')" />
                        <x-input label="Note" wire:model="note" :disabled="!$open" />
                    </div>
                </div>
                {{-- <x-slot:actions>
                    <x-button label="Cancel" link="{{ route('cashBook.index') }}" />
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
            <livewire:cash-book.detail :id="$cashBook->id" />
        </div>

        @if ($cashBook->saved == '1')
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
                    @forelse ($cashBook->logs()->with('user')->latest()->limit(8)->get() as $log)
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

                    @can('void cash-book')
                    @if ($cashBook->status != 'void')
                    <div>
                        <x-button
                            label="Void"
                            icon="o-archive-box-x-mark"
                            wire:click="void('{{ $cashBook->id }}')"
                            spinner="void('{{ $cashBook->id }}')"
                            wire:confirm="Are you sure you want to void this?"
                            class="btn-error btn-soft"
                        />
                    </div>
                    @endif
                    @endcan

                    @can('delete cash-book')
                    <div class="text-xs">
                        <p>Once you delete, there is no going back. Please be certain.</p>
                    </div>
                    <div>
                        <x-button
                            label="Delete Permanently"
                            icon="o-trash"
                            wire:click="delete('{{ $cashBook->id }}')"
                            spinner="save"
                            wire:confirm="Are you sure you want to delete this?"
                            class="btn-error btn-soft"
                        />
                    </div>
                    @endcan
                </div>
            </x-card>
        </div>
        @endif

    </div>

    <x-modal wire:model="closeConfirm" title="Closing Confirmation" persistent>
        <div class="flex pb-2">
            Are you sure you want to close this cash book?
        </div>
        <x-slot:actions>
            <div class="flex items-center gap-4">
                <x-button label="Cancel" icon="o-x-mark" @click="$wire.closeConfirm = false" class="" />
                <x-button label="Yes, I am sure" icon="o-check" wire:click="close" spinner="close" class="" />
            </div>
        </x-slot:actions>
    </x-modal>
</div>
