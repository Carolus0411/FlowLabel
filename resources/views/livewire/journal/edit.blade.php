<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Helpers\Code;
use App\Models\Contact;
use App\Models\Journal;

new class extends Component {
    use Toast;

    public Journal $journal;

    public $code = '';
    public $date = '';
    public $note = '';
    public $contact_id = '';
    public $type = '';
    public $status = '';
    public $ref_name = '';
    public $ref_id = '';
    public $debit_total = 0;
    public $credit_total = 0;

    public $open = true;
    public $closeConfirm = false;
    public $validityStatus = false;
    public $validityMessage = '';

    public $details;
    public Collection $contacts;

    public function mount(): void
    {
        Gate::authorize('update journal');
        $this->fill($this->journal);
        $this->searchContact();
        $this->validity();
    }

    public function with(): array
    {
        $this->open = $this->journal->status == 'open';
        $this->contact_id = $this->contact_id ?? '';

        return [];
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
            'code' => 'required',
            'date' => 'required',
            'note' => 'nullable',
            'contact_id' => 'required',
            'type' => 'required',
            'details' => new \App\Rules\JournalDetailCheck($this->journal),
        ]);

        unset($data['details']);

        if ($this->journal->saved == '0') {
            $code = Code::auto('JV');
            $data['code'] = $code;
            $data['saved'] = 1;
            $this->journal->details()->update(['code' => $code]);
        }

        $data['debit_total'] = Cast::number($this->debit_total);
        $data['credit_total'] = Cast::number($this->credit_total);

        $this->journal->update($data);

        $this->validity();
        $this->success('Journal successfully updated.', redirectTo: route('journal.index'));
    }

    #[On('detail-updated')]
    public function detailUpdated(array $data = [])
    {
        $this->debit_total = Cast::money($data['debit_total'] ?? 0);
        $this->credit_total = Cast::money($data['credit_total'] ?? 0);
        $this->validity();
    }

    public function updated($property, $value): void
    {

    }

    public function delete(Journal $journal): void
    {
        Gate::authorize('delete journal');
        $journal->details()->delete();
        $journal->delete();
        $this->success('Journal successfully deleted.', redirectTo: route('journal.index'));
    }

    public function validity(): void
    {
        $this->debit_total = $this->journal->details()->sum('debit');
        $this->credit_total = $this->journal->details()->sum('credit');

        $this->validityStatus = true;
        $this->validityMessage = '';

        if (empty($this->debit_total) OR empty($this->credit_total)) {
            $this->validityStatus = false;
            $this->validityMessage = 'Debit or Credit cannot be zero';
        }

        if ($this->debit_total != $this->credit_total) {
            $this->validityStatus = false;
            $this->validityMessage = 'Debit and Credit must be same';
        }
    }

    public function close(): void
    {
        $this->journal->update([
            'status' => 'close'
        ]);

        $this->closeConfirm = false;
        $this->success('Journal successfully closed.', redirectTo: route('journal.index'));
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
                    <span>Update Journal</span>
                    @if ($journal->status == 'close')
                    <x-badge value="Close" class="badge-success uppercase" />
                    @elseif ($journal->status == 'void')
                    <x-badge value="Void" class="badge-error uppercase" />
                    @else
                    <x-badge value="Open" class="badge-primary uppercase" />
                    @endif
                </div>
            </x-slot:title>
            <x-slot:actions>
                <x-button label="Back" link="{{ route('journal.index') }}" icon="o-arrow-uturn-left" />
                @if ($validityStatus AND $journal->saved == '1' AND $journal->status == 'open')
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
                        <x-select label="Type" wire:model="type" :options="\App\Enums\JournalType::toSelect()" placeholder="-- Select --" :disabled="!$open" />
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
                        <x-input label="Ref Name" wire:model="ref_name" readonly class="bg-base-200" />
                        <x-input label="Ref ID" wire:model="ref_id" readonly class="bg-base-200" />
                        <x-input label="Note" wire:model="note" :disabled="!$open" />
                        <x-input label="Debit Total" wire:model="debit_total" readonly class="bg-base-200" x-mask:dynamic="$money($input,'.',',')" />
                        <x-input label="Credit Total" wire:model="credit_total" readonly class="bg-base-200" x-mask:dynamic="$money($input,'.',',')" />
                    </div>
                </div>
                {{-- <x-slot:actions>
                    <x-button label="Cancel" link="{{ route('journal.index') }}" />
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
            <livewire:journal.detail :id="$journal->id" />
        </div>

        @if ($journal->saved == '1')
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
                    @forelse ($journal->logs()->with('user')->latest()->limit(10)->get() as $log)
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
                            wire:click="delete('{{ $journal->id }}')"
                            spinner="save"
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
            Are you sure you want to close this journal?
        </div>
        <x-slot:actions>
            <div class="flex items-center gap-4">
                <x-button label="Cancel" icon="o-x-mark" @click="$wire.closeConfirm = false" class="" />
                <x-button label="Yes, I am sure" icon="o-check" wire:click="close" spinner="close" class="" />
            </div>
        </x-slot:actions>
    </x-modal>
</div>
