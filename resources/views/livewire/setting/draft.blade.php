<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Models\CashIn;
use App\Models\CashOut;
use App\Models\Journal;

new class extends Component {
    use Toast;

    public function mount(): void
    {
        Gate::authorize('view draft');
    }

    public function with(): array
    {
        return [
            'cashInDraft' => CashIn::draft()->with('contact')->get(),
            'cashOutDraft' => CashOut::draft()->with('contact')->get(),
            'journalDraft' => Journal::draft()->with('contact')->get(),
        ];
    }

    public function deleteCashIn(CashIn $cashIn): void
    {
        $cashIn->details()->delete();
        $cashIn->delete();
        $this->success('Deleted','Cash-in successfully deleted.');
    }

    public function deleteCashOut(CashOut $cashOut): void
    {
        $cashOut->details()->delete();
        $cashOut->delete();
        $this->success('Deleted','Cash-out successfully deleted.');
    }

    public function deleteJournal(Journal $journal): void
    {
        $journal->details()->delete();
        $journal->delete();
        $this->success('Deleted','Journal successfully deleted.');
    }
}; ?>

<div>
    <x-header title="Draft" separator />

    <x-card>
        <div class="text-sm space-y-4">

            {{-- CASH IN --}}
            @if ($cashInDraft->count() > 0)
            <div>
            <h3 class="font-semibold text-lg mb-4">Cash In</h3>
            <table class="table table-sm">
            <tbody>
            @foreach($cashInDraft as $draft)
            <tr class="hover:bg-amber-50 dark:hover:bg-gray-800 cursor-pointer">
                <td><a href="{{ route('cash-in.edit', $draft->id) }}" class="text-primary hover:underline" target="_blank">{{ $draft->code }}</a></td>
                <td>{{ $draft->created_at->diffForHumans() }}</td>
                <td>{{ $draft->contact->name }}</td>
                <td class="p-0.5">
                    <x-button
                        label="delete"
                        wire:click="deleteCashIn({{ $draft->id }})"
                        spinner="deleteCashIn({{ $draft->id }})"
                        wire:confirm="Are you sure to delete this row?"
                        class="btn-xs btn-error btn-soft"
                    />
                </td>
            </tr>
            @endforeach
            </tbody>
            </table>
            </div>
            @endif

            {{-- CASH OUT --}}
            @if ($cashOutDraft->count() > 0)
            <div>
            <h3 class="font-semibold text-lg mb-4">Cash Out</h3>
            <table class="table table-sm">
            <tbody>
            @foreach($cashOutDraft as $draft)
            <tr class="hover:bg-amber-50 dark:hover:bg-gray-800 cursor-pointer">
                <td><a href="{{ route('cash-out.edit', $draft->id) }}" class="text-primary hover:underline" target="_blank">{{ $draft->code }}</a></td>
                <td>{{ $draft->created_at->diffForHumans() }}</td>
                <td>{{ $draft->contact->name }}</td>
                <td class="p-0.5">
                    <x-button
                        label="delete"
                        wire:click="deleteCashOut({{ $draft->id }})"
                        spinner="deleteCashOut({{ $draft->id }})"
                        wire:confirm="Are you sure to delete this row?"
                        class="btn-xs btn-error btn-soft"
                    />
                </td>
            </tr>
            @endforeach
            </tbody>
            </table>
            </div>
            @endif

            {{-- JOURNAL --}}
            @if ($journalDraft->count() > 0)
            <div>
            <h3 class="font-semibold text-lg mb-4">Journal</h3>
            <table class="table table-sm">
            <tbody>
            @foreach($journalDraft as $draft)
            <tr class="hover:bg-amber-50 dark:hover:bg-gray-800 cursor-pointer">
                <td><a href="{{ route('journal.edit', $draft->id) }}" class="text-primary hover:underline" target="_blank">{{ $draft->code }}</a></td>
                <td>{{ $draft->created_at->diffForHumans() }}</td>
                <td>{{ $draft->contact->name }}</td>
                <td class="p-0.5">
                    <x-button
                        label="delete"
                        wire:click="deleteJournal({{ $draft->id }})"
                        spinner="deleteJournal({{ $draft->id }})"
                        wire:confirm="Are you sure to delete this row?"
                        class="btn-xs btn-error btn-soft"
                    />
                </td>
            </tr>
            @endforeach
            </tbody>
            </table>
            </div>
            @endif

        </div>
    </x-card>
</div>
