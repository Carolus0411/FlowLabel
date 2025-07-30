<?php

use Illuminate\Support\Collection;
use Livewire\Attributes\Reactive;
use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Rules\Number;
use App\Models\CashIn;
use App\Models\SalesInvoice;
use App\Models\SalesSettlement;
use App\Models\SalesSettlementSource;

new class extends Component {
    use Toast;

    public SalesSettlement $salesSettlement;
    public $contact_id;
    public $selected;

    public string $mode = '';
    public bool $sourceDrawer = false;
    public bool $open = true;

    public $payment_method = '';
    public $settleable_type = '';
    public $settleable_id = '';
    public $currency_id = '';
    public $currency_rate = 1;
    public $foreign_amount = 0;
    public $amount = 0;

    public Collection $cashIn;

    #[On('contact-changed')]
    public function contactChanged($value)
    {
        $this->contact_id = $value;
    }

    public function searchCashIn(string $value = ''): void
    {
        $selected = CashIn::where('code', $this->settleable_id)->get();
        $this->cashIn = CashIn::query()
            ->where('contact_id', $this->contact_id)
            ->where('has_settlement', '0')
            ->filterLike('code', $value)
            ->take(20)
            ->get()
            ->merge($selected);
    }

    public function mount( $id = '' ): void
    {
        $this->salesSettlement = SalesSettlement::find($id);
        $this->searchCashIn();
    }

    public function with(): array
    {
        $this->open = $this->salesSettlement->status == 'open';

        return [
            'sources' => $this->salesSettlement->sources()->get()
        ];
    }

    public function clearForm(): void
    {
        $this->selected = null;
        $this->payment_method = '';
        $this->settleable_type = '';
        $this->settleable_id = '';
        $this->currency_id = '';
        $this->currency_rate = 1;
        $this->foreign_amount = 0;
        $this->amount = 0;
        $this->resetValidation();
    }

    public function add(): void
    {
        $this->clearForm();
        $this->searchCashIn();
        $this->mode = 'add';
        $this->sourceDrawer = true;
    }

    public function edit(SalesSettlementSource $source): void
    {
        $this->clearForm();
        $this->fill($source);
        $this->getSource($source->settleable_id);

        $this->selected = $source;
        $this->mode = 'edit';
        $this->sourceDrawer = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'payment_method' => 'required',
            'settleable_id' => 'required',
            'currency_id' => 'required',
            'currency_rate' => ['required', new Number],
            'foreign_amount' => ['required', new Number],
        ]);

        $currency_rate = Cast::number($this->currency_rate);
        $foreign_amount = Cast::number($this->foreign_amount);
        $amount = $foreign_amount * $currency_rate;

        if ($this->mode == 'add')
        {
            $sources = $this->salesSettlement->sources()->create([
                'payment_method' => $this->payment_method,
                'settleable_id' => $this->settleable_id,
                'settleable_type' => $this->settleable_type,
                'currency_id' => $this->currency_id,
                'currency_rate' => $currency_rate,
                'foreign_amount' => $foreign_amount,
                'amount' => $amount,
            ]);

            $sources->settleable()->update([
                'has_settlement' => '1'
            ]);
        }

        // if ($this->mode == 'edit')
        // {
        //     $this->selected->update([
        //         'payment_method' => $this->payment_method,
        //         'settleable_id' => $this->settleable_id,
        //         'settleable_type' => $this->settleable_type,
        //         'currency_id' => $this->currency_id,
        //         'currency_rate' => $currency_rate,
        //         'foreign_amount' => $foreign_amount,
        //         'amount' => $amount,
        //     ]);
        // }

        $data = $this->calculate();

        $this->sourceDrawer = false;
        $this->success('Source has been updated.');
    }

    public function calculate(): void
    {
        $this->dispatch('detail-updated');
    }

    public function delete(string $id): void
    {
        $source = SalesSettlementSource::find($id);

        $source->settleable()->update([
            'has_settlement' => '0'
        ]);

        $source->delete();

        $this->calculate();

        $this->success('Source has been deleted.');
    }

    public function updated($property, $value): void
    {
        if (in_array($property, ['payment_method'])) {
            $this->settleable_id = '';
        }

        if (in_array($property, ['settleable_id'])) {
            $this->getSource($value);
        }
    }

    public function getSource($code): void
    {
        if ($this->payment_method == 'cash')
        {
            $cashIn = CashIn::where('code', $code)->first();
            $this->settleable_type = get_class($cashIn);
            $this->foreign_amount = Cast::money($cashIn->total_amount ?? 0);
        }
    }
}; ?>

