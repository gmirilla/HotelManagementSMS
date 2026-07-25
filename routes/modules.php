<?php

declare(strict_types=1);

use App\Livewire\Accounting\ArApManager;
use App\Livewire\Accounting\Cashbook;
use App\Livewire\Accounting\ChartOfAccounts;
use App\Livewire\Accounting\FinancialReports;
use App\Livewire\Accounting\JournalEntryManager;
use App\Livewire\Accounting\TaxRuleManager;
use App\Livewire\Admin\AppearanceSettings;
use App\Livewire\Admin\BranchManager;
use App\Livewire\Admin\HotelSettings;
use App\Livewire\Admin\PaymentSettings;
use App\Livewire\Admin\UserManager;
use App\Livewire\CRM\CorporateAccountManager;
use App\Livewire\CRM\CouponManager;
use App\Livewire\CRM\FeedbackManager;
use App\Livewire\CRM\LoyaltyManager;
use App\Livewire\CRM\MarketingCampaignManager;
use App\Livewire\Events\EventBookingManager;
use App\Livewire\Events\EventSpaceManager;
use App\Livewire\FrontDesk\Dashboard as FrontDeskDashboard;
use App\Livewire\FrontDesk\FolioShow;
use App\Livewire\Guests\GuestManager;
use App\Livewire\Guests\GuestProfile;
use App\Livewire\Housekeeping\TaskBoard;
use App\Livewire\HR\AttendanceBoard;
use App\Livewire\HR\DisciplinaryRecordManager;
use App\Livewire\HR\EmployeeManager;
use App\Livewire\HR\LeaveManager;
use App\Livewire\HR\PayrollManager;
use App\Livewire\HR\PerformanceReviewManager;
use App\Livewire\HR\RecruitmentBoard;
use App\Livewire\Inventory\ItemManager;
use App\Livewire\Maintenance\WorkOrderManager;
use App\Livewire\Procurement\PurchaseOrderManager;
use App\Livewire\Reservations\BookingWizard;
use App\Livewire\Reservations\ReservationManager;
use App\Livewire\Reservations\ReservationShow;
use App\Livewire\Restaurant\KitchenDisplay;
use App\Livewire\Restaurant\MenuManager;
use App\Livewire\Restaurant\PosTerminal;
use App\Livewire\Rooms\RoomManager;
use App\Livewire\Rooms\RoomTypeManager;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('room-types', RoomTypeManager::class)->name('room-types.index');
    Route::get('rooms', RoomManager::class)->name('rooms.index');

    Route::get('guests', GuestManager::class)->name('guests.index');
    Route::get('guests/{guest}', GuestProfile::class)->name('guests.show');

    Route::get('reservations', ReservationManager::class)->name('reservations.index');
    Route::get('reservations/create', BookingWizard::class)->name('reservations.create');
    Route::get('reservations/{reservation}', ReservationShow::class)->name('reservations.show');

    Route::get('front-desk', FrontDeskDashboard::class)->name('front-desk.index');

    Route::get('folios/{folio}', FolioShow::class)->name('folios.show');

    Route::get('housekeeping', TaskBoard::class)->name('housekeeping.index');
    Route::get('maintenance', WorkOrderManager::class)->name('maintenance.index');

    Route::get('inventory', ItemManager::class)->name('inventory.index');
    Route::get('purchase-orders', PurchaseOrderManager::class)->name('purchase-orders.index');

    Route::get('restaurant/menu', MenuManager::class)->name('restaurant.menu');
    Route::get('restaurant/pos', PosTerminal::class)->name('restaurant.pos');
    Route::get('restaurant/kitchen', KitchenDisplay::class)->name('restaurant.kitchen');

    Route::get('accounting/chart-of-accounts', ChartOfAccounts::class)->name('accounting.chart-of-accounts');
    Route::get('accounting/journal-entries', JournalEntryManager::class)->name('accounting.journal-entries');
    Route::get('accounting/reports', FinancialReports::class)->name('accounting.reports');
    Route::get('accounting/cashbook', Cashbook::class)->name('accounting.cashbook');
    Route::get('accounting/ar-ap', ArApManager::class)->name('accounting.ar-ap');
    Route::get('accounting/tax-rules', TaxRuleManager::class)->name('accounting.tax-rules');

    Route::get('hr/employees', EmployeeManager::class)->name('hr.employees');
    Route::get('hr/attendance', AttendanceBoard::class)->name('hr.attendance');
    Route::get('hr/leave', LeaveManager::class)->name('hr.leave');
    Route::get('hr/payroll', PayrollManager::class)->name('hr.payroll');
    Route::get('hr/performance-reviews', PerformanceReviewManager::class)->name('hr.performance-reviews');
    Route::get('hr/disciplinary-records', DisciplinaryRecordManager::class)->name('hr.disciplinary-records');
    Route::get('hr/recruitment', RecruitmentBoard::class)->name('hr.recruitment');

    Route::get('crm/corporate-accounts', CorporateAccountManager::class)->name('crm.corporate-accounts');
    Route::get('crm/feedback', FeedbackManager::class)->name('crm.feedback');
    Route::get('crm/loyalty', LoyaltyManager::class)->name('crm.loyalty');
    Route::get('crm/coupons', CouponManager::class)->name('crm.coupons');
    Route::get('crm/campaigns', MarketingCampaignManager::class)->name('crm.campaigns');

    Route::get('events/spaces', EventSpaceManager::class)->name('events.spaces');
    Route::get('events/bookings', EventBookingManager::class)->name('events.bookings');

    Route::get('admin/users', UserManager::class)->name('admin.users');
    Route::get('admin/appearance', AppearanceSettings::class)->name('admin.appearance');
    Route::get('admin/hotel-settings', HotelSettings::class)->name('admin.hotel-settings');
    Route::get('admin/payment-settings', PaymentSettings::class)->name('admin.payment-settings');
    Route::get('admin/branches', BranchManager::class)->name('admin.branches');
});
