<x-mail::message>
# {{ $data['title'] }}

{{ $data['body'] }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

