@props([ 'data' ])
<div class="flex items-center">
    @if ($data->balance_amount == 0)
    <x-badge value="Paid" {{ $attributes->merge(['class' => 'text-xs badge-success']) }} />
    @elseif (($data->balance_amount > 0) AND ($data->balance_amount < $data->invoice_amount))
    <x-badge value="Outstanding" {{ $attributes->merge(['class' => 'text-xs badge-warning']) }} />
    @else
    <x-badge value="Unpaid" {{ $attributes->merge(['class' => 'text-xs badge-error']) }} />
    @endif
    {{-- @if ($status == 'paid')
    <x-badge value="Paid" {{ $attributes->merge(['class' => 'text-xs badge-success']) }} />
    @elseif ($status == 'outstanding')
    <x-badge value="Outstanding" {{ $attributes->merge(['class' => 'text-xs badge-warning']) }} />
    @elseif ($status == 'unpaid')
    <x-badge value="Unpaid" {{ $attributes->merge(['class' => 'text-xs badge-error']) }} />
    @endif --}}
</div>
