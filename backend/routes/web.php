<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/servers', fn () => 'Server')->name('servers');
Route::get('/schedules', fn () => 'Zeitplaene')->name('schedules');

Route::get('/login', fn () => view('login'))->name('login');
Route::get('/logout', fn () => redirect()->route('login'))->name('logout');

Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
