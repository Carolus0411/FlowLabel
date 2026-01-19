<?php

namespace Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\PurchaseInvoice;
use Illuminate\Support\Carbon;
use App\Models\Supplier;

class PurchaseInvoiceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_page_shows_detail_component_and_supplier_field()
    {
        $supplier = Supplier::create([
            'code' => 'SUP-001',
            'name' => 'Supplier A',
        ]);

        $invoice = PurchaseInvoice::create([
            'code' => 'PI-100',
            'invoice_date' => Carbon::now(),
            'due_date' => Carbon::now()->addDays(30),
            'top' => 30,
            'status' => 'open',
            'supplier_id' => $supplier->id,
        ]);

        $response = $this->get(route('purchase-invoice.edit', ['purchaseInvoice' => $invoice->id]));
        $response->assertStatus(200);
        $response->assertSee('Items Master');
        $response->assertSee('Supplier');
    }
}