<div
    x-data="{ sourceDrawer : $wire.entangle('sourceDrawer') }"
    x-init="$watch('sourceDrawer', value => { mask() })"
>
    <x-card title="Sources" separator progress-indicator>
        <x-slot:menu>
            @if ($open)
            <x-button label="Add Source" icon="o-plus" wire:click="add" spinner="add" class="" />
            @endif
        </x-slot:menu>

        <div class="overflow-x-auto">
            <table class="table">
            <thead>
            <tr>
                <th class="text-left">Payment Method</th>
                <th class="text-left">Payment Code</th>
                <th class="text-right lg:w-[3rem]">Currency</th>
                <th class="text-right lg:w-[6rem]">Rate</th>
                <th class="text-right lg:w-[9rem]">Source Amount</th>
                <th class="text-right lg:w-[9rem]">IDR Amount</th>
                @if ($open)
                <th class="lg:w-[4rem]"></th>
                @endif
            </tr>
            </thead>
            <tbody>

            @forelse ($sources as $key => $source)
            @if ($open)
            <tr wire:key="table-row-{{ $source->id }}" wire:loading.class="cursor-wait" class="divide-x divide-gray-200 dark:divide-gray-900 hover:bg-yellow-50 dark:hover:bg-gray-800">
                <td class="">{{ $source->payment_method }}</td>
                <td class="">{{ $source->settleable_id }}</td>
                <td class="">{{ $source->currency->code ?? '' }}</td>
                <td class="text-right">{{ Cast::money($source->currency_rate, 2) }}</td>
                <td class="text-right">{{ Cast::money($source->foreign_amount, 2) }}</td>
                <td class="text-right">{{ Cast::money($source->amount, 2) }}</td>
                <td>
                <div class="flex items-center">
                    <x-button icon="o-x-mark" wire:click="delete('{{ $source->id }}')" spinner="delete('{{ $source->id }}')" wire:confirm="Are you sure ?" class="btn-xs btn-ghost text-xs -m-1 text-error" />
                </div>
                </td>
            </tr>
            @else
            <tr wire:key="table-row-{{ $source->id }}" class="divide-x divide-gray-200 dark:divide-gray-900 hover:bg-yellow-50 dark:hover:bg-gray-800">
                <td class="">{{ $source->payment_method }}</td>
                <td class="">{{ $source->settlement_id }}</td>
                <td class="">{{ $source->currency->code ?? '' }}</td>
                <td class="text-right">{{ Cast::money($source->currency_rate, 2) }}</td>
                <td class="text-right">{{ Cast::money($source->foreign_amount, 2) }}</td>
                <td class="text-right">{{ Cast::money($source->amount, 2) }}</td>
            </tr>
            @endif
            @empty
            <tr class="divide-x divide-gray-200 dark:divide-gray-900 hover:bg-yellow-50 dark:hover:bg-gray-800">
                <td colspan="10" class="text-center">No record found.</td>
            </tr>
            @endforelse

            </tbody>
            </table>
        </div>
    </x-card>

    {{-- FORM --}}
    {{-- x-mask: dynamic="$money($input,'.','')" --}}
    <x-drawer wire:model="sourceDrawer" title="Create Source" right separator with-close-button class="lg:w-1/3">
        <x-form wire:submit="save">
            <div class="space-y-4">
                <x-select
                    label="Payment Method"
                    wire:model.live="payment_method"
                    :options="\App\Enums\PaymentMethod::toSelect()"
                    placeholder="-- Select --"
                    :disabled="!$open"
                />

                @if ($payment_method == 'cash')
                <x-choices
                    label="Cash In"
                    wire:model.live="settleable_id"
                    :options="$cashIn"
                    search-function="searchCashIn"
                    option-label="code"
                    option-sub-label="total_amount"
                    option-value="code"
                    single
                    searchable
                    clearable
                    placeholder="-- Select --"
                    :disabled="!$open"
                />
                @endif

                <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
                    <x-choices-offline
                        label="Currency"
                        :options="\App\Models\Currency::query()->isActive()->get()"
                        wire:model="currency_id"
                        option-label="code"
                        single
                        searchable
                        placeholder="-- Select --"
                    />
                    <x-input label="Source Amount" wire:model="foreign_amount" class="money" disabled />
                    <x-input label="Rate" wire:model="currency_rate" class="money" />
                </div>
            </div>
            <x-slot:actions>
                <x-button label="Save" icon="o-paper-airplane" type="submit" spinner="save" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-drawer>
</div>
