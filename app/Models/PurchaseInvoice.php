<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\Filterable;
use Illuminate\Database\Query\Expression;

class PurchaseInvoice extends Model
{
    use Filterable;

    protected $table = 'purchase_invoice';
    protected $guarded = [];

    #[Scope]
    protected function stored(Builder $query): void
    {
        $query->where('saved', 1);
    }

    #[Scope]
    protected function closed(Builder $query): void
    {
        $query->where('status', 'close');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceDetail::class, 'purchase_invoice_id', 'id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id')->withDefault();
    }

    // Purchase Settlement details connected to this invoice via purchase_invoice_code => code
    public function settlementDetails(): HasMany
    {
        return $this->hasMany(\App\Models\PurchaseSettlementDetail::class, 'purchase_invoice_code', 'code');
    }

    public function recalcPaymentStatus(): void
    {
        // make sure balance_amount is a concrete numeric value, not a DB raw expression
        if ($this->balance_amount instanceof Expression) {
            $this->refresh();
        }
        $payment_status = 'unpaid';

        if ($this->balance_amount == 0) {
            $payment_status = 'paid';
        } else {
            if ($this->settlementDetails()->exists()) {
                $payment_status = 'outstanding';
            } else {
                $payment_status = 'unpaid';
            }
        }

        if ($this->payment_status !== $payment_status) {
            $this->update(['payment_status' => $payment_status]);
        }
    }
}
