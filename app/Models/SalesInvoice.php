<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;
use Illuminate\Database\Query\Expression;

class SalesInvoice extends Model
{
    use Filterable;

    protected $table = 'sales_invoice';
    protected $guarded = [];

    #[Scope]
    protected function stored(Builder $query): void
    {
        $query->where('saved', 1);
    }

    #[Scope]
    protected function draft(Builder $query): void
    {
        $query->where('saved', 0);
    }

    #[Scope]
    protected function closed(Builder $query): void
    {
        $query->where('status', 'close');
    }

    #[Scope]
    protected function unpaid(Builder $query): void
    {
        $query->whereRaw('(balance_amount = invoice_amount)');
    }

    #[Scope]
    protected function outstanding(Builder $query): void
    {
        $query->whereRaw('((balance_amount > 0) AND (balance_amount < invoice_amount))');
        // $query->where(function (Builder $q) {
        //     $q->where('balance_amount', '=', '0');
        //     $q->orWhereRaw('((balance_amount > 0) AND (balance_amount < invoice_amount))');
        // });
    }

    #[Scope]
    protected function paid(Builder $query): void
    {
        $query->where('balance_amount', '=', '0');
    }

    public function details(): HasMany
	{
		return $this->hasMany(SalesInvoiceDetail::class,'sales_invoice_id','id');
	}

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class,'contact_id','id')->withDefault();
    }

    public function ppn(): BelongsTo
    {
        return $this->belongsTo(Ppn::class,'ppn_id','id')->withDefault();
    }

    public function pph(): BelongsTo
    {
        return $this->belongsTo(Pph::class,'pph_id','id')->withDefault();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class,'created_by','id')->withDefault();
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class,'updated_by','id')->withDefault();
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class,'closed_by','id')->withDefault();
    }
    public function logs(): HasMany
	{
		return $this->hasMany(UserLog::class,'ref_id','code')->where('resource', 'SalesInvoice');
	}

    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            $model->created_by = auth()->user()->id;
            $model->updated_by = auth()->user()->id;

            // Determine initial payment status if any settlement details are present
            $payment_status = 'unpaid';
            $balance = $model->balance_amount;
            if ($balance instanceof Expression) {
                $balance = $model->getOriginal('balance_amount') ?? 0;
            }
            if ($balance == 0) {
                $payment_status = 'paid';
            } else {
                // if any settlement exists for this invoice, mark as outstanding
                if ($model->settlementDetails()->exists()) {
                    $payment_status = 'outstanding';
                }
            }
            $model->payment_status = $payment_status;
        });

        static::updating(function (Model $model) {

            $payment_status = 'unpaid';

            // Paid if balance is zero
            $balance = $model->balance_amount;
            if ($balance instanceof Expression) {
                $balance = $model->getOriginal('balance_amount') ?? 0;
            }
            if ($balance == 0) {
                $payment_status = 'paid';
            } else {
                // If there are any settlements applied (partial payments), mark outstanding
                if ($model->settlementDetails()->exists()) {
                    $payment_status = 'outstanding';
                } else {
                    $payment_status = 'unpaid';
                }
            }

            $model->payment_status = $payment_status;
            $model->updated_by = auth()->user()->id;
        });

        static::updated(function (Model $model) {
            auth()->user()->logs()->create([
                'resource' => class_basename($model),
                'action' => $model->isDirty('code') ? 'create' : 'update',
                'ref_id' => $model->code,
                'data' => json_encode($model)
            ]);
        });

        static::deleted(function (Model $model) {
            auth()->user()->logs()->create([
                'resource' => class_basename($model),
                'action' => 'delete',
                'ref_id' => $model->code,
                'data' => json_encode($model)
            ]);
        });
    }

    // Sales Settlement details connected to this invoice via sales_invoice_code => code
    public function settlementDetails(): HasMany
    {
        return $this->hasMany(\App\Models\SalesSettlementDetail::class, 'sales_invoice_code', 'code');
    }

    public function recalcPaymentStatus(): void
    {
        // if balance_amount holds a DB expression (due to DB::raw update on model), refresh
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
            // update without triggering infinite loops by directly updating the DB
            $this->update(['payment_status' => $payment_status]);
        }
    }
}
