<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Livewire\WithFileUploads;
use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Cviebrock\EloquentSluggable\Services\SlugService;
use Mary\Traits\Toast;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Product;

new class extends Component {
    use Toast, WithFileUploads;

    public Product $product;

    public $name = '';
    public $description = '';
    public $features = '';
    public $category_id = '';
    public $brand_id = '';
    public $dimension = '';
    public $weight = '';
    public $slug = '';
    public $meta_description = '';
    public $meta_keyword = '';
    public bool $is_active = false;
    public bool $is_featured = false;
    public bool $is_new = false;

    public $image1;
    public $image2;
    public $image3;
    public $image4;
    public $image5;
    public $storedImage1;
    public $storedImage2;
    public $storedImage3;
    public $storedImage4;
    public $storedImage5;

    public Collection $variants;

    public function mount(): void
    {
        Gate::authorize('update product');
        $this->fill($this->product);
        $this->variants = collect([]);

        $this->image1 = '';
        $this->image2 = '';
        $this->image3 = '';
        $this->image4 = '';
        $this->image5 = '';
        $this->storedImage1 = $this->product->image1;
        $this->storedImage2 = $this->product->image2;
        $this->storedImage3 = $this->product->image3;
        $this->storedImage4 = $this->product->image4;
        $this->storedImage5 = $this->product->image5;
    }

    public function with(): array
    {
        return [
            'category' => Category::query()->orderBy('name')->get(),
            'brand' => Brand::query()->orderBy('name')->get(),
        ];
    }

    #[On('variants-updated')]
    public function variantsUpdated(array $variants)
    {
        $this->variants = collect($variants);
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required',
            'description' => 'required',
            'features' => 'required',
            'category_id' => 'required',
            'brand_id' => 'required',
            'dimension' => 'required',
            'weight' => 'required',
            'meta_description' => 'nullable',
            'meta_keyword' => 'nullable',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_new' => 'boolean',
            'image1' => 'nullable|image|mimes:jpg,png,webp,svg|max:1024',
            'image2' => 'nullable|image|mimes:jpg,png,webp,svg|max:1024',
            'image3' => 'nullable|image|mimes:jpg,png,webp,svg|max:1024',
            'image4' => 'nullable|image|mimes:jpg,png,webp,svg|max:1024',
            'image5' => 'nullable|image|mimes:jpg,png,webp,svg|max:1024',
            'variants' => 'required|array|min:1',
        ]);

        unset($data['image1']);
        unset($data['image2']);
        unset($data['image3']);
        unset($data['image4']);
        unset($data['image5']);
        unset($data['variants']);

        if ($this->image1) {
            $url = $this->image1->store('products/'.$this->product->id, 'public');
            $data['image1'] =  "/storage/" . $url;
        }
        if ($this->image2) {
            $url = $this->image2->store('products/'.$this->product->id, 'public');
            $data['image2'] =  "/storage/" . $url;
        }
        if ($this->image3) {
            $url = $this->image3->store('products/'.$this->product->id, 'public');
            $data['image3'] =  "/storage/" . $url;
        }
        if ($this->image4) {
            $url = $this->image4->store('products/'.$this->product->id, 'public');
            $data['image4'] =  "/storage/" . $url;
        }
        if ($this->image5) {
            $url = $this->image5->store('products/'.$this->product->id, 'public');
            $data['image5'] =  "/storage/" . $url;
        }

        $this->product->update($data);

        $this->product->variants()->delete();

        $this->variants->each(function ($item, $key) {
            $this->product->variants()->create([
                'sku' => $item['sku'],
                'name' => $item['name'],
                'color_top' => $item['color_top'],
                'color_bottom' => $item['color_bottom'],
                'in_stock' => $item['in_stock'],
                'price' => $item['price'],
                'slash_price' => $item['slash_price'],
                'marketplace_url' => $item['marketplace_url'],
                'image' => $item['image'],
            ]);
        });

        $this->success('Product successfully updated.', redirectTo: route('product.index'));
    }

    public function generateSlug()
    {
        $slug = SlugService::createSlug(Product::class, 'slug', 'My First Post');
        $this->product->update([
            'slug' => $slug
        ]);

        $this->slug = $slug;
    }
}; ?>
@php
$config = [
    'spellChecker' => false,
    'uploadImage' => false,
    'status' => false,
    'minHeight' => '100px',
    'toolbar' => [
        'heading', 'bold', 'italic', 'strikethrough',
        '|', 'quote', 'unordered-list', 'ordered-list', 'horizontal-rule',
        '|', 'link', 'table', 'clean-block'
    ],
];
@endphp
<div>
    <x-header title="Update Product" separator>
        <x-slot:actions>
            <x-button label="Back" link="{{ route('product.index') }}" icon="o-arrow-uturn-left" />
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="save">
        <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-12 gap-4">
            <div class="col-span-8 space-y-4">
                <x-card>
                    <div class="space-y-4">
                        <x-input label="Name" wire:model="name" />
                        <x-markdown label="Description" wire:model="description" :config="$config" />
                        <x-markdown label="Features" wire:model="features" :config="$config" />
                    </div>
                </x-card>

                <x-card title="Images">
                    <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-5 gap-2">
                        <x-file wire:model="image1" accept="image/png, image/jpeg, image/svg+xml, image/webp" hint="Main image">
                            <img src="{{ $storedImage1 ? $storedImage1 : asset('assets/img/image-placeholder.svg') }}" class="w-20 h-20 rounded-lg object-cover object-center" />
                        </x-file>

                        <x-file wire:model="image2" accept="image/png, image/jpeg, image/svg+xml, image/webp">
                            <img src="{{ $storedImage2 ? $storedImage2 : asset('assets/img/image-placeholder.svg') }}" class="w-20 h-20 rounded-lg object-cover object-center" />
                        </x-file>

                        <x-file wire:model="image3" accept="image/png, image/jpeg, image/svg+xml, image/webp">
                            <img src="{{ $storedImage3 ? $storedImage3 : asset('assets/img/image-placeholder.svg') }}" class="w-20 h-20 rounded-lg object-cover object-center" />
                        </x-file>

                        <x-file wire:model="image4" accept="image/png, image/jpeg, image/svg+xml, image/webp">
                            <img src="{{ $storedImage4 ? $storedImage4 : asset('assets/img/image-placeholder.svg') }}" class="w-20 h-20 rounded-lg object-cover object-center" />
                        </x-file>

                        <x-file wire:model="image5" accept="image/png, image/jpeg, image/svg+xml, image/webp">
                            <img src="{{ $storedImage5 ? $storedImage5 : asset('assets/img/image-placeholder.svg') }}" class="w-20 h-20 rounded-lg object-cover object-center" />
                        </x-file>
                    </div>
                </x-card>

                <div class="overflow-x-auto space-y-4">
                    @error('variants')
                    <x-alert title="{{ $message }}" icon="o-exclamation-triangle" class="alert-error alert-soft" />
                    @enderror
                    <livewire:product.variants :id="$product->id ?? 0" />
                </div>

                <x-card title="SEO">
                    <div class="space-y-4">
                        <x-input label="Slug" wire:model="slug" readonly>
                            {{-- <x-slot:append>
                                <x-button label="Regenerate" wire:click="generateSlug" spinner="generateSlug" class="join-item btn-primary btn-soft" icon="o-arrow-path" />
                            </x-slot:append> --}}
                        </x-input>
                        <x-textarea rows="3" label="Meta Description" wire:model="meta_description" />
                        <x-textarea rows="3" label="Meta Keyword" wire:model="meta_keyword" />
                    </div>
                </x-card>
            </div>

            <div class="col-span-4">
                <div class="lg:sticky lg:top-[74px] space-y-4">
                    <x-card>
                        <div class="space-y-4">
                            <x-choices-offline label="Category" wire:model="category_id" :options="$category" option-label="name" single searchable />
                            <x-choices-offline label="Brand" wire:model="brand_id" :options="$brand" option-label="name" single searchable />
                            <x-input label="Dimension" wire:model="dimension" placeholder="L x W x H" />
                            <x-input label="Weight" wire:model="weight" suffix="Kgs" x-mask:dynamic="$money($input,'.','')" />
                            <x-toggle label="Active" wire:model="is_active" hint="Show product on website" />
                            <x-toggle label="Featured Product" wire:model="is_featured" hint="Show product on listed product features" />
                            <x-toggle label="New Product" wire:model="is_new" hint="Mark as new product" />
                        </div>
                    </x-card>
                    <x-hr />
                    <div class="flex justify-end gap-3">
                        <x-button label="Back" link="{{ route('product.index') }}" />
                        <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
                    </div>
                </div>
            </div>
        </div>

        {{-- <x-slot:actions>
            <x-button label="Cancel" link="{{ route('product.index') }}" />
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions> --}}
    </x-form>
</div>
