<?php

declare(strict_types=1);

use App\Livewire\FrontDesk\Dashboard as FrontDeskDashboard;
use App\Livewire\FrontDesk\FolioShow;
use App\Livewire\Guests\GuestManager;
use App\Livewire\Guests\GuestProfile;
use App\Livewire\Reservations\BookingWizard;
use App\Livewire\Reservations\ReservationManager;
use App\Livewire\Reservations\ReservationShow;
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
});
