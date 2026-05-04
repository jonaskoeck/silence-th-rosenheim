<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectServerController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ServerActionController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/servers', [ProjectServerController::class, 'index'])->name('servers');
Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');
Route::post('/inventory/run', [InventoryController::class, 'run'])->name('inventory.run');
Route::post('/inventory/run/{project}', [InventoryController::class, 'runForProject'])->name('inventory.run.project');
Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules');
Route::get('/login', fn () => view('login'))->name('login');
Route::get('/logout', fn () => redirect()->route('login'))->name('logout');
Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
Route::post('/server-actions', [ServerActionController::class, 'store'])->name('server-actions.store');
Route::delete('/servers/{server}/server-actions', [ServerActionController::class, 'destroyForServer'])->name('server-actions.destroy-for-server');
Route::patch('/servers/{server}/label', [ProjectServerController::class, 'updateLabel'])->name('servers.label');
