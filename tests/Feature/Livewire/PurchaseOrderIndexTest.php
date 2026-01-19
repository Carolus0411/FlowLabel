<?php

namespace Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\PurchaseOrder;
use Illuminate\Support\Carbon;
use App\Models\Supplier;

class PurchaseOrderIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_page_shows_received_label_and_essential_columns()
    {
        $supplier = Supplier::create([
            'code' => 'SUP-PO-001',
            'name' => 'Supplier PO Test',
        ]);

        $order = PurchaseOrder::create([
            'code' => 'PO-100',
            'order_date' => Carbon::now(),
            'due_date' => Carbon::now()->addDays(7),
            'top' => 7,
            'status' => 'open',
            'supplier_id' => $supplier->id,
            'saved' => 1,
        ]);

        $response = $this->get(route('purchase-order.index'));
        $response->assertStatus(200);
        $response->assertSee('Received');
        $response->assertSee('Order Date');
        $response->assertSee('Supplier');
    }
}

