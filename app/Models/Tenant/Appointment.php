<?php

namespace App\Models\Tenant;

use App\Scopes\BranchScope;
use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    use BelongsToBranch;

    public const array RESERVED_STATUSES = ['requested', 'scheduled', 'confirmed', 'in_progress'];

    protected $fillable = [
        'branch_id',
        'staff_member_id',
        'customer_id',
        'contact_name',
        'contact_phone',
        'public_booking_token_hash',
        'reminder_sent_at',
        'created_by_user_id',
        'invoice_id',
        'start_at',
        'end_at',
        'duration_minutes',
        'total_price',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'duration_minutes' => 'integer',
            'total_price' => 'decimal:2',
            'reminder_sent_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<StaffMember, $this> */
    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return HasMany<AppointmentService, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(AppointmentService::class);
    }

    public static function hasConflict(
        int $branchId,
        int $staffMemberId,
        string $startAt,
        string $endAt,
        ?int $excludeId = null,
    ): bool {
        return static::withoutGlobalScope(BranchScope::class)
            ->where('branch_id', $branchId)
            ->where('staff_member_id', $staffMemberId)
            ->whereIn('status', self::RESERVED_STATUSES)
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->exists();
    }
}
