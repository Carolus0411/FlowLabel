<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Helpers\Code;
use App\Models\Intercash;
use App\Models\CashAccount;
use App\Models\BankAccount;
use App\Models\Currency;
use App\Models\CashOut;
use App\Models\CashOutDetail;
use App\Models\BankOut;
use App\Models\BankOutDetail;
use App\Models\CashIn;
use App\Models\CashInDetail;
use App\Models\BankIn;
use App\Models\BankInDetail;

new class extends Component {
    use Toast, \App\Traits\CashAccountChoice, \App\Traits\BankAccountChoice;

    public Intercash $intercash;

    public $code = '';
    public $date = '';
    public $description = '';
    public $type = 'cash-to-cash';
    public $from_account_id = '';
    public $from_account_type = 'cash';
    public $to_account_id = '';
    public $to_account_type = 'cash';
    public $from_account_code = '';
    public $to_account_code = '';
    public $no_code_from = '';
    public $no_code_to = '';
    public $currency_id = '';
    public $currency_rate = 1;
    public $foreign_amount = 0;
    public $amount = 0;
    public $status = '';
    public $approved_by = '';
    public $posted_by = '';

    public $closeConfirm = false;

    public function mount(): void
    {
        Gate::authorize('update intercash');
        $this->fill($this->intercash);

        // Determine account types based on data
        if ($this->intercash->from_cash_account_id) {
            $this->from_account_type = 'cash';
            $this->from_account_id = $this->intercash->from_cash_account_id;
        } elseif ($this->intercash->from_bank_account_id) {
            $this->from_account_type = 'bank';
            $this->from_account_id = $this->intercash->from_bank_account_id;
        }

        if ($this->intercash->to_cash_account_id) {
            $this->to_account_type = 'cash';
            $this->to_account_id = $this->intercash->to_cash_account_id;
        } elseif ($this->intercash->to_bank_account_id) {
            $this->to_account_type = 'bank';
            $this->to_account_id = $this->intercash->to_bank_account_id;
        }

        $this->no_code_from = $this->intercash->no_code_from;
        $this->no_code_to = $this->intercash->no_code_to;
    }

    public function save($close = false): void
    {
        $this->closeConfirm = false;

        $data = $this->validate([
            'code' => 'required',
            'date' => 'required',
            'type' => 'required',
            'from_account_id' => 'required',
            'to_account_id' => 'required',
            'currency_id' => 'required',
            'currency_rate' => 'required|numeric',
            'foreign_amount' => 'required|numeric',
        ]);

        $currency_rate = Cast::number($this->currency_rate);
        $foreign_amount = Cast::number($this->foreign_amount);
        $amount = $foreign_amount * $currency_rate;

        $updateData = [
            'code' => $this->code,
            'date' => $this->date,
            'description' => $this->description,
            'type' => $this->type,
            'currency_id' => $this->currency_id,
            'currency_rate' => $currency_rate,
            'foreign_amount' => $foreign_amount,
            'amount' => $amount,
        ];

        // Set from account
        if ($this->from_account_type == 'cash') {
            $updateData['from_cash_account_id'] = $this->from_account_id;
            $updateData['from_bank_account_id'] = null;
            $cashAccount = CashAccount::find($this->from_account_id);
            $updateData['from_account_code'] = $cashAccount?->code;
        } else {
            $updateData['from_bank_account_id'] = $this->from_account_id;
            $updateData['from_cash_account_id'] = null;
            $bankAccount = BankAccount::find($this->from_account_id);
            $updateData['from_account_code'] = $bankAccount?->code;
        }

        // Set to account
        if ($this->to_account_type == 'cash') {
            $updateData['to_cash_account_id'] = $this->to_account_id;
            $updateData['to_bank_account_id'] = null;
            $cashAccount = CashAccount::find($this->to_account_id);
            $updateData['to_account_code'] = $cashAccount?->code;
        } else {
            $updateData['to_bank_account_id'] = $this->to_account_id;
            $updateData['to_cash_account_id'] = null;
            $bankAccount = BankAccount::find($this->to_account_id);
            $updateData['to_account_code'] = $bankAccount?->code;
        }

        if ($this->intercash->saved == '0') {
            $code = Code::auto('IC');
            $updateData['code'] = $code;
            $updateData['saved'] = 1;
        }

        $this->intercash->update($updateData);

        if ($close) {
            $this->close();
        }

        $this->success('Intercash successfully updated.', redirectTo: route('intercash.index'));
    }

    public function close(): void
    {
        Gate::authorize('approve intercash');

        if ($this->intercash->status != 'open') {
            $this->error('Only open intercash can be approved.');
            return;
        }

        if (!$this->intercash->saved) {
            $this->error('Please save the intercash first before approving.');
            return;
        }

        DB::transaction(function () {
            // COA untuk Inter Cash - sesuaikan dengan sistem Anda
            $interCashCoaCode = '101-109'; // Inter Cash COA Code

            // Cari COA berdasarkan code
            $interCashCoa = \App\Models\Coa::where('code', $interCashCoaCode)->first();
            if (!$interCashCoa) {
                throw new \Exception('Inter Cash COA not found. Please create COA with code: ' . $interCashCoaCode);
            }

            $cashOutId = null;
            $bankOutId = null;
            $cashInId = null;
            $bankInId = null;
            $outCode = '';
            $inCode = '';

            // 1. Create OUT Transaction (Debet: Inter Cash, Credit: Bank/Cash Account)
            if ($this->from_account_type == 'cash') {
                $cashAccount = CashAccount::find($this->from_account_id);
                $prefix = 'CO/' . $cashAccount->code . '/';
                $outCode = Code::auto($prefix, $this->date);

                $cashOut = CashOut::create([
                    'code' => $outCode,
                    'date' => $this->date,
                    'type' => 'general',
                    'note' => $this->description ?: 'Intercash Transfer - ' . $this->code,
                    'cash_account_id' => $this->from_account_id,
                    'total_amount' => $this->amount,
                    'saved' => 1,
                    'status' => 'open',
                ]);

                $cashOutDetail = CashOutDetail::create([
                    'cash_out_id' => $cashOut->id,
                    'coa_id' => $interCashCoa->id,
                    'coa_code' => $interCashCoa->code,
                    'amount' => $this->amount,
                    'note' => $this->description,
                ]);

                // Close and generate journal
                $cashOut->update(['status' => 'close']);
                \App\Jobs\CashOutApprove::dispatchSync($cashOut);

                $cashOutId = $cashOut->id;

            } else {
                $bankAccount = BankAccount::find($this->from_account_id);
                $prefix = 'BO/' . $bankAccount->code . '/';
                $outCode = Code::auto($prefix, $this->date);

                $bankOut = BankOut::create([
                    'code' => $outCode,
                    'date' => $this->date,
                    'type' => 'general',
                    'note' => $this->description ?: 'Intercash Transfer - ' . $this->code,
                    'bank_account_id' => $this->from_account_id,
                    'total_amount' => $this->amount,
                    'saved' => 1,
                    'status' => 'open',
                ]);

                $bankOutDetail = BankOutDetail::create([
                    'bank_out_id' => $bankOut->id,
                    'coa_id' => $interCashCoa->id,
                    'coa_code' => $interCashCoa->code,
                    'amount' => $this->amount,
                    'note' => $this->description,
                ]);

                // Close and generate journal
                $bankOut->update(['status' => 'close']);
                \App\Jobs\BankOutApprove::dispatchSync($bankOut);

                $bankOutId = $bankOut->id;
            }

            // 2. Create IN Transaction (Debet: Bank/Cash Account, Credit: Inter Cash)
            if ($this->to_account_type == 'cash') {
                $cashAccount = CashAccount::find($this->to_account_id);
                $prefix = 'CI/' . $cashAccount->code . '/';
                $inCode = Code::auto($prefix, $this->date);

                $cashIn = CashIn::create([
                    'code' => $inCode,
                    'date' => $this->date,
                    'type' => 'general',
                    'note' => $this->description ?: 'Intercash Transfer - ' . $this->code,
                    'cash_account_id' => $this->to_account_id,
                    'total_amount' => $this->amount,
                    'saved' => 1,
                    'status' => 'open',
                ]);

                $cashInDetail = CashInDetail::create([
                    'cash_in_id' => $cashIn->id,
                    'coa_id' => $interCashCoa->id,
                    'coa_code' => $interCashCoa->code,
                    'amount' => $this->amount,
                    'note' => $this->description,
                ]);

                // Close and generate journal
                $cashIn->update(['status' => 'close']);
                \App\Jobs\CashInApprove::dispatchSync($cashIn);

                $cashInId = $cashIn->id;

            } else {
                $bankAccount = BankAccount::find($this->to_account_id);
                $prefix = 'BI/' . $bankAccount->code . '/';
                $inCode = Code::auto($prefix, $this->date);

                $bankIn = BankIn::create([
                    'code' => $inCode,
                    'date' => $this->date,
                    'type' => 'general',
                    'note' => $this->description ?: 'Intercash Transfer - ' . $this->code,
                    'bank_account_id' => $this->to_account_id,
                    'total_amount' => $this->amount,
                    'saved' => 1,
                    'status' => 'open',
                ]);

                $bankInDetail = BankInDetail::create([
                    'bank_in_id' => $bankIn->id,
                    'coa_id' => $interCashCoa->id,
                    'coa_code' => $interCashCoa->code,
                    'amount' => $this->amount,
                    'note' => $this->description,
                ]);

                // Close and generate journal
                $bankIn->update(['status' => 'close']);
                \App\Jobs\BankInApprove::dispatchSync($bankIn);

                $bankInId = $bankIn->id;
            }

            // Update intercash dengan reference IDs
            $this->intercash->update([
                'status' => 'approve',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'no_code_from' => $outCode,
                'no_code_to' => $inCode,
                'cash_out_id' => $cashOutId,
                'bank_out_id' => $bankOutId,
                'cash_in_id' => $cashInId,
                'bank_in_id' => $bankInId,
            ]);

            $this->no_code_from = $outCode;
            $this->no_code_to = $inCode;
        });

        $this->success('Intercash successfully approved and journals created.');
    }

    public function void(): void
    {
        Gate::authorize('void intercash');

        if ($this->intercash->status != 'approve') {
            $this->error('Only approved intercash can be voided.');
            return;
        }

        DB::transaction(function () {
            // Void related transactions
            if ($this->intercash->cash_out_id) {
                $cashOut = CashOut::find($this->intercash->cash_out_id);
                if ($cashOut && $cashOut->status == 'close') {
                    \App\Jobs\CashOutVoid::dispatchSync($cashOut);
                }
            }

            if ($this->intercash->bank_out_id) {
                $bankOut = BankOut::find($this->intercash->bank_out_id);
                if ($bankOut && $bankOut->status == 'close') {
                    \App\Jobs\BankOutVoid::dispatchSync($bankOut);
                }
            }

            if ($this->intercash->cash_in_id) {
                $cashIn = CashIn::find($this->intercash->cash_in_id);
                if ($cashIn && $cashIn->status == 'close') {
                    \App\Jobs\CashInVoid::dispatchSync($cashIn);
                }
            }

            if ($this->intercash->bank_in_id) {
                $bankIn = BankIn::find($this->intercash->bank_in_id);
                if ($bankIn && $bankIn->status == 'close') {
                    \App\Jobs\BankInVoid::dispatchSync($bankIn);
                }
            }

            $this->intercash->update([
                'status' => 'void',
            ]);
        });

        $this->success('Intercash successfully voided.', redirectTo: route('intercash.index'));
    }

    public function delete(Intercash $intercash): void
    {
        Gate::authorize('delete intercash');
        \App\Jobs\IntercashDelete::dispatchSync($this->intercash);
        $this->success('Intercash successfully deleted.', redirectTo: route('intercash.index'));
    }

    public function updated($property, $value): void
    {
        if ($property == 'from_account_type') {
            $this->from_account_id = '';
        }

        if ($property == 'to_account_type') {
            $this->to_account_id = '';
        }

        if (in_array($property, ['currency_rate', 'foreign_amount'])) {
            $this->amount = Cast::number($this->foreign_amount) * Cast::number($this->currency_rate);
        }
    }

    public function searchFromAccount(string $value = ''): void
    {
        if ($this->from_account_type == 'cash') {
            $this->searchCashAccount($value);
        } else {
            $this->searchBankAccount($value);
        }
    }

    public function searchToAccount(string $value = ''): void
    {
        if ($this->to_account_type == 'cash') {
            $this->searchCashAccount($value);
        } else {
            $this->searchBankAccount($value);
        }
    }

    public function with(): array
    {
        $currencies = Currency::where('is_active', 1)->get();

        // Use trait-provided collections that include search support
        // $this->cashAccountChoice and $this->bankAccountChoice are provided by CashAccountChoice and BankAccountChoice traits
        $fromAccountOptions = $this->from_account_type == 'cash' ? $this->cashAccountChoice : $this->bankAccountChoice;
        $toAccountOptions = $this->to_account_type == 'cash' ? $this->cashAccountChoice : $this->bankAccountChoice;

        return [
            'fromAccountOptions' => $fromAccountOptions,
            'toAccountOptions' => $toAccountOptions,
            'currencies' => $currencies,
            'isOpen' => $this->intercash->status == 'open',
            'approvedBy' => $this->intercash->approvedBy?->name,
            'postedBy' => $this->intercash->postedBy?->name,
        ];
    }
}; ?>

