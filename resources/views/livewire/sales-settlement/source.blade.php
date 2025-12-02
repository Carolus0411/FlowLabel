<?php

use Illuminate\Support\Collection;
use Livewire\Attributes\Reactive;
use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Rules\Number;
use App\Models\CashIn;
use App\Models\BankIn;
use App\Models\PrepaidAccount;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
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
    public $debugSelected = null;

    public Collection $cashIn;
    public Collection $bankIn;
    public Collection $prepaidAccounts;
    public Collection $bankInAll;
    public array $bankInAllDebug = [];
    public array $bankInDebug = [];

    #[On('contact-changed')]
    public function contactChanged($value)
    {
        $this->contact_id = $value;
        $this->searchCashIn();
        $this->searchBankIn();
        $this->searchPrepaidAccounts();
    }

    public function searchCashIn(string $value = ''): void
    {
        // Get currently selected item for edit mode (so it shows in dropdown even if used)
        $selected = collect();
        if ($this->mode == 'edit' && $this->settleable_id) {
            $selected = CashIn::where('code', $this->settleable_id)->get();
        }

        $this->cashIn = CashIn::query()
            ->closed()
            ->sales()
            ->where('contact_id', $this->contact_id)
            ->where('has_receivable', '1')
            ->whereDoesntHave('settlements', function ($q) {
                $q->whereHas('salesSettlement', function ($q2) {
                    $q2->where('saved', 1)->where('status', '!=', 'void');
                });
            })
            ->filterLike('code', $value)
            ->take(20)
            ->get()
            ->merge($selected)
            ->unique('id');
    }

    public function searchBankIn(string $value = ''): void
    {
        $accountReceivable = settings('account_receivable_code');
        // Get currently selected item for edit mode (so it shows in dropdown even if used)
        $selected = collect();
        if ($this->mode == 'edit' && $this->settleable_id) {
            $selected = BankIn::where('code', $this->settleable_id)->with('details')->get();
        }
        // all bankIns for contact (no filter) for debug
        $this->bankInAll = BankIn::query()
            ->where('contact_id', $this->contact_id)
            ->with('details')
            ->take(50)
            ->get();

        // build debug arrays
        $this->bankInAllDebug = $this->bankInAll->map(function($b) use ($accountReceivable) {
            $ar = strtolower(trim($accountReceivable));
            $hasAr = $b->details->pluck('coa_code')->map(fn($c) => strtolower(trim($c)))->contains($ar);
            $passClosed = ($b->status == 'close');
            $hasSettlement = $b->settlements()->whereHas('salesSettlement', function($q){ $q->where('saved',1)->where('status','!=','void'); })->exists();
            return [
                'id' => $b->id,
                'code' => $b->code,
                'status' => $b->status,
                'has_receivable' => $b->getAttributes()['has_receivable'] ?? null,
                'has_settlement' => $hasSettlement,
                'has_ar' => $hasAr,
                'pass_closed' => $passClosed,
                'eligible' => ($passClosed && !$hasSettlement && $hasAr),
            ];
        })->toArray();

        $accountReceivableNormalized = strtolower(trim($accountReceivable));
        $bankQuery = BankIn::query()
            ->closed()
            ->where('contact_id', $this->contact_id);

        // Exclude bankIn that are already used in saved (non-void) settlement sources
        $bankQuery->whereDoesntHave('settlements', function ($q) {
            $q->whereHas('salesSettlement', function ($q2) {
                $q2->where('saved', 1)->where('status', '!=', 'void');
            });
        });

        $bankQuery->whereHas('details', function ($q) use ($accountReceivableNormalized) {
            $q->whereRaw("lower(trim(coa_code)) = ?", [$accountReceivableNormalized]);
        });

        $this->bankIn = $bankQuery
            ->with('details')
            ->filterLike('code', $value)
            ->take(20)
            ->get()
            ->merge($selected)
            ->unique('id');

        $this->bankInDebug = $this->bankIn->map(function($b) use ($accountReceivable) {
            $hasAr = $b->details->pluck('coa_code')->map(fn($c) => strtolower(trim($c)))->contains(strtolower(trim($accountReceivable)));
            $hasSettlement = $b->settlements()->whereHas('salesSettlement', function($q){ $q->where('saved',1)->where('status','!=','void'); })->exists();
            return [
                'id' => $b->id,
                'code' => $b->code,
                'has_receivable' => $b->getAttributes()['has_receivable'] ?? null,
                'has_settlement' => $hasSettlement,
                'has_ar' => $hasAr,
                'eligible' => ($b->status == 'close' && $hasAr && !$hasSettlement),
            ];
        })->toArray();
    }

    public function searchPrepaidAccounts(string $value = ''): void
    {
        // Get prepaid accounts for this contact that have available balance (credit > debit used)
        $prepaidCoaCodes = PrepaidAccount::getPrepaidCoaCodes();

        // Get currently selected item for edit mode
        $selected = collect();
        if ($this->mode == 'edit' && $this->settleable_id) {
            $selected = PrepaidAccount::where('code', $this->settleable_id)
                ->with('coa')
                ->get()
                ->map(function ($item) {
                    $totalCredit = PrepaidAccount::where('contact_id', $item->contact_id)
                        ->where('coa_code', $item->coa_code)
                        ->sum('credit');
                    $totalDebit = PrepaidAccount::where('contact_id', $item->contact_id)
                        ->where('coa_code', $item->coa_code)
                        ->sum('debit');
                    $item->available_balance = $totalCredit - $totalDebit;
                    $item->display_label = $item->code . ' - ' . ($item->coa->name ?? $item->coa_code);
                    return $item;
                });
        }

        // Get prepaid accounts with available balance for this contact
        // Balance = sum of credits - sum of debits for same coa_code and contact
        // Exclude prepaid accounts already used in other saved settlements
        $usedPrepaidCodes = SalesSettlementSource::query()
            ->where('payment_method', 'prepaid')
            ->whereHas('salesSettlement', function ($q) {
                $q->where('saved', 1)->where('status', '!=', 'void');
            })
            ->pluck('settleable_id')
            ->toArray();

        $this->prepaidAccounts = PrepaidAccount::query()
            ->where('contact_id', $this->contact_id)
            ->whereIn('coa_code', $prepaidCoaCodes)
            ->where('credit', '>', 0)
            ->whereNotIn('code', $usedPrepaidCodes)
            ->filterLike('code', $value)
            ->with('coa')
            ->take(20)
            ->get()
            ->map(function ($item) {
                // Calculate available balance for this prepaid
                $totalCredit = PrepaidAccount::where('contact_id', $item->contact_id)
                    ->where('coa_code', $item->coa_code)
                    ->sum('credit');
                $totalDebit = PrepaidAccount::where('contact_id', $item->contact_id)
                    ->where('coa_code', $item->coa_code)
                    ->sum('debit');
                $item->available_balance = $totalCredit - $totalDebit;
                $item->display_label = $item->code . ' - ' . ($item->coa->name ?? $item->coa_code);
                return $item;
            })
            ->filter(fn($item) => $item->available_balance > 0)
            ->merge($selected)
            ->unique('id');
    }

    public function mount( $id = '', $contact_id = '' ): void
    {
        $this->salesSettlement = SalesSettlement::find($id);
        $this->contact_id = $contact_id ?: $this->salesSettlement->contact_id;
        $this->searchCashIn();
        $this->searchBankIn();
        $this->searchPrepaidAccounts();
        $this->bankIn = $this->bankIn ?? collect();
        $this->prepaidAccounts = $this->prepaidAccounts ?? collect();
    }

    public function with(): array
    {
        $this->open = $this->salesSettlement->status == 'open';

        return [
            'sources' => $this->salesSettlement
                ->sources()
                ->with(['currency'])
                ->get()
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
        $this->bankIn = collect();
        $this->prepaidAccounts = collect();
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
        // Validate with unique check to prevent duplicate usage
        $data = $this->validate([
            'payment_method' => 'required',
            'settleable_id' => [
                'required',
                new \App\Rules\SettlementSourceUniqueCheck(
                    $this->settleable_type,
                    'sales_settlement_source',
                    'sales_settlement_code',
                    $this->salesSettlement->code
                ),
            ],
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

        // Dispatch event to notify parent that source was added (for unsaved settlement warning)
        if ($this->mode == 'add' && $this->salesSettlement->saved == '0') {
            $this->dispatch('source-added');
        }

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
        $source->delete();

        $this->calculate();

        $this->success('Source has been deleted.');
    }

    public function updated($property, $value): void
    {
        if (in_array($property, ['payment_method'])) {
            $this->settleable_id = '';
            if ($this->payment_method == 'transfer') {
                $this->searchBankIn();
            } elseif ($this->payment_method == 'prepaid') {
                $this->searchPrepaidAccounts();
            } else {
                $this->bankIn = collect();
                $this->prepaidAccounts = collect();
            }
        }

        if (in_array($property, ['settleable_id'])) {
            $this->getSource($value);
        }
    }

    public function getSource($code): void
    {
        if ($this->payment_method == 'cash')
        {
            $accountReceivable = settings('account_receivable_code');
            $cashIn = CashIn::where('code', $code)->first();
            if ($cashIn) {
                $detail = $cashIn->details()->where('coa_code', $accountReceivable)->first();
                $amount = $cashIn->details()->where('coa_code', $accountReceivable)->sum('foreign_amount');
                // if there's no foreign amount stored, try to get amount and convert using currency_rate
                if (Cast::number($amount) <= 0) {
                    $amountIdr = $cashIn->details()->where('coa_code', $accountReceivable)->sum('amount');
                    if ($detail && Cast::number($detail->currency_rate) > 0) {
                        $amount = Cast::number($amountIdr) / Cast::number($detail->currency_rate);
                    } else {
                        $amount = $amountIdr;
                    }
                }
                // fallback to first detail if AR detail not found
                if (!$detail) {
                    $detail = $cashIn->details()->first();
                    $amount = $cashIn->details()->sum('foreign_amount');
                }
                $this->settleable_type = get_class($cashIn);
                $this->foreign_amount = Cast::money($amount);
                if ($detail) {
                    $this->currency_id = $detail->currency_id;
                    $this->currency_rate = $detail->currency_rate;
                } else {
                    // fallback: set default currency (IDR) if not found
                    $this->currency_id = settings('currency_id') ?? $this->currency_id;
                    $this->currency_rate = $this->currency_rate ?: 1;
                }
            }
        }

                if ($this->payment_method == 'transfer')
        {
            $accountReceivable = settings('account_receivable_code');
            $bankIn = BankIn::where('code', $code)->first();
            if ($bankIn) {
                // mirror CashIn getSource behavior: populate from AR detail/native detail fallback
                $detail = $bankIn->details()->where('coa_code', $accountReceivable)->first();
                $amount = $bankIn->details()->where('coa_code', $accountReceivable)->sum('foreign_amount');
                if (Cast::number($amount) <= 0) {
                    $amountIdr = $bankIn->details()->where('coa_code', $accountReceivable)->sum('amount');
                    if ($detail && Cast::number($detail->currency_rate) > 0) {
                        $amount = Cast::number($amountIdr) / Cast::number($detail->currency_rate);
                    } else {
                        $amount = $amountIdr;
                    }
                }
                // fallback to first detail if AR detail not found
                if (!$detail) {
                    $detail = $bankIn->details()->first();
                    $amount = $bankIn->details()->sum('foreign_amount');
                }
                $this->settleable_type = get_class($bankIn);
                $this->foreign_amount = Cast::money($amount);
                if ($detail) {
                    $this->currency_id = $detail->currency_id;
                    $this->currency_rate = $detail->currency_rate;
                }
                // Debug: save selected info for troubleshooting
                $this->debugSelected = ['code' => $code, 'amount' => $amount, 'currency_id' => $this->currency_id, 'currency_rate' => $this->currency_rate];
            }
        }

        if ($this->payment_method == 'prepaid')
        {
            $prepaid = PrepaidAccount::where('code', $code)->with('coa')->first();
            if ($prepaid) {
                // Calculate available balance for this prepaid COA and contact
                $totalCredit = PrepaidAccount::where('contact_id', $prepaid->contact_id)
                    ->where('coa_code', $prepaid->coa_code)
                    ->sum('credit');
                $totalDebit = PrepaidAccount::where('contact_id', $prepaid->contact_id)
                    ->where('coa_code', $prepaid->coa_code)
                    ->sum('debit');
                $availableBalance = $totalCredit - $totalDebit;

                $this->settleable_type = get_class($prepaid);
                $this->foreign_amount = Cast::money($availableBalance);
                // Prepaid is always in IDR
                $this->currency_id = settings('currency_id') ?? 1;
                $this->currency_rate = 1;
            }
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
                <td class="">{{ $source->settleable_id }}</td>
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
                    label="Source : cash / bank / prepaid"
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
                @elseif ($payment_method == 'transfer')
                <div class="text-xs text-gray-500 mb-2">Only payment numbers with account <strong>{{ settings('account_receivable_code') }}</strong> (Trade Receivable) are allowed.</div>
                <x-choices
                    label="Source : cash / bank / prepaid"
                    wire:model.live="settleable_id"
                    :options="$bankIn"
                    search-function="searchBankIn"
                    option-label="code"
                    option-sub-label="total_amount"
                    option-value="code"
                    single
                    searchable
                    clearable
                    placeholder="-- Select --"
                    :disabled="!$open"
                />
                @if($bankIn->isEmpty())
                <div class="text-sm text-error mt-2">No eligible Bank In found for this contact with account {{ settings('account_receivable_code') }} (Trade Receivable).</div>
                @endif
                @elseif ($payment_method == 'prepaid')
                <div class="text-xs text-gray-500 mb-2">Select a prepaid account (Customer Down Payment or Refundable Cust.Deposit) with available balance.</div>
                <x-choices
                    label="Source : cash / bank / prepaid"
                    wire:model.live="settleable_id"
                    :options="$prepaidAccounts"
                    search-function="searchPrepaidAccounts"
                    option-label="display_label"
                    option-sub-label="available_balance"
                    option-value="code"
                    single
                    searchable
                    clearable
                    placeholder="-- Select --"
                    :disabled="!$open"
                />
                @if($prepaidAccounts->isEmpty())
                <div class="text-sm text-error mt-2">No prepaid account with available balance found for this contact.</div>
                @endif
                @else
                <x-input label="Source : cash / bank / prepaid" wire:model="settleable_id" disabled />
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
