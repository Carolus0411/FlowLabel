<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Helpers\Code;
use App\Models\StockAdjustmentIn;

new class extends Component {
    use Toast;

    public StockAdjustmentIn $stockAdjustmentIn;

    public $code = '';
    public $date = '';
    public $note = '';
    public $status = '';

    public $closeConfirm = false;

    public function mount(): void
    {
        Gate::authorize('update stock adjustment in');
        $this->fill($this->stockAdjustmentIn);
    }

    public function save($close = false): void
    {
        $this->closeConfirm = false;

        $this->validate([
            'code' => 'required',
            'date' => 'required',
        ]);

        // Validate that there are details
        if ($this->stockAdjustmentIn->details()->count() === 0) {
            $this->error('Please add at least one product.');
            return;
        }

        $updateData = [
            'code' => $this->code,
            'date' => $this->date,
            'note' => $this->note,
        ];

        if ($this->stockAdjustmentIn->saved == '0') {
            $code = Code::auto('SAI');
            $updateData['code'] = $code;
            $updateData['saved'] = 1;
        }

        $this->stockAdjustmentIn->update($updateData);

        if ($close) {
            $this->close();
        }

        $this->success('Stock Adjustment In saved successfully.', redirectTo: route('stock-adjustment-in.index'));
    }

    public function close(): void
    {
        Gate::authorize('approve stock adjustment in');

        if ($this->stockAdjustmentIn->status != 'open') {
            $this->error('Only open stock adjustment can be approved.');
            return;
        }

        if (!$this->stockAdjustmentIn->saved) {
            $this->error('Please save the stock adjustment first before approving.');
            return;
        }

        DB::transaction(function () {
            $this->stockAdjustmentIn->update([
                'status' => 'close',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // Dispatch job to update inventory ledger
            \App\Jobs\StockAdjustmentInApprove::dispatchSync($this->stockAdjustmentIn);
        });

        $this->success('Stock Adjustment In approved and inventory updated.', redirectTo: route('stock-adjustment-in.index'));
    }

    public function void(): void
    {
        Gate::authorize('void stock adjustment in');

        if ($this->stockAdjustmentIn->status != 'close') {
            $this->error('Only closed stock adjustment can be voided.');
            return;
        }

        DB::transaction(function () {
            $this->stockAdjustmentIn->update([
                'status' => 'void',
            ]);

            // Dispatch job to reverse inventory ledger
            \App\Jobs\StockAdjustmentInVoid::dispatchSync($this->stockAdjustmentIn);
        });

        $this->success('Stock Adjustment In voided.', redirectTo: route('stock-adjustment-in.index'));
    }

    public function delete(): void
    {
        Gate::authorize('delete stock adjustment in');

        if ($this->stockAdjustmentIn->status != 'open') {
            $this->error('Only open stock adjustment can be deleted.');
            return;
        }

        $this->stockAdjustmentIn->delete();
        $this->success('Stock Adjustment In deleted.', redirectTo: route('stock-adjustment-in.index'));
    }

    public function with(): array
    {
        $isOpen = $this->stockAdjustmentIn->status == 'open';
        $approvedBy = $this->stockAdjustmentIn->approvedBy?->name;

        return compact('isOpen', 'approvedBy');
    }
}; ?>

<div>
    <div class="lg:top-[65px] lg:sticky z-10 bg-base-200 pb-0 pt-3">
        <x-header separator>
            <x-slot:title>
                <div class="flex items-center gap-4">
                    <span>{{ $stockAdjustmentIn->id ? 'Stock Adjustment In' : 'Create Stock Adjustment In' }}</span>
                    <x-status-badge :status="$stockAdjustmentIn->status" class="uppercase text-sm" />
                </div>
            </x-slot:title>
            <x-slot:actions>
                <x-button label="Back" link="{{ route('stock-adjustment-in.index') }}" icon="o-arrow-uturn-left" class="btn-soft" responsive />
                @if ($stockAdjustmentIn->status == 'close' && Gate::allows('void stock adjustment in'))
                <x-button label="Void" icon="o-x-mark" wire:click="void" wire:confirm="Are you sure you want to void this stock adjustment?" spinner="void" class="btn-error" responsive />
                @endif
                @if ($stockAdjustmentIn->status == 'open' && Gate::allows('approve stock adjustment in'))
                <x-button label="Approve" icon="o-check" @click="$wire.closeConfirm = true" class="btn-success" responsive />
                @endif
                @if ($stockAdjustmentIn->status == 'open')
                <x-button label="Save" icon="o-check" wire:click="save" spinner="save" class="btn-primary" responsive />
                @endif
                @if ($stockAdjustmentIn->status == 'open' && Gate::allows('delete stock adjustment in'))
                <x-button label="Delete" icon="o-trash" wire:click="delete" wire:confirm="Are you sure you want to delete this stock adjustment?" spinner="delete" class="btn-ghost" responsive />
                @endif
            </x-slot:actions>
        </x-header>
    </div>

    <x-card class="p-6 mt-4">
        <x-form wire:submit="save">
            <div class="space-y-4">
                <div class="divider">General Information</div>

                <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-3 gap-4">
                    <x-input label="Code" wire:model="code" readonly class="bg-base-200" />
                    <x-input label="Date" wire:model="date" type="date" :disabled="!$isOpen" />
                    <div>
                        <label class="label">
                            <span class="label-text">Status</span>
                        </label>
                        <x-status-badge :status="$stockAdjustmentIn->status" class="uppercase" />
                    </div>
                </div>

                <x-textarea label="Note" wire:model="note" :disabled="!$isOpen" rows="2" />

                @if ($stockAdjustmentIn->status != 'open')
                <div class="divider">Approval Information</div>
                <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
                    <x-input label="Approved By" value="{{ $approvedBy }}" readonly class="bg-base-200" />
                    <x-input label="Approved At" value="{{ $stockAdjustmentIn->approved_at ? date('d/m/Y H:i', strtotime($stockAdjustmentIn->approved_at)) : '' }}" readonly class="bg-base-200" />
                </div>
                @endif
            </div>
        </x-form>
    </x-card>

    <div class="mt-4">
        <livewire:stock-adjustment-in.detail :id="$stockAdjustmentIn->id" />
    </div>

    <x-modal wire:model="closeConfirm" title="Confirm Approval" box-class="border-2 border-warning">
        <div class="text-center p-6">
            <p class="text-lg mb-4">Are you sure you want to approve this stock adjustment?</p>
            <p class="text-sm text-warning">This will update the inventory ledger and cannot be undone without voiding.</p>
        </div>
        <x-slot:actions>
            <x-button label="Cancel" @click="$wire.closeConfirm = false" />
            <x-button label="Yes, Approve" wire:click="close" spinner="close" class="btn-success" />
        </x-slot:actions>
    </x-modal>
</div>
