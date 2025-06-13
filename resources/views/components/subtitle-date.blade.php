@props([
    'date1',
    'date2',
])
<div {{ $attributes->merge(['class' => 'flex items-center gap-1']) }}>
    <div>Date :</div>
    <div class="bg-base-300 px-2 py-0 rounded-lg">{{ \App\Helpers\Cast::date($date1, 'd-M-y') }}</div>
    <div>to</div>
    <div class="bg-base-300 px-2 py-0 rounded-lg">{{ \App\Helpers\Cast::date($date2, 'd-M-y') }}</div>
</div>
