@props([ 'status' ])
<div class="flex items-center {{ $boxClass ?? '' }}">
    @if ($status == 'paid')
    <x-badge value="Paid" {{ $attributes->merge(['class' => 'text-xs badge-success']) }} />
    @elseif ($status == 'outstanding')
    <x-badge value="Outstanding" {{ $attributes->merge(['class' => 'text-xs badge-warning']) }} />
    @elseif ($status == 'unpaid')
    <x-badge value="Unpaid" {{ $attributes->merge(['class' => 'text-xs badge-error']) }} />
    @endif
</div>
