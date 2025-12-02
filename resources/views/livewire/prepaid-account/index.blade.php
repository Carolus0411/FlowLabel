<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Session;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Mary\Traits\Toast;
use App\Models\PrepaidAccount;
use App\Models\Contact;
use App\Models\Supplier;
use App\Models\Coa;
use App\Helpers\Cast;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'prepaid_date_from')]
    public string $date_from = '';

    #[Session(key: 'prepaid_date_to')]
    public string $date_to = '';

    #[Session(key: 'prepaid_contact_id')]
    public $contact_id = '';

    #[Session(key: 'prepaid_supplier_id')]
    public $supplier_id = '';

    #[Session(key: 'prepaid_coa_code')]
    public $coa_code = '';

    #[Session(key: 'prepaid_source_type')]
    public $source_type = '';

    #[Session(key: 'prepaid_code')]
    public $code = '';

    public Collection $contacts;
    public Collection $suppliers;
    public Collection $coaOptions;
    public bool $drawer = false;
    public int $filterCount = 0;

    #[Session(key: 'prepaid_per_page')]
    public int $perPage = 10;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];

    public function mount(): void
    {
        Gate::authorize('view prepaid-account');

        if (empty($this->date_from)) {
            $this->date_from = date('Y-m-01');
        }
        if (empty($this->date_to)) {
            $this->date_to = date('Y-m-d');
        }

        $this->searchContact('');
        $this->searchSupplier('');
        $this->loadCoaOptions();
        $this->updateFilterCount();
    }

    public function loadCoaOptions(): void
    {
        $prepaidCodes = PrepaidAccount::getPrepaidCoaCodes();
        $this->coaOptions = Coa::whereIn('code', $prepaidCodes)->get();
    }

    public function prepaidQuery()
    {
        $query = PrepaidAccount::query()
            ->when(!empty($this->date_from), fn($q) => $q->whereDate('date', '>=', $this->date_from))
            ->when(!empty($this->date_to), fn($q) => $q->whereDate('date', '<=', $this->date_to))
            ->when(!empty($this->code), function ($q) { $q->filterLike('code', $this->code); })
            ->when(!empty($this->coa_code), fn($q) => $q->where('coa_code', $this->coa_code))
            ->when(!empty($this->source_type), fn($q) => $q->where('source_type', $this->source_type))
            ->when(!empty($this->contact_id), function ($q) {
                $value = $this->contact_id;
                if (is_array($value)) $value = $value['id'] ?? null;
                if (is_object($value)) $value = $value->id ?? $value;
                if (is_string($value) && is_numeric($value)) $value = intval($value);
                return $q->where('contact_id', $value);
            })
            ->when(!empty($this->supplier_id), function ($q) {
                $value = $this->supplier_id;
                if (is_array($value)) $value = $value['id'] ?? null;
                if (is_object($value)) $value = $value->id ?? $value;
                if (is_string($value) && is_numeric($value)) $value = intval($value);
                return $q->where('supplier_id', $value);
            })
            ->with(['coa', 'contact', 'supplier'])
            ->orderBy(...array_values($this->sortBy));

        return $query;
    }

    public function prepaidAccounts(): LengthAwarePaginator
    {
        return $this->prepaidQuery()->paginate($this->perPage);
    }

    public function prepaidTotals(): array
    {
        $query = $this->prepaidQuery()->toBase();
        $row = $query->selectRaw('COUNT(*) as total_count, COALESCE(SUM(debit),0) as sum_debit, COALESCE(SUM(credit),0) as sum_credit')->first();
        return [
            'total_count' => (int) ($row->total_count ?? 0),
            'sum_debit' => (float) ($row->sum_debit ?? 0),
            'sum_credit' => (float) ($row->sum_credit ?? 0),
        ];
    }

    public function with(): array
    {
        $accounts = $this->prepaidAccounts();
        $totals = $this->prepaidTotals();

        return [
            'headers' => $this->headers(),
            'prepaidAccounts' => $accounts,
            'total_count' => $totals['total_count'],
            'sum_debit' => $totals['sum_debit'],
            'sum_credit' => $totals['sum_credit'],
            'balance' => $totals['sum_debit'] - $totals['sum_credit'],
            'sourceTypes' => $this->getSourceTypes(),
        ];
    }

    public function headers(): array
    {
        return [
            ['key' => 'code', 'label' => 'Prepaid No.'],
            ['key' => 'date', 'label' => 'Date', 'format' => ['date', 'd/m/Y']],
            ['key' => 'coa.name', 'label' => 'Account Prepaid', 'sortable' => false],
            ['key' => 'party_name', 'label' => 'Contact/Supplier', 'sortable' => false],
            ['key' => 'source_type', 'label' => 'Source'],
            ['key' => 'source_code', 'label' => 'Transaction Ref'],
            ['key' => 'debit', 'label' => 'Debit', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'credit', 'label' => 'Credit', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
        ];
    }

    public function getSourceTypes(): array
    {
        return [
            ['id' => 'CashIn', 'name' => 'Cash In'],
            ['id' => 'CashOut', 'name' => 'Cash Out'],
            ['id' => 'BankIn', 'name' => 'Bank In'],
            ['id' => 'BankOut', 'name' => 'Bank Out'],
        ];
    }

    public function searchContact(string $value = ''): void
    {
        $selected = Contact::where('id', intval($this->contact_id))->get();
        $this->contacts = Contact::query()
            ->filterLike('name', $value)
            ->isActive()
            ->orderBy('name')
            ->take(20)
            ->get()
            ->merge($selected);
    }

    public function searchSupplier(string $value = ''): void
    {
        $selected = Supplier::where('id', intval($this->supplier_id))->get();
        $this->suppliers = Supplier::query()
            ->filterLike('name', $value)
            ->isActive()
            ->orderBy('name')
            ->take(20)
            ->get()
            ->merge($selected);
    }

    public function updated($property): void
    {
        if (! is_array($property) && $property != "") {
            $this->resetPage();
            $this->updateFilterCount();
        }
    }

    public function updateFilterCount(): void
    {
        $count = 0;
        if (!empty($this->code)) $count++;
        if (!empty($this->contact_id)) $count++;
        if (!empty($this->supplier_id)) $count++;
        if (!empty($this->coa_code)) $count++;
        if (!empty($this->source_type)) $count++;
        if (!empty($this->date_from)) $count++;
        if (!empty($this->date_to)) $count++;
        $this->filterCount = $count;
    }

    public function clear(): void
    {
        $this->date_from = date('Y-m-01');
        $this->date_to = date('Y-m-d');
        $this->success('Filters cleared.');
        $this->reset(['code', 'contact_id', 'supplier_id', 'coa_code', 'source_type']);
        $this->resetPage();
        $this->updateFilterCount();
        $this->drawer = false;
    }

    public function search(): void
    {
        $data = $this->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
            'contact_id' => 'nullable',
            'supplier_id' => 'nullable',
            'coa_code' => 'nullable',
            'source_type' => 'nullable',
            'code' => 'nullable',
        ]);

        $this->drawer = false;
    }

    public function export()
    {
        Gate::authorize('export prepaid-account');

        $query = $this->prepaidQuery()->orderBy('id', 'asc');
        $writer = SimpleExcelWriter::streamDownload('Prepaid Account.xlsx');

        foreach ($query->lazy() as $account) {
            $writer->addRow([
                'id' => $account->id ?? '',
                'code' => $account->code ?? '',
                'date' => $account->date ?? '',
                'coa_code' => $account->coa_code ?? '',
                'coa_name' => $account->coa->name ?? '',
                'contact' => $account->contact->name ?? '',
                'supplier' => $account->supplier->name ?? '',
                'source_type' => $account->source_type ?? '',
                'source_code' => $account->source_code ?? '',
                'debit' => $account->debit ?? 0,
                'credit' => $account->credit ?? 0,
                'note' => $account->note ?? '',
            ]);
        }

        return response()->streamDownload(function() use ($writer) {
            $writer->close();
        }, 'Prepaid Account.xlsx');
    }
}; ?>

