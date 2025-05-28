@props([
    'label' => '',
    'options' => [],
    'search' => 'search(value)',
    'optionlabel' => 'name',
    'optionvalue' => 'id',
    'placeholder' => '-- Select --',
    'disabled' => false
])
@php
$live = '';
if ($attributes->has('wire:model.live')) $live = '.live';
$name = $attributes->whereStartsWith('wire:model')->first();
$uuid = md5($name);
$_errors = $errors->get($name);
$hasError = $errors->has($name);
@endphp
<div
    x-data="{
        open: false,
        selection: $wire.entangle('{{ $name }}'){{ $live }},
        hasValue: false
    }"
>
    <fieldset class="fieldset py-0">

    {{-- LABEL --}}
    @unless(empty($label))
    <legend class="fieldset-legend mb-0.5">
        {{ $label }}
    </legend>
    @endunless

    {{-- MAIN --}}
    <div
        x-data="{
            options: {{ json_encode($options) }},
            placeholder: '{{ $placeholder }}',
            preventSearch: false,
            init() {
                let self = this;
                if ((this.selection != null) && (this.selection != '')) {
                    this.hasValue = true;
                    $refs.label.innerHTML = this.options.find(i => i.{{ $optionvalue }} == this.selection).{{ $optionlabel }};
                }
                $wire.on('clear_{{ $name }}', (event) => {
                    self.clear();
                });
            },
            toggle() {
                this.open =! this.open;
            },
            search(value) {
                if(!this.preventSearch) {
                    @this.{!! $search !!};
                    $refs.keyword.focus();
                }
                this.preventSearch = false;
            },
            select(id, label) {
                this.selection = id;
                $refs.label.innerHTML = label;
                this.hasValue = true;
                this.open = false;
            },
            clear() {
                $refs.label.innerHTML = this.placeholder;
                this.selection = '';
                this.hasValue = false;
                this.open = true;
            },
        }"
        x-ref="button"
        @keydown.enter="toggle()"
        @click.outside="open = false"
        @keyup.esc = "open = false"
        @keydown.down="open = true"
        @keydown.up="open = true"
        class="relative"
    >
        <span x-cloak x-show="hasValue" @click="clear" class="z-40 absolute top-1/2 -translate-y-1/2 right-10 flex items-center cursor-pointer text-gray-400 hover:text-gray-600">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </span>

        <div
            @click="toggle()"
            tabindex="0"
            {{
                $attributes->whereStartsWith('class')->class([
                    "w-full select flex items-center justify-start",
                    "select-error" => $hasError,
                ])
            }}
        >
            <p wire:ignore x-ref="label" class="text-sm truncate max-w-[300px]">
                {!! empty($placeholder) ? '&nbsp;' : $placeholder !!}
            </p>
        </div>

        <div
            x-cloak
            x-show="open"
            x-trap.noscroll="open"
            x-anchor.bottom-start.offset.5="$refs.button"
            class="absolute z-50 w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-800 rounded-md shadow"
            @keydown.down="$focus.wrap().next()"
            @keydown.up="$focus.wrap().previous()"
        >
            <div class="relative p-2 border-b border-gray-200 dark:border-gray-800">
                {{-- INPUT --}}
                <input
                    x-ref="keyword"
                    @keydown.enter.stop.prevent="preventSearch = true"
                    @keydown.up="preventSearch = true"
                    @keydown.down="preventSearch = true"
                    @keydown.left="preventSearch = true"
                    @keydown.right="preventSearch = true"
                    @keydown.tab="preventSearch = true"
                    @keydown.debounce.500ms="search($el.value)"
                    type="text"
                    autofocus
                    placeholder="Search"
                    class="w-full input focus:outline-0"
                />

                {{-- PROGRESS --}}
                <progress wire:loading wire:target="{{ preg_replace('/\((.*?)\)/', '', $search) }}" class="progress progress-primary absolute left-0 bottom-0 h-0.5"></progress>
            </div>
            <div
                class="max-h-[200px] overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800"
            >
                {{-- OPTIONS LIST --}}
                @forelse ( $options as $key => $option )
                <button
                    wire:key="choice-item-{{ $uuid }}-{{ $key }}"
                    id="choice-item-{{ $uuid }}-{{ $key }}"
                    type="button"
                    @click="select('{{ $option->{$optionvalue} ?? '' }}','{{ $option->{$optionlabel} ?? '' }}')"
                    @keyup.enter="select('{{ $option->{$optionvalue} ?? '' }}','{{ $option->{$optionlabel} ?? '' }}')"
                    class="block w-full text-left text-sm cursor-pointer p-2 hover:bg-gray-200 dark:hover:bg-slate-800 focus:bg-gray-200 dark:focus:bg-slate-800 focus:outline-none"
                >
                    {{ $option->{$optionlabel} ?? '' }}
                </button>
                @empty
                <div class="p-2 text-sm">No data found.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ERROR MESSAGE --}}
    @if ($_errors)
    <div class="text-xs text-error">
        @foreach ((array) $_errors as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
    @endif

    </fieldset>
</div>
