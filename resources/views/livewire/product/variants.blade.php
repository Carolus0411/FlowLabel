<?php

use Illuminate\Support\Collection;
use Livewire\WithFileUploads;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Helpers\Cast;
use App\Models\Product;

new class extends Component {
    use Toast, WithFileUploads;

    public $product;
    public Collection $variants;

    public string $mode = '';
    public string $selected = '';
    public bool $drawer = false;
    public bool $open = true;

    public $sku = '';
    public $name = '';
    public $color_top = '';
    public $color_bottom = '';
    public $in_stock = false;
    public $price = 0;
    public $slash_price = 0;
    public $marketplace_url = 0;
    public $image;
    public $storedImage;

    public function mount( $id = '' ): void
    {
        $this->product = Product::find(intval($id));

        $this->variants = collect([]);

        if( $this->product->variants ?? false )
        {
            foreach ($this->product->variants()->get() as $variant)
            {
                $this->variants->push((object)[
                    'sku' => $variant->sku,
                    'name' => $variant->name,
                    'color_top' => $variant->color_top,
                    'color_bottom' => $variant->color_bottom,
                    'in_stock' => $variant->in_stock,
                    'price' => $variant->price,
                    'slash_price' => $variant->slash_price,
                    'marketplace_url' => $variant->marketplace_url,
                    'image' => $variant->image,
                ]);
            }
        }

        $this->dispatch('variants-updated', variants: $this->variants);
    }

    public function with(): array
    {
        return [];
    }

    public function clearForm(): void
    {
        $this->selected = '';
        $this->sku = '';
        $this->name = '';
        $this->color_top = '';
        $this->color_bottom = '';
        $this->in_stock = false;
        $this->price = '0';
        $this->slash_price = '0';
        $this->marketplace_url = '';
        $this->image = null;
        $this->storedImage = '';
        $this->resetValidation();
    }

    public function add()
    {
        $this->clearForm();
        $this->mode = 'add';
        $this->drawer = true;
    }

    public function edit(string $id)
    {
        $this->clearForm();

        $this->selected = $id;
        $target = $this->variants->get($id);

        $this->sku = $target->sku;
        $this->name = $target->name;
        $this->color_top = $target->color_top;
        $this->color_bottom = $target->color_bottom;
        $this->in_stock = $target->in_stock;
        $this->price = $target->price;
        $this->slash_price = $target->slash_price;
        $this->marketplace_url = $target->marketplace_url;
        $this->storedImage = $target->image;

        $this->mode = 'edit';
        $this->drawer = true;
    }

    public function save()
    {
        $data = $this->validate([
            'sku' => 'required',
            'name' => 'required',
            'color_top' => 'required',
            'color_bottom' => 'required',
            'in_stock' => 'required',
            'price' => 'required',
            'slash_price' => 'nullable',
            'marketplace_url' => 'nullable',
            'image' => 'nullable|image|mimes:jpg,png,webp,svg|max:1024',
        ]);

        if ($this->mode == 'add')
        {
            $image = null;
            if ($this->image) {
                $url = $this->image->store('products', 'public');
                $image =  "/storage/" . $url;
            }

            $this->variants->push((object)[
                'sku' => $this->sku,
                'name' => $this->name,
                'color_top' => $this->color_top,
                'color_bottom' => $this->color_bottom,
                'in_stock' => $this->in_stock,
                'price' => $this->price,
                'slash_price' => $this->slash_price,
                'marketplace_url' => $this->marketplace_url,
                'image' => $image,
            ]);
        }

        if ($this->mode == 'edit')
        {
            $image = null;
            if ($this->image) {
                $url = $this->image->store('products', 'public');
                $image =  "/storage/" . $url;
            }

            $this->variants->transform(function ($data, $key) use ($image) {
                if ($key == $this->selected) {
                    $data->sku = $this->sku;
                    $data->name = $this->name;
                    $data->color_top = $this->color_top;
                    $data->color_bottom = $this->color_bottom;
                    $data->in_stock = $this->in_stock;
                    $data->price = $this->price;
                    $data->slash_price = $this->slash_price;
                    $data->marketplace_url = $this->marketplace_url;
                    $data->image = $image;
                }
                return $data;
            });
        }

        $this->dispatch('variants-updated', variants: $this->variants);

        $this->drawer = false;
        $this->success('Variant successfully created.');
    }

    public function delete(string $id)
    {
        $this->variants->forget($id);

        $this->dispatch('variants-updated', variants: $this->variants);

        $this->success('Variant successfully deleted.');
    }

    public function deleteOnEdit()
    {
        if ($this->mode == 'edit')
        {
            $this->variants->forget($this->selected);
            $this->dispatch('variants-updated', variants: $this->variants);
            $this->success('Variant successfully deleted.');
            $this->drawer = false;
        }
        else
        {
            $this->error('Variant fail to delete.');
        }
    }
}; ?>
<div>
    <x-card title="Variants">
        <x-slot:menu>
            <x-button label="Add Variant" icon="o-plus" wire:click="add" spinner="add" class="" />
        </x-slot:menu>

        <table class="table">
        <thead>
        <tr>
            <th class="text-xs w-[60px]"></th>
            <th class="text-xs">SKU</td>
            <th class="text-xs">Name</th>
            <th class="text-xs">Color</th>
            <th class="text-xs">Stock</th>
            <th class="text-xs">Price</th>
        </tr>
        </thead>
        <tbody>

        @forelse ($variants as $key => $variant)
        <tr wire:loading.class="cursor-wait" class="divide-x divide-gray-200 dark:divide-gray-900 hover:bg-yellow-50 dark:hover:bg-gray-800 cursor-pointer">
            <td wire:click="edit('{{ $key }}')" class="!p-0">
                <div class="flex justify-center">
                    <img
                        src="{{ $variant->image ? $variant->image : asset('assets/img/image-placeholder.svg') }}"
                        class="w-10 h-10 object-cover object-center"
                    />
                </div>
            </td>
            <td wire:click="edit('{{ $key }}')" class="">{{ $variant->sku }}</td>
            <td wire:click="edit('{{ $key }}')" class="">{{ $variant->name }}</td>
            <td wire:click="edit('{{ $key }}')" class="">
                <div class="flex gap-1">
                    <div class="w-5 h-5" style="background-color:{{ $variant->color_top }};"></div>
                    <div class="w-5 h-5" style="background-color:{{ $variant->color_bottom }};"></div>
                </div>
            </td>
            <td wire:click="edit('{{ $key }}')" class="text-right">{{ $variant->in_stock ? 'In Stock' : 'Out Of Stock' }}</td>
            <td wire:click="edit('{{ $key }}')" class="text-right">{{ Cast::money($variant->price, 2) }}</td>
            {{-- <td>
                <div class="flex items-center">
                    <x-button icon="o-x-mark" wire: click="delete('{{ $key }}')" spinner="delete('{{ $key }}')" wire: confirm="Are you sure ?" class="btn-xs btn-ghost text-xs -m-1 text-error" />
                </div>
            </td> --}}
        </tr>
        @empty
        <tr class="divide-x divide-gray-200 dark:divide-gray-900 hover:bg-yellow-50 dark:hover:bg-gray-800">
            <td colspan="10" class="text-center">No record found.</td>
        </tr>
        @endforelse

        </tbody>
        </table>
    </x-card>

    {{-- DRAWER --}}
    <x-drawer wire:model="drawer" title="Variants" right separator with-close-button class="lg:w-1/3">
        <div class="space-y-4">
            <x-input label="Color Name" wire:model="name" />
            <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
                <x-input label="SKU" wire:model="sku" />
                <x-toggle label="In Stock" wire:model="in_stock" />
                <x-colorpicker label="Color Top" wire:model="color_top" />
                <x-colorpicker label="Color Bottom" wire:model="color_bottom" />
                <x-input label="Price" wire:model="price" x-mask:dynamic="$money($input,'.','')" />
                <x-input label="Slash Price" wire:model="slash_price" x-mask:dynamic="$money($input,'.','')" />
            </div>
            <x-input label="Marketplace URL" wire:model="marketplace_url" hint="Full url address" />
            <x-file label="Image" wire:model="image" accept="image/png, image/jpeg, image/svg+xml, image/webp" />
            @if ($storedImage)
            <img src="{{ $storedImage ? $storedImage : asset('assets/img/image-placeholder.svg') }}" class="w-20 h-20 rounded-lg object-cover object-center" />
            @endif
        </div>
        <x-slot:actions>
            <div class="w-full flex justify-between gap-2">
                <x-button label="Delete" icon="o-x-mark" wire:click="deleteOnEdit()" spinner="deleteOnEdit()" wire:confirm="Are you sure ?" class="btn-error" />
                <x-button label="Save" icon="o-paper-airplane" wire:click="save()" spinner="save()" class="btn-primary" />
            </div>
        </x-slot:actions>
    </x-drawer>
</div>
