<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Mail\TestMail;

new class extends Component {
    use Toast;

    public string $recipient = 'akhmadshaleh@gmail.com';

    public function mount(): void
    {
        Gate::authorize('send test mail');
    }

    public function save(): void
    {
        $data = $this->validate([
            'recipient' => 'required|email',
        ]);

        Mail::to($this->recipient)->queue(new TestMail([
            'title' => 'Mail from TNC Express.',
            'body' => 'This is for testing email using smtp.',
        ]));

        // Mail::to($this->recipient)->send(new TestMail([
        //     'title' => 'The Title',
        //     'body' => 'The Body',
        // ]));

        $this->success('Mail has been sent.');
    }
}; ?>

<div>
    <x-header title="Send Test Mail" separator />

    <x-form wire:submit="save">
        <x-card>
            <table class="table table-sm">
            <tr class="hover">
                <td class="md:w-[150px]">Host</td>
                <td>{{ env('MAIL_HOST') }}</td>
            </tr>
            <tr class="hover">
                <td>Port</td>
                <td>{{ env('MAIL_PORT') }}</td>
            </tr>
            <tr class="hover">
                <td>Username</td>
                <td>{{ env('MAIL_USERNAME') }}</td>
            </tr>
            <tr class="hover">
                <td>Password</td>
                <td>{{ '******' /*env('MAIL_PASSWORD')*/ }}</td>
            </tr>
            <tr class="hover">
                <td>Encryption</td>
                <td>{{ env('MAIL_ENCRYPTION') }}</td>
            </tr>
            <tr class="hover">
                <td>From Address</td>
                <td>{{ env('MAIL_FROM_ADDRESS') }}</td>
            </tr>
            <tr class="hover">
                <td>From Name</td>
                <td>{{ env('MAIL_FROM_NAME') }}</td>
            </tr>
            </table>
        </x-card>

        <x-card>
            <div class="space-y-4">
                <x-input label="Recipient" wire:model="recipient" />
            </div>
        </x-card>
        <x-slot:actions>
            <x-button label="Send Test Mail" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
