<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-header title="Print Label Details" separator />

            <x-card>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Basic Information</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm font-medium text-gray-600">Code</label>
                                <p class="text-gray-900">{{ $orderLabel->code }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600">Order Date</label>
                                <p class="text-gray-900">{{ $orderLabel->order_date ? $orderLabel->order_date->format('d-m-Y') : '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600">Status</label>
                                <p><x-status-badge :status="$orderLabel->status" /></p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold mb-4">PDF Information</h3>
                        <div class="space-y-3">
                            @if($orderLabel->original_filename)
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Original File</label>
                                    <p class="text-gray-900">{{ $orderLabel->original_filename }}</p>
                                </div>
                            @endif

                            @if($orderLabel->split_filename)
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Split File</label>
                                    <p class="text-gray-900">{{ $orderLabel->split_filename }}</p>
                                </div>
                            @endif

                            @if($orderLabel->page_number)
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Page Number</label>
                                    <p class="text-gray-900">{{ $orderLabel->page_number }}</p>
                                </div>
                            @endif

                            @if($orderLabel->file_path)
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Download</label>
                                    <p>
                                        <a href="{{ route('print-label.download', ['path' => urlencode($orderLabel->file_path)]) }}"
                                           class="btn btn-primary btn-sm">
                                            <x-icon name="o-arrow-down-tray" class="w-4 h-4 mr-1" />
                                            Download PDF
                                        </a>
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                @if($orderLabel->extracted_text)
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold mb-4">Extracted Text Content</h3>
                        <div class="bg-gray-50 p-4 rounded-lg max-h-96 overflow-y-auto">
                            <pre class="text-sm text-gray-700 whitespace-pre-wrap">{{ $orderLabel->extracted_text }}</pre>
                        </div>
                    </div>
                @endif

                @if($orderLabel->note)
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold mb-4">Notes</h3>
                        <p class="text-gray-700">{{ $orderLabel->note }}</p>
                    </div>
                @endif

                <x-slot:actions>
                    <x-button label="Back to List" link="{{ route('print-label.index') }}" icon="o-arrow-left" />
                    @if($orderLabel->file_path)
                        <a href="{{ route('print-label.download', ['path' => urlencode($orderLabel->file_path)]) }}"
                           class="btn btn-primary">
                            <x-icon name="o-arrow-down-tray" class="w-4 h-4 mr-1" />
                            Download PDF
                        </a>
                    @endif
                </x-slot:actions>
            </x-card>
        </div>
    </div>
</x-app-layout>
