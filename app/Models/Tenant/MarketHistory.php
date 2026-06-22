<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketHistory extends Model
{
    protected $table = 'market_history';

    protected $fillable = [
        'customer_id',
        'device_id',
        'catalog_item_id',
        'subject_name',
        'unit_price',
        'total_amount',
        'transaction_date',
        'transaction_time',
        'created_by',
        'shift_no',
        'ledger_entry_id',
        'quantity',
        'invoice_no',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'device_id' => 'integer',
        'catalog_item_id' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'transaction_date' => 'date',
        'shift_no' => 'integer',
        'ledger_entry_id' => 'integer',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Devices::class, 'device_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
