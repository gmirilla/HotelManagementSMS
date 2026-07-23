<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

#[Fillable([
    'tenant_id', 'name', 'code', 'currency', 'timezone', 'address_line1',
    'city', 'country', 'check_in_time', 'check_out_time', 'cancellation_policy', 'is_active',
])]
class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use HasFactory, SoftDeletes;

    #[Override]
    protected function casts(): array
    {
        return [
            'cancellation_policy' => 'array',
            'is_active' => 'boolean',
            'check_in_time' => 'datetime:H:i:s',
            'check_out_time' => 'datetime:H:i:s',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'branch_user')
            ->withPivot(['role_id', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<RoomType, $this>
     */
    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class);
    }

    /**
     * @return HasMany<Room, $this>
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * @return HasMany<Folio, $this>
     */
    public function folios(): HasMany
    {
        return $this->hasMany(Folio::class);
    }

    /**
     * @return HasMany<HousekeepingTask, $this>
     */
    public function housekeepingTasks(): HasMany
    {
        return $this->hasMany(HousekeepingTask::class);
    }

    /**
     * @return HasMany<LostFoundItem, $this>
     */
    public function lostFoundItems(): HasMany
    {
        return $this->hasMany(LostFoundItem::class);
    }

    /**
     * @return HasMany<Asset, $this>
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * @return HasMany<MaintenanceWorkOrder, $this>
     */
    public function maintenanceWorkOrders(): HasMany
    {
        return $this->hasMany(MaintenanceWorkOrder::class);
    }

    /**
     * @return HasMany<Warehouse, $this>
     */
    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    /**
     * @return HasMany<PurchaseOrder, $this>
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * @return HasMany<RestaurantOutlet, $this>
     */
    public function restaurantOutlets(): HasMany
    {
        return $this->hasMany(RestaurantOutlet::class);
    }

    /**
     * @return HasMany<Account, $this>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * @return HasMany<JournalEntry, $this>
     */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    /**
     * @return HasMany<ArEntry, $this>
     */
    public function arEntries(): HasMany
    {
        return $this->hasMany(ArEntry::class);
    }

    /**
     * @return HasMany<ApEntry, $this>
     */
    public function apEntries(): HasMany
    {
        return $this->hasMany(ApEntry::class);
    }

    /**
     * @return HasMany<CashbookEntry, $this>
     */
    public function cashbookEntries(): HasMany
    {
        return $this->hasMany(CashbookEntry::class);
    }

    /**
     * @return HasMany<TaxRule, $this>
     */
    public function taxRules(): HasMany
    {
        return $this->hasMany(TaxRule::class);
    }

    /**
     * @return HasMany<Employee, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * @return HasMany<LeaveType, $this>
     */
    public function leaveTypes(): HasMany
    {
        return $this->hasMany(LeaveType::class);
    }

    /**
     * @return HasMany<AttendanceRecord, $this>
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * @return HasMany<PayrollRun, $this>
     */
    public function payrollRuns(): HasMany
    {
        return $this->hasMany(PayrollRun::class);
    }

    /**
     * @return HasMany<JobOpening, $this>
     */
    public function jobOpenings(): HasMany
    {
        return $this->hasMany(JobOpening::class);
    }
}
