<?php

declare(strict_types=1);

namespace App\Livewire\CRM;

use App\Domain\CRM\Enums\CouponDiscountType;
use App\Domain\CRM\Enums\CouponScope;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Coupons & Promotions')]
class CouponManager extends Component
{
    use InteractsWithActiveBranch;

    public bool $showForm = false;

    public string $code = '';

    public string $name = '';

    public string $discountType = 'percent';

    public string $discountValue = '';

    public string $scope = 'all';

    public string $validFrom = '';

    public string $validUntil = '';

    public string $usageLimit = '';

    protected function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'discountType' => ['required', 'string'],
            'discountValue' => ['required', 'integer', 'min:1'],
            'scope' => ['required', 'string'],
            'validFrom' => ['required', 'date'],
            'validUntil' => ['required', 'date', 'after_or_equal:validFrom'],
            'usageLimit' => ['nullable', 'integer', 'min:1'],
        ];
    }

    #[Computed]
    public function coupons(): Collection
    {
        return Coupon::where('branch_id', $this->branchId)->orderByDesc('created_at')->get();
    }

    public function create(): void
    {
        $this->authorize('create', Coupon::class);

        $this->reset(['code', 'name', 'discountValue', 'usageLimit']);
        $this->discountType = 'percent';
        $this->scope = 'all';
        $this->validFrom = now()->toDateString();
        $this->validUntil = now()->addMonth()->toDateString();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('create', Coupon::class);
        $this->validate();

        Coupon::create([
            'branch_id' => $this->branchId,
            'code' => mb_strtoupper($this->code),
            'name' => $this->name,
            'discount_type' => $this->discountType,
            'discount_value' => $this->discountValue,
            'scope' => $this->scope,
            'valid_from' => $this->validFrom,
            'valid_until' => $this->validUntil,
            'usage_limit' => $this->usageLimit ?: null,
        ]);

        $this->showForm = false;
        unset($this->coupons);
    }

    public function toggleActive(int $couponId): void
    {
        $coupon = Coupon::findOrFail($couponId);
        $this->authorize('update', $coupon);

        $coupon->update(['is_active' => ! $coupon->is_active]);
        unset($this->coupons);
    }

    public function render()
    {
        return view('livewire.crm.coupon-manager', [
            'discountTypes' => CouponDiscountType::cases(),
            'scopes' => CouponScope::cases(),
        ]);
    }
}
