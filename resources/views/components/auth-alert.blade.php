@props([
    'status',
])

@if ($status)
    <div role="alert" {{ $attributes->merge(['class' => 'alert']) }}>
        <span>{{ $status }}</span>
    </div>
@endif
