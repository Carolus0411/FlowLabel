<?php

use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Livewire\WithFileUploads;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Models\SalesSettlement;
use App\Models\SalesSettlementDetail;

new class extends Component {
    use Toast, WithFileUploads;

    public $file;
    public $mode = 'header';
    public $message = '';

    public function mount(): void
    {
        Gate::authorize('import salessettlement');
    }

    public function save()
    {
        $valid = $this->validate([
            'file' => 'required|mimes:xlsx|max:20480',
        ]);

        $target = $this->file->path();

        DB::beginTransaction();

        try {

            if ( file_exists( $target ) ) {

                $rows = SimpleExcelReader::create($target)->getRows();
                $rows->each(function(array $row) {

                    if ($this->mode == 'header') {
                        SalesSettlement::insert([
                            'code' => $row['code'],
                            'date' => $row['date']->format('Y-m-d'),
                            'note' => $row['note'],
                            'debit_total' => Cast::number($row['debit']),
                            'credit_total' => Cast::number($row['credit']),
                            'ref_id' => $row['ref_id'],
                            'type' => $row['type'],
                            'status' => 'close',
                            'saved' => '1',
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);
                    }

                    if ($this->mode == 'detail') {

                        $debit = $credit = 0;

                        if ($row['dc'] == 'D') {
                            $debit = Cast::number($row['amount']);
                            $credit = 0;
                            $amount = Cast::number($row['amount']);
                        } else {
                            $debit = 0;
                            $credit = Cast::number($row['amount']);
                            $amount = Cast::number($row['amount']) * -1;
                        }

                        $salessettlement = SalesSettlement::select(['id','date','status'])->where('code', $row['code'])->first();

                        if (!empty($salessettlement->id))
                        {
                            $type = match($row['type']) {
                                'Umum' => 'general',
                                'Adj' => 'adjustment',
                                default => $row['type'],
                            };

                            SalesSettlementDetail::create([
                                'code' => $row['code'],
                                'coa_code' => $row['coa_code'],
                                'description' => $row['description'],
                                'dc' => $row['dc'],
                                'debit' => $debit,
                                'credit' => $credit,
                                'amount' => $amount,
                                'date' => $salessettlement->date,
                                'status' => $salessettlement->status,
                                'type' => $type,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now(),
                            ]);
                        }
                    }

                });
            }

            DB::commit();
            $this->success('Success','SalesSettlement successfully imported.', redirectTo: route('salessettlement.import'));
        }
        catch (Exception $e)
        {
            DB::rollBack();
            logger()->error($e->getMessage());
            $this->error('Error','SalesSettlement failed to import.', redirectTo: route('salessettlement.import'));
        }
    }
}; ?>
@php
$modes = [
    ['id' => 'header', 'name' => 'Header'],
    ['id' => 'detail', 'name' => 'Detail'],
];
@endphp
<div>
    <x-header title="Import SalesSettlement" separator />
    <x-card>
        <x-form wire:submit="save">
            <x-file wire:model="file" label="File" hint="xlsx or csv" wire:target="save" wire:loading.attr="disabled" />
            <x-radio label="Mode" :options="$modes" wire:model="mode" />
            <x-slot:actions>
                <x-button label="Cancel" link="{{ route('salessettlement.index') }}" wire:target="save" wire:loading.attr="disabled" />
                <x-button label="Import" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>
