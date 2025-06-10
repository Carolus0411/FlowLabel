<?php

use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Livewire\Attributes\Reactive;
use Livewire\Attributes\On;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Rules\Number;
use App\Models\Coa;
use App\Models\Journal;
use App\Models\JournalDetail;

new class extends Component {
    use Toast;

    public $modal = false;

    #[Reactive]
    public $ref_id = '';

    #[Reactive]
    public $ref_name = '';

    #[On('show-journal')]
    public function showJournal()
    {
        $this->modal = true;
    }

    #[On('hide-journal')]
    public function hideJournal()
    {
        $this->modal = false;
    }

    public function with(): array
    {
        return [
            'journal' => Journal::with(['details.coa'])
                ->where('ref_name', $this->ref_name)
                ->where('ref_id', $this->ref_id)
                ->first()
        ];
    }
}; ?>

<div>
    <x-modal wire:model="modal" title="Journal" subtitle="{{ $journal->code ?? '' }}" box-class="max-w-11/12 lg:max-w-2/3">
        <div class="overflow-x-auto">
            <table class="table">
            <thead>
            <tr>
                <th class="text-left">Account</th>
                <th class="text-left">Description</th>
                <th class="text-right lg:w-[9rem]">Debit</th>
                <th class="text-right lg:w-[9rem]">Credit</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($journal->details ?? [] as $detail)
            <tr class="divide-x divide-gray-200 dark:divide-gray-900 hover:bg-yellow-50 dark:hover:bg-gray-800">
                <td><b>{{ $detail->coa->code ?? '' }}</b>, {{ $detail->coa->name ?? '' }}</td>
                <td class="">{{ $detail->description }}</td>
                <td class="text-right">{{ \App\Helpers\Cast::money($detail->debit, 2) }}</td>
                <td class="text-right">{{ \App\Helpers\Cast::money($detail->credit, 2) }}</td>
            </tr>
            @empty
            <tr class="divide-x divide-gray-200 dark:divide-gray-900 hover:bg-yellow-50 dark:hover:bg-gray-800">
                <td colspan="4" class="text-center">No record found.</td>
            </tr>
            @endforelse
            </tbody>
            <tfoot>
            <tr>
                <th class="text-left">Account</th>
                <th class="text-left">Description</th>
                <th class="text-right lg:w-[9rem]">Debit</th>
                <th class="text-right lg:w-[9rem]">Credit</th>
            </tr>
            </tfoot>
            </table>
        </div>
        <x-slot:actions>
            <x-button label="Close" icon="o-x-mark" @click="$wire.modal = false" />
            @unless (empty($journal->id))
            <x-button label="View Journal" icon="o-eye" link="{{ route('journal.edit', $journal->id) }}" class="btn-primary" />
            @endunless
        </x-slot:actions>
    </x-modal>
</div>
