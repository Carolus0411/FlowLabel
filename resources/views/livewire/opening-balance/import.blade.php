<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Spatie\SimpleExcel\SimpleExcelReader;
use Mary\Traits\Toast;
use App\Models\Balance;

new class extends Component {
    use Toast, WithFileUploads;

    public $file;

    public function mount(): void
    {
        Gate::authorize('import opening-balance');
    }

    public function save()
    {
        $valid = $this->validate([
            'file' => 'required|mimes:xlsx|max:2048',
        ]);

        $target = $this->file->path();

        DB::beginTransaction();

        $year = settings('opening_balance_period');

        try {

            if ( file_exists( $target ) ) {

                $rows = SimpleExcelReader::create($target)->getRows();
                $rows->each(function(array $row) use ($year) {

                    if ( !empty($year) AND !empty($row['coa_code']) )
                    {
                        if (!empty($row['id'])) {
                            $data['id'] = $row['id'];
                        }

                        if (!empty($row['id'])) {
                            Balance::where('id', $row['id'])->delete();
                        }

                        $balance = Balance::where('year', $year)->where('coa_code', $row['coa_code'])->first();
                        if (empty($balance->id))
                        {
                            $data['year'] = $year;
                            $data['coa_code'] = $row['coa_code'];
                            $data['dc'] = $row['dc'];
                            $data['debit'] = $row['debit'];
                            $data['credit'] = $row['credit'];

                            Balance::create($data);
                        }
                        else
                        {
                            $balance->update([
                                'dc' => $row['dc'],
                                'debit' => $row['debit'],
                                'credit' => $row['credit'],
                            ]);
                        }
                    }

                });
            }

            DB::commit();
            $this->success('Success','Balance successfully imported.', redirectTo: route('opening-balance.import'));
        }
        catch (Exception $e)
        {
            DB::rollBack();
            logger()->error($e->getMessage());
            $this->error('Error','Balance failed to import.', redirectTo: route('opening-balance.import'));
        }
    }
}; ?>

<div>
    <x-header title="Import Opening Balance" separator />
    <x-card>
        <x-form wire:submit="save">
            <x-file wire:model="file" label="File" hint="xlsx or csv" wire:target="save" wire:loading.attr="disabled" />
            <x-slot:actions>
                <x-button label="Cancel" link="{{ route('opening-balance.index') }}" wire:target="save" wire:loading.attr="disabled" />
                <x-button label="Import" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>
