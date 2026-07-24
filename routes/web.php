<?php

declare(strict_types=1);

use App\Livewire\Reporting\DashboardOverview;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

Route::get('/dashboard', DashboardOverview::class)->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__ . '/auth.php';
require __DIR__ . '/modules.php';
