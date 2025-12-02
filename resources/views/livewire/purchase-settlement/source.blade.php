<?php

use Illuminate\Support\Collection;
use Livewire\Attributes\Reactive;
use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Rules\Number;
use App\Models\CashOut;
use App\Models\BankOut;
use Illuminate\Support\Facades\Schema;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseSettlement;
use App\Models\PurchaseSettlementSource;
use App\Models\PrepaidAccount;

new class extends Component {
    use Toast;

    public PurchaseSettlement $purchaseSettlement;
    public $supplier_id;
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

    public Collection $cashOut;
    public Collection $bankOut;
    public Collection $prepaidAccounts;
    public Collection $bankOutAll;
    public array $bankOutAllDebug = [];
    public array $bankOutDebug = [];

    #[On('supplier-changed')]
    public function supplierChanged($value)
    {
        $this->supplier_id = $value;
        $this->searchCashOut();
        $this->searchBankOut();
        $this->searchPrepaidAccounts();
    }

    public function searchCashOut(string $value = ''): void
    {
        // Get currently selected item for edit mode (so it shows in dropdown even if used)
        $selected = collect();
        if ($this->mode == 'edit' && $this->settleable_id) {
            $selected = CashOut::where('code', $this->settleable_id)->get();
        }

        $this->cashOut = CashOut::query()
            ->closed()
            ->where('supplier_id', $this->supplier_id)
            ->where('has_payable', '1')
            ->whereDoesntHave('purchaseSettlements', function ($q) {
                $q->whereHas('purchaseSettlement', function ($q2) {
                    $q2->where('saved', 1)->where('status', '!=', 'void');
                });
            })
            ->filterLike('code', $value)
            ->take(20)
            ->get()
            ->merge($selected)
            ->unique('id');
    }

    public function searchBankOut(string $value = ''): void
    {
        $accountPayable = settings('account_payable_code');
        // Get currently selected item for edit mode (so it shows in dropdown even if used)
        $selected = collect();
        if ($this->mode == 'edit' && $this->settleable_id) {
            $selected = BankOut::where('code', $this->settleable_id)->with('details')->get();
        }
        // all bankOuts for supplier (no filter) for debug
        $this->bankOutAll = BankOut::query()
            ->where('supplier_id', $this->supplier_id)
            ->with('details')
            ->take(50)
            ->get();

        // build debug arrays
        $this->bankOutAllDebug = $this->bankOutAll->map(function($b) use ($accountPayable) {
            $ar = strtolower(trim($accountPayable));
            $hasAp = $b->details->pluck('coa_code')->map(fn($c) => strtolower(trim($c)))->contains($ar);
            $passClosed = ($b->status == 'close');
            $hasSettlement = $b->purchaseSettlements()->whereHas('purchaseSettlement', function($q){ $q->where('saved',1)->where('status','!=','void'); })->exists();
            return [
                'id' => $b->id,
                'code' => $b->code,
                'status' => $b->status,
                'has_payable' => $b->getAttributes()['has_payable'] ?? null,
                'has_settlement' => $hasSettlement,
                'has_ap' => $hasAp,
                'pass_closed' => $passClosed,
                'eligible' => ($passClosed && !$hasSettlement && $hasAp),
            ];
        })->toArray();

        $accountPayableNormalized = strtolower(trim($accountPayable));
        $bankQuery = BankOut::query()
            ->closed()
            ->where('supplier_id', $this->supplier_id);

        // Exclude bankOut that are already used in saved (non-void) settlement sources
        $bankQuery->whereDoesntHave('purchaseSettlements', function ($q) {
            $q->whereHas('purchaseSettlement', function ($q2) {
                $q2->where('saved', 1)->where('status', '!=', 'void');
            });
        });

        $bankQuery->whereHas('details', function ($q) use ($accountPayableNormalized) {
            $q->whereRaw("lower(trim(coa_code)) = ?", [$accountPayableNormalized]);
        });

        $this->bankOut = $bankQuery
            ->with('details')
            ->filterLike('code', $value)
            ->take(20)
            ->get()
            ->merge($selected)
            ->unique('id');

        $this->bankOutDebug = $this->bankOut->map(function($b) use ($accountPayable) {
            $hasAp = $b->details->pluck('coa_code')->map(fn($c) => strtolower(trim($c)))->contains(strtolower(trim($accountPayable)));
            $hasSettlement = $b->purchaseSettlements()->whereHas('purchaseSettlement', function($q){ $q->where('saved',1)->where('status','!=','void'); })->exists();
            return [
                'id' => $b->id,
                'code' => $b->code,
                'has_payable' => $b->getAttributes()['has_payable'] ?? null,
                'has_settlement' => $hasSettlement,
                'has_ap' => $hasAp,
                'eligible' => ($b->status == 'close' && $hasAp && !$hasSettlement),
            ];
        })->toArray();
    }

    public function searchPrepaidAccounts(string $value = ''): void
    {
        $prepaidCoaCodes = PrepaidAccount::getPrepaidCoaCodes();

        // Get currently selected item for edit mode (so it shows in dropdown even if used)
        $selected = collect();
        if ($this->mode == 'edit' && $this->settleable_id) {
            $selected = PrepaidAccount::where('code', $this->settleable_id)
                ->with('coa')
                ->get()
                ->map(function ($item) {
                    // For suppliers: available balance = total debit - total credit (opposite of contacts)
                    $totalDebit = PrepaidAccount::where('supplier_id', $item->supplier_id)
                        ->where('coa_code', $item->coa_code)
                        ->sum('debit');
                    $totalCredit = PrepaidAccount::where('supplier_id', $item->supplier_id)
                        ->where('coa_code', $item->coa_code)
                        ->sum('credit');
                    $item->available_balance = $totalDebit - $totalCredit;
                    $item->display_label = $item->code . ' - ' . ($item->coa->name ?? $item->coa_code);
                    return $item;
                });
        }

        // Get prepaid accounts with available balance for this supplier
        // For suppliers: prepaid is recorded as DEBIT (asset) in BankOut/CashOut
        // Available balance = total debit - total credit
        // Exclude prepaid accounts already used in other saved settlements
        $usedPrepaidCodes = PurchaseSettlementSource::query()
            ->where('payment_method', 'prepaid')
            ->whereHas('purchaseSettlement', function ($q) {
                $q->where('saved', 1)->where('status', '!=', 'void');
            })
            ->pluck('settleable_id')
            ->toArray();

        $this->prepaidAccounts = PrepaidAccount::query()
            ->where('supplier_id', $this->supplier_id)
            ->whereIn('coa_code', $prepaidCoaCodes)
            ->where('debit', '>', 0)
            ->whereNotIn('code', $usedPrepaidCodes)
            ->filterLike('code', $value)
            ->with('coa')
            ->take(20)
            ->get()
            ->map(function ($item) {
                // Calculate available balance for this prepaid
                // For suppliers: available balance = total debit - total credit
                $totalDebit = PrepaidAccount::where('supplier_id', $item->supplier_id)
                    ->where('coa_code', $item->coa_code)
                    ->sum('debit');
                $totalCredit = PrepaidAccount::where('supplier_id', $item->supplier_id)
                    ->where('coa_code', $item->coa_code)
                    ->sum('credit');
                $item->available_balance = $totalDebit - $totalCredit;
                $item->display_label = $item->code . ' - ' . ($item->coa->name ?? $item->coa_code);
                return $item;
            })
            ->filter(fn($item) => $item->available_balance > 0)
            ->merge($selected)
            ->unique('id');
    }

    public function mount( $id = '', $supplier_id = '' ): void
    {
        $this->purchaseSettlement = PurchaseSettlement::find($id);
        $this->supplier_id = $supplier_id ?: $this->purchaseSettlement->supplier_id;
        $this->searchCashOut();
        $this->searchBankOut();
        $this->searchPrepaidAccounts();
        $this->bankOut = $this->bankOut ?? collect();
        $this->prepaidAccounts = $this->prepaidAccounts ?? collect();
    }

    public function with(): array
    {
        $this->open = $this->purchaseSettlement->status == 'open';

        return [
            'sources' => $this->purchaseSettlement->sources()->with(['currency'])->get()
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
        $this->bankOut = collect();
        $this->prepaidAccounts = collect();
        $this->resetValidation();
    }

    public function add(): void
    {
        $this->clearForm();
        $this->searchCashOut();
        $this->mode = 'add';
        $this->sourceDrawer = true;
    }

    public function edit(PurchaseSettlementSource $source): void
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
            'settleable_id' => ['required', new \App\Rules\SettlementSourceUniqueCheck(
                $this->settleable_type,
                'purchase_settlement_source',
                'purchase_settlement_code',
                $this->purchaseSettlement->code
            )],
            'currency_id' => 'required',
            'currency_rate' => ['required', new Number],
            'foreign_amount' => ['required', new Number],
        ]);        $currency_rate = Cast::number($this->currency_rate);
        $foreign_amount = Cast::number($this->foreign_amount);
        $amount = $foreign_amount * $currency_rate;

        if ($this->mode == 'add')
        {
            $sources = $this->purchaseSettlement->sources()->create([
                'payment_method' => $this->payment_method,
                'settleable_id' => $this->settleable_id,
                'settleable_type' => $this->settleable_type,
                'currency_id' => $this->currency_id,
                'currency_rate' => $currency_rate,
                'foreign_amount' => $foreign_amount,
                'amount' => $amount,
            ]);
        }

        $this->calculate();

        // Dispatch event to notify parent that source was added (for unsaved settlement warning)
        if ($this->mode == 'add' && $this->purchaseSettlement->saved == '0') {
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
        $source = PurchaseSettlementSource::find($id);
        $source->delete();

        $this->calculate();

        $this->success('Source has been deleted.');
    }

    public function updated($property, $value): void
    {
        if (in_array($property, ['payment_method'])) {
            $this->settleable_id = '';
            if ($this->payment_method == 'transfer') {
                $this->searchBankOut();
            } elseif ($this->payment_method == 'prepaid') {
                $this->searchPrepaidAccounts();
            } else {
                $this->bankOut = collect();
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
            $accountPayable = settings('account_payable_code');
            $cashOut = CashOut::where('code', $code)->first();
            if ($cashOut) {
                $detail = $cashOut->details()->where('coa_code', $accountPayable)->first();
                $amount = $cashOut->details()->where('coa_code', $accountPayable)->sum('foreign_amount');
                // if there's no foreign amount stored, try to get amount and convert using currency_rate
                if (Cast::number($amount) <= 0) {
                    $amountIdr = $cashOut->details()->where('coa_code', $accountPayable)->sum('amount');
                    if ($detail && Cast::number($detail->currency_rate) > 0) {
                        $amount = Cast::number($amountIdr) / Cast::number($detail->currency_rate);
                    } else {
                        $amount = $amountIdr;
                    }
                }
                // fallback to first detail if AP detail not found
                if (!$detail) {
                    $detail = $cashOut->details()->first();
                    $amount = $cashOut->details()->sum('foreign_amount');
                }
                $this->settleable_type = get_class($cashOut);
                $this->foreign_amount = Cast::money($amount);
                if ($detail) {
                    $this->currency_id = $detail->currency_id;
                    $this->currency_rate = $detail->currency_rate;
                } else {
                    $this->currency_id = settings('currency_id') ?? $this->currency_id;
                    $this->currency_rate = $this->currency_rate ?: 1;
                }
            }
        }

        if ($this->payment_method == 'transfer')
        {
            $accountPayable = settings('account_payable_code');
            $bankOut = BankOut::where('code', $code)->first();
            if ($bankOut) {
                // populate from AP detail / fallback
                $detail = $bankOut->details()->where('coa_code', $accountPayable)->first();
                $amount = $bankOut->details()->where('coa_code', $accountPayable)->sum('foreign_amount');
                if (Cast::number($amount) <= 0) {
                    $amountIdr = $bankOut->details()->where('coa_code', $accountPayable)->sum('amount');
                    if ($detail && Cast::number($detail->currency_rate) > 0) {
                        $amount = Cast::number($amountIdr) / Cast::number($detail->currency_rate);
                    } else {
                        $amount = $amountIdr;
                    }
                }
                if (!$detail) {
                    $detail = $bankOut->details()->first();
                    $amount = $bankOut->details()->sum('foreign_amount');
                }
                $this->settleable_type = get_class($bankOut);
                $this->foreign_amount = Cast::money($amount);
                if ($detail) {
                    $this->currency_id = $detail->currency_id;
                    $this->currency_rate = $detail->currency_rate;
                }
                $this->debugSelected = ['code' => $code, 'amount' => $amount, 'currency_id' => $this->currency_id, 'currency_rate' => $this->currency_rate];
            }
        }

        if ($this->payment_method == 'prepaid')
        {
            $prepaid = PrepaidAccount::where('code', $code)->with('coa')->first();
            if ($prepaid) {
                // Calculate available balance for this prepaid COA and supplier
                // For suppliers: available balance = total debit - total credit
                $totalDebit = PrepaidAccount::where('supplier_id', $prepaid->supplier_id)
                    ->where('coa_code', $prepaid->coa_code)
                    ->sum('debit');
                $totalCredit = PrepaidAccount::where('supplier_id', $prepaid->supplier_id)
                    ->where('coa_code', $prepaid->coa_code)
                    ->sum('credit');
                $availableBalance = $totalDebit - $totalCredit;

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
                    :options="$cashOut"
                    search-function="searchCashOut"
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
                <div class="text-xs text-gray-500 mb-2">Only payment numbers with account <strong>{{ settings('account_payable_code') }}</strong> (Trade Payable) are allowed.</div>
                <x-choices
                    label="Source : cash / bank / prepaid"
                    wire:model.live="settleable_id"
                    :options="$bankOut"
                    search-function="searchBankOut"
                    option-label="code"
                    option-sub-label="total_amount"
                    option-value="code"
                    single
                    searchable
                    clearable
                    placeholder="-- Select --"
                    :disabled="!$open"
                />
                @if($bankOut->isEmpty())
                <div class="text-sm text-error mt-2">No eligible Bank Out found for this supplier with account {{ settings('account_payable_code') }} (Trade Payable).</div>
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
                <div class="text-sm text-error mt-2">No prepaid account with available balance found for this supplier.</div>
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
