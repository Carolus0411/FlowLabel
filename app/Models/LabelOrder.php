<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabelOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'original_filename',
        'split_filename',
        'page_number',
        'extracted_data',
        'order_type',
        'status',
        'raw_text',
        'file_path',
        'created_by',
    ];

    protected $attributes = [
        'order_type' => 'general',
        'status' => 'processed',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeByOrderType($query, $orderType)
    {
        return $query->where('order_type', $orderType);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByOrderId($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function getFileUrlAttribute()
    {
        return route('label-order.download', ['path' => urlencode($this->file_path)]);
    }

    public function extractOrderData($text)
    {
        $data = [];

        // Extract common patterns from order documents
        // Order ID patterns
        if (preg_match('/(?:Order|SO|PO|DO)\s*(?:ID|Number|#)?\s*:?\s*([A-Z0-9\-]+)/i', $text, $matches)) {
            $data['order_number'] = $matches[1];
        }

        // Date patterns
        if (preg_match('/(\d{1,2}[-\/]\d{1,2}[-\/]\d{4}|\d{4}[-\/]\d{1,2}[-\/]\d{1,2})/', $text, $matches)) {
            $data['date'] = $matches[1];
        }

        // Customer/Supplier patterns
        if (preg_match('/(?:Customer|Supplier|Contact)\s*:?\s*([^\n\r]+)/i', $text, $matches)) {
            $data['customer_supplier'] = trim($matches[1]);
        }

        // Amount patterns
        if (preg_match('/(?:Total|Amount|Price)\s*:?\s*[\$]?([0-9,]+\.?\d*)/i', $text, $matches)) {
            $data['total_amount'] = $matches[1];
        }

        // Product/Item patterns (extract multiple items if present)
        $items = [];
        if (preg_match_all('/(?:Item|Product|Code)\s*:?\s*([^\n\r]+)/i', $text, $matches)) {
            $items = array_map('trim', $matches[1]);
        }
        if (!empty($items)) {
            $data['items'] = $items;
        }

        // Quantity patterns
        if (preg_match_all('/(?:Qty|Quantity)\s*:?\s*(\d+)/i', $text, $quantities)) {
            $data['quantities'] = $quantities[1];
        }

        return $data;
    }
}
