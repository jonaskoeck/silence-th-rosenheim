<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ServerController;
use App\Models\Project;
use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;


Route::redirect('/', '/dashboard');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/servers',   [ServerController::class, 'index'])->name('servers');
Route::get('/inventory',   fn() => view('inventory', ['runs' => collect(), 'projects' => Project::all()]))->name('inventory');
Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules');

Route::get('/login',  fn() => view('login'))->name('login');
Route::get('/logout', fn() => redirect()->route('login'))->name('logout');
