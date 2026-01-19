<div class="flex items-center justify-between border-b pb-2 mb-4">
    <div class="flex items-center gap-2">
        <x-icon name="o-cube" class="w-6 text-purple-500" />
        <div class="text-lg font-bold">{{ config('app.name') }}</div>
    </div>

    <div class="text-right">
        <div class="text-xl font-semibold">{{ $title ?? '' }}</div>
        <div class="text-sm text-muted">{{ now()->toDateString() }}</div>
    </div>
</div>
