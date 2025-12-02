<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;

class SalesSettlementDetail extends Model
{
    use Filterable;

    protected $table = 'sales_settlement_detail';
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            $model->status = $model->status ?? $model->salesSettlement->status;
        });

        static::updating(function (Model $model) {
            $model->status = $model->status ?? $model->salesSettlement->status;
        });

        // after creating/updating/deleting details, recalc invoice payment status
        static::created(function (Model $model) {
            if ($invoice = \App\Models\SalesInvoice::where('code', $model->sales_invoice_code)->first()) {
                $invoice->recalcPaymentStatus();
            }
        });

        static::updated(function (Model $model) {
            if ($invoice = \App\Models\SalesInvoice::where('code', $model->sales_invoice_code)->first()) {
                $invoice->recalcPaymentStatus();
            }
        });

        static::deleted(function (Model $model) {
            if ($invoice = \App\Models\SalesInvoice::where('code', $model->sales_invoice_code)->first()) {
                $invoice->recalcPaymentStatus();
            }
        });
    }

    public function salesSettlement(): BelongsTo
    {
        return $this->belongsTo(SalesSettlement::class,'sales_settlement_code','code')->withDefault();
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class,'sales_invoice_code','code')->withDefault();
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class,'currency_id','id')->withDefault();
    }
}
