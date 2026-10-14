<?php

namespace App\Models\Tenant;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'branch_id',
        'invoice_no',
        'invoice_type',
        'device_id',
        'customer_id',
        'shift_id',
        'cash_box_id',
        'created_by_user_id',
        'play_amount',
        'order_amount',
        'subtotal',
        'discount_amount',
        'discount_reason',
        'service_amount',
        'tax_amount',
        'deposit_amount',
        'final_amount',
        'paid_amount',
        'remain_amount',
        'status',
        'issued_at',
    ];

    protected $casts = [
        'cash_box_id' => 'integer',
        'play_amount' => 'decimal:2',
        'order_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'service_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remain_amount' => 'decimal:2',
        'issued_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Devices::class, 'device_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function appointment(): HasOne
    {
        return $this->hasOne(Appointment::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function cashBox(): BelongsTo
    {
        return $this->belongsTo(CashBox::class, 'cash_box_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function marketHistories(): HasMany
    {
        return $this->hasMany(MarketHistory::class, 'invoice_no', 'invoice_no');
    }

    public function taxHistories(): HasMany
    {
        return $this->hasMany(TaxHistory::class, 'invoice_id');
    }

    public function serviceHistories(): HasMany
    {
        return $this->hasMany(ServiceHistory::class, 'invoice_id');
    }

    public function discountHistories(): HasMany
    {
        return $this->hasMany(DiscountHistory::class, 'invoice_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class, 'invoice_id');
    }

    public function playSessionDetails(): HasMany
    {
        return $this->hasMany(PlayHistory::class, 'invoice_no', 'invoice_no');
    }

    public function orderSessionDetails(): HasMany
    {
        return $this->hasMany(MarketHistory::class, 'invoice_no', 'invoice_no');
    }
}
