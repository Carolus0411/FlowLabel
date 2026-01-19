<x-header title="Label Order Details" separator progress-indicator>
    <x-slot:subtitle>
        Order ID: {{ $labelOrder->order_id }} | Page {{ $labelOrder->page_number }}
    </x-slot:subtitle>
    <x-slot:actions>
        <a href="{{ $labelOrder->file_url }}" class="btn btn-primary">
            <x-icon name="o-arrow-down-tray" class="w-4 h-4" />
            Download PDF
        </a>
        <a href="{{ route('label-order.index') }}" class="btn btn-outline">
            <x-icon name="o-arrow-left" class="w-4 h-4" />
            Back to List
        </a>
    </x-slot:actions>
</x-header>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
    <!-- Order Information -->
    <x-card title="Order Information">
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label">Order ID</label>
                    <x-badge value="{{ $labelOrder->order_id }}" class="badge-primary" />
                </div>
                <div>
                    <label class="label">Order Type</label>
                    <x-badge value="{{ ucwords(str_replace('_', ' ', $labelOrder->order_type)) }}" class="badge-info" />
                </div>
                <div>
                    <label class="label">Page Number</label>
                    <span class="text-lg font-semibold">{{ $labelOrder->page_number }}</span>
                </div>
                <div>
                    <label class="label">Status</label>
                    <x-badge
                        :value="$labelOrder->status"
                        :class="$labelOrder->status === 'processed' ? 'badge-success' : 'badge-error'"
                    />
                </div>
                <div>
                    <label class="label">Original File</label>
                    <span class="text-sm">{{ $labelOrder->original_filename }}</span>
                </div>
                <div>
                    <label class="label">Split File</label>
                    <span class="text-sm">{{ $labelOrder->split_filename }}</span>
                </div>
            </div>
            <div>
                <label class="label">Created By</label>
                <span>{{ $labelOrder->creator->name ?? 'Unknown' }}</span>
            </div>
            <div>
                <label class="label">Created At</label>
                <span>{{ $labelOrder->created_at->format('d-M-Y H:i:s') }}</span>
            </div>
        </div>
    </x-card>

    <!-- Extracted Data -->
    <x-card title="Extracted Data">
        @if($labelOrder->extracted_data && count($labelOrder->extracted_data) > 0)
            <div class="space-y-4">
                @if(isset($labelOrder->extracted_data['order_number']))
                    <div>
                        <label class="label">Order Number</label>
                        <x-badge value="{{ $labelOrder->extracted_data['order_number'] }}" class="badge-primary" />
                    </div>
                @endif

                @if(isset($labelOrder->extracted_data['date']))
                    <div>
                        <label class="label">Date</label>
                        <span>{{ $labelOrder->extracted_data['date'] }}</span>
                    </div>
                @endif

                @if(isset($labelOrder->extracted_data['customer_supplier']))
                    <div>
                        <label class="label">Customer/Supplier</label>
                        <span>{{ $labelOrder->extracted_data['customer_supplier'] }}</span>
                    </div>
                @endif

                @if(isset($labelOrder->extracted_data['total_amount']))
                    <div>
                        <label class="label">Total Amount</label>
                        <span class="text-lg font-semibold text-green-600">{{ $labelOrder->extracted_data['total_amount'] }}</span>
                    </div>
                @endif

                @if(isset($labelOrder->extracted_data['items']) && count($labelOrder->extracted_data['items']) > 0)
                    <div>
                        <label class="label">Items</label>
                        <div class="space-y-2">
                            @foreach($labelOrder->extracted_data['items'] as $index => $item)
                                <div class="p-2 bg-gray-50 rounded">
                                    <div class="font-medium">{{ $item }}</div>
                                    @if(isset($labelOrder->extracted_data['quantities'][$index]))
                                        <div class="text-sm text-gray-600">Qty: {{ $labelOrder->extracted_data['quantities'][$index] }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="text-gray-500 text-center py-8">
                <x-icon name="o-document-text" class="w-12 h-12 mx-auto mb-4 opacity-50" />
                <p>No data was extracted from this PDF</p>
            </div>
        @endif
    </x-card>
</div>

<!-- Raw Text -->
@if($labelOrder->raw_text)
    <x-card title="Raw Extracted Text" class="mt-6" collapsed>
        <div class="bg-gray-50 p-4 rounded-lg">
            <pre class="text-xs whitespace-pre-wrap font-mono">{{ $labelOrder->raw_text }}</pre>
        </div>
    </x-card>
@endif
