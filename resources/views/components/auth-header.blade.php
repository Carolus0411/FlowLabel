@props([
    'title',
    'description',
])

<div {!! $attributes->merge(['class' => 'flex flex-col w-full text-center']) !!}>
    <h1 class="text-lg font-semibold">{{ $title }}</h1>
    <h3 class="text-sm text-neutral-400">{{ $description }}</h3>
</div>