<div>
    <x-header title="Prepaid Account" separator>
        <x-slot:subtitle>
            Track prepaid transactions from Cash In, Cash Out, Bank In, Bank Out
        </x-slot:subtitle>
        <x-slot:actions>
            @can('export prepaid-account')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" class="btn-soft" />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
        </x-slot:actions>
    </x-header>

    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$prepaidAccounts" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text>
            @scope('cell_code', $account)
            <div class="font-medium">{{ $account->code }}</div>
            @endscope
            @scope('cell_date', $account)
            <div class="whitespace-nowrap">{{ \Carbon\Carbon::parse($account->date)->format('d/m/Y') }}</div>
            @endscope
            @scope('cell_coa.name', $account)
            <div>
                <div class="font-medium">{{ $account->coa->code ?? '' }}</div>
                <div class="text-xs text-slate-500">{{ $account->coa->name ?? '' }}</div>
            </div>
            @endscope
            @scope('cell_party_name', $account)
            <div class="whitespace-nowrap">
                @if($account->contact_id)
                    <span class="text-blue-600">{{ $account->contact->name ?? '' }}</span>
                @elseif($account->supplier_id)
                    <span class="text-green-600">{{ $account->supplier->name ?? '' }}</span>
                @else
                    <span class="text-slate-400">-</span>
                @endif
            </div>
            @endscope
            @scope('cell_source_type', $account)
            <x-badge :value="$account->source_type" class="badge-soft badge-sm" />
            @endscope
            @scope('cell_source_code', $account)
            <div class="font-mono text-sm">{{ $account->source_code }}</div>
            @endscope
            @scope('cell_debit', $account)
            <div class="text-right">{{ $account->debit > 0 ? number_format($account->debit, 2) : '-' }}</div>
            @endscope
            @scope('cell_credit', $account)
            <div class="text-right">{{ $account->credit > 0 ? number_format($account->credit, 2) : '-' }}</div>
            @endscope

            <x-slot:footer>
                <tr class="bg-base-200">
                    <td colspan="6" class="text-right font-semibold">Total (Count: {{ $total_count }})</td>
                    <td class="text-right font-semibold">{{ \App\Helpers\Cast::money($sum_debit, 2) }}</td>
                    <td class="text-right font-semibold">{{ \App\Helpers\Cast::money($sum_credit, 2) }}</td>
                </tr>
                <tr class="bg-base-300">
                    <td colspan="6" class="text-right font-bold">Balance</td>
                    <td colspan="2" class="text-right font-bold">{{ \App\Helpers\Cast::money($balance, 2) }}</td>
                </tr>
            </x-slot>
        </x-table>
    </x-card>

    {{-- FILTER DRAWER --}}
    <x-search-drawer>
    <x-grid>
        <x-datetime label="Date From" wire:model="date_from" />
        <x-datetime label="Date To" wire:model="date_to" />
        <x-input label="Prepaid No." wire:model="code" />
        <x-choices-offline
            label="Account Prepaid"
            :options="$coaOptions"
            wire:model="coa_code"
            option-label="name"
            option-value="code"
            single
            searchable
            clearable
            placeholder="-- All Accounts --"
        />
        <x-choices-offline
            label="Source Type"
            :options="$sourceTypes"
            wire:model="source_type"
            option-label="name"
            option-value="id"
            single
            searchable
            clearable
            placeholder="-- All Sources --"
        />
        <x-choices
            label="Contact"
            wire:model="contact_id"
            :options="$contacts"
            search-function="searchContact"
            option-label="name"
            single
            searchable
            clearable
            placeholder="-- Select --"
        />
        <x-choices
            label="Supplier"
            wire:model="supplier_id"
            :options="$suppliers"
            search-function="searchSupplier"
            option-label="name"
            single
            searchable
            clearable
            placeholder="-- Select --"
        />
    </x-grid>
    </x-search-drawer>
</div>
