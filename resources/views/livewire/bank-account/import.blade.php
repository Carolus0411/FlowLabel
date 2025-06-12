<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Spatie\SimpleExcel\SimpleExcelReader;
use Mary\Traits\Toast;
use App\Models\Bank;
use App\Models\Currency;
use App\Models\Coa;
use App\Models\BankAccount;

new class extends Component {
    use Toast, WithFileUploads;

    public $file;

    public function mount(): void
    {
        Gate::authorize('import bank-account');
    }

    public function save()
    {
        $valid = $this->validate([
            'file' => 'required|mimes:xlsx|max:2048',
        ]);

        $target = $this->file->path();

        DB::beginTransaction();

        try {

            if ( file_exists( $target ) ) {

                $rows = SimpleExcelReader::create($target)->getRows();
                $rows->each(function(array $row) {

                    if ( !empty($row['name']) )
                    {
                        if (!empty($row['id'])) {
                            $data['id'] = $row['id'];
                        }

                        if (!empty($row['id'])) {
                            BankAccount::where('id', $row['id'])->delete();
                        }

                        $bank = Bank::where('id', $row['bank_id'])->first();
                        $currency = Currency::where('id', $row['currency_id'])->first();
                        $coa = Coa::where('code', $row['coa_code'])->first();

                        $data['code'] = $row['code'];
                        $data['name'] = $row['name'];
                        $data['bank_id'] = $bank->id ?? '';
                        $data['currency_id'] = $currency->id ?? '';
                        $data['coa_code'] = $coa->code ?? '';
                        $data['is_active'] = $row['is_active'];

                        BankAccount::create($data);
                    }

                });
            }

            DB::commit();
            $this->success('Success','Bank account successfully imported.', redirectTo: route('bank-account.index'));
        }
        catch (Exception $e)
        {
            DB::rollBack();
            logger()->error($e->getMessage());
            $this->error('Error','Bank account failed to import.', redirectTo: route('bank-account.index'));
        }
    }
}; ?>

<div>
    <x-header title="Import Bank Account" separator />
    <x-card>
        <x-form wire:submit="save">
            <x-file wire:model="file" label="File" hint="xlsx or csv" wire:target="save" wire:loading.attr="disabled" />
            <x-slot:actions>
                <x-button label="Cancel" link="{{ route('bank-account.index') }}" wire:target="save" wire:loading.attr="disabled" />
                <x-button label="Import" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>