<div>
    <div class="lg:top-[65px] lg:sticky z-10 bg-base-200 pb-0 pt-3">
        <x-header separator>
            <x-slot:title>
                <div class="flex items-center gap-4">
                    <span>{{ $intercash->id ? 'Update' : 'Create' }} Intercash</span>
                    <x-status-badge :status="$intercash->status" class="uppercase text-sm" />
                </div>
            </x-slot:title>
            <x-slot:actions>
                <x-button label="Back" link="{{ route('intercash.index') }}" icon="o-arrow-uturn-left" class="btn-soft" responsive />
                @if ($intercash->status == 'approve' && Gate::allows('void intercash'))
                <x-button label="Void" icon="o-x-mark" wire:click="void" wire:confirm="Are you sure you want to void this intercash?" spinner="void" class="btn-error" responsive />
                @endif
                @if ($intercash->status == 'open' && Gate::allows('approve intercash'))
                <x-button label="Close" icon="o-check" wire:click="close" spinner="close" class="btn-success" responsive />
                @endif
                @if ($intercash->status == 'open')
                <x-button label="Save" icon="o-paper-airplane" wire:click="save" spinner="save" class="btn-primary" responsive />
                @endif
                @if ($intercash->status == 'open' && Gate::allows('delete intercash'))
                <x-button label="Delete" icon="o-trash" wire:click="delete" wire:confirm="Are you sure you want to delete this intercash?" spinner="delete" class="btn-error" responsive />
                @endif
            </x-slot:actions>
        </x-header>
    </div>

    <div class="space-y-4">
        <x-card>
            <x-form wire:submit="save">
                <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-4 gap-4">
                    <x-input label="No. IC" wire:model="code" readonly class="bg-base-200" />
                    <x-datetime label="Date" wire:model="date" />
                    <x-input label="Description" wire:model="description" />
                </div>

                <div class="divider">From Account</div>
                <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
                    <x-select
                        label="From Account Type"
                        wire:model.live="from_account_type"
                        :options="[['id' => 'cash', 'name' => 'Cash Account'], ['id' => 'bank', 'name' => 'Bank Account']]"
                        option-label="name"
                        option-value="id"
                        :disabled="!$isOpen"
                    />
                    <x-choices
                        label="From Account"
                        wire:model="from_account_id"
                        :options="$fromAccountOptions"
                        search-function="searchFromAccount"
                        option-label="name"
                        option-sub-label="code"
                        option-value="id"
                        single
                        searchable
                        clearable
                        placeholder="-- Select --"
                        :disabled="!$isOpen || empty($fromAccountOptions)"
                    />
                </div>

                <div class="divider">To Account</div>
                <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
                    <x-select
                        label="To Account Type"
                        wire:model.live="to_account_type"
                        :options="[['id' => 'cash', 'name' => 'Cash Account'], ['id' => 'bank', 'name' => 'Bank Account']]"
                        option-label="name"
                        option-value="id"
                        :disabled="!$isOpen"
                    />
                    <x-choices
                        label="To Account"
                        wire:model="to_account_id"
                        :options="$toAccountOptions"
                        search-function="searchToAccount"
                        option-label="name"
                        option-sub-label="code"
                        option-value="id"
                        single
                        searchable
                        clearable
                        placeholder="-- Select --"
                        :disabled="!$isOpen || empty($toAccountOptions)"
                    />
                </div>

                @if ($intercash->status != 'open')
                <div class="divider">Generated Transactions</div>
                <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">
                            <span class="label-text">Transaction OUT</span>
                        </label>
                        @if ($intercash->no_code_from)
                            <div class="flex items-center gap-2">
                                <x-input wire:model="no_code_from" readonly class="bg-base-200 flex-1" />
                                @if ($intercash->cash_out_id)
                                    <x-button icon="o-eye" link="{{ route('cash-out.edit', $intercash->cash_out_id) }}" class="btn-sm btn-ghost" tooltip="View Cash Out" />
                                @elseif ($intercash->bank_out_id)
                                    <x-button icon="o-eye" link="{{ route('bank-out.edit', $intercash->bank_out_id) }}" class="btn-sm btn-ghost" tooltip="View Bank Out" />
                                @endif
                            </div>
                        @else
                            <x-input value="Not generated yet" readonly class="bg-base-200" />
                        @endif
                    </div>
                    <div>
                        <label class="label">
                            <span class="label-text">Transaction IN</span>
                        </label>
                        @if ($intercash->no_code_to)
                            <div class="flex items-center gap-2">
                                <x-input wire:model="no_code_to" readonly class="bg-base-200 flex-1" />
                                @if ($intercash->cash_in_id)
                                    <x-button icon="o-eye" link="{{ route('cash-in.edit', $intercash->cash_in_id) }}" class="btn-sm btn-ghost" tooltip="View Cash In" />
                                @elseif ($intercash->bank_in_id)
                                    <x-button icon="o-eye" link="{{ route('bank-in.edit', $intercash->bank_in_id) }}" class="btn-sm btn-ghost" tooltip="View Bank In" />
                                @endif
                            </div>
                        @else
                            <x-input value="Not generated yet" readonly class="bg-base-200" />
                        @endif
                    </div>
                </div>
                @endif

                <div class="divider">Amount</div>
                <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-4 gap-4">
                    <x-choices-offline
                        label="Currency"
                        :options="$currencies"
                        wire:model="currency_id"
                        option-label="code"
                        option-value="id"
                        single
                        :disabled="!$isOpen"
                    />
                    <x-input label="Kurs" wire:model="currency_rate" :disabled="!$isOpen" x-mask:dynamic="$money($input,'.','')" />
                    <x-input label="Foreign Amount" wire:model="foreign_amount" :disabled="!$isOpen" x-mask:dynamic="$money($input,'.','')" />
                    <x-input label="Amount (IDR)" wire:model="amount" readonly class="bg-base-200" x-mask:dynamic="$money($input,'.','')" />
                </div>

                @if ($intercash->status != 'open')
                <div class="divider">Approvals</div>
                <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
                    <x-input label="Approved By" value="{{ $approvedBy }}" readonly class="bg-base-200" />
                    <x-input label="Posted By" value="{{ $postedBy }}" readonly class="bg-base-200" />
                </div>
                @endif
            </x-form>
        </x-card>
    </div>
</div>
