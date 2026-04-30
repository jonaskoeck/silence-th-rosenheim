<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;


Route::redirect('/', '/dashboard');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/servers',   fn() => 'Server')->name('servers');
Route::get('/schedules', fn() => 'Zeitplaene')->name('schedules');

Route::get('/login',  fn() => view('login'))->name('login');
Route::get('/logout', fn() => redirect()->route('login'))->name('logout');

