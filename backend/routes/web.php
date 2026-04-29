<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Models\Project;
use Illuminate\Support\Facades\Route;


Route::redirect('/', '/dashboard');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/servers',     fn() => 'Server')->name('servers');
Route::get('/inventory',   fn() => view('inventory', ['runs' => collect(), 'projects' => Project::all()]))->name('inventory');
Route::get('/schedules', fn() => 'Zeitplaene')->name('schedules');

Route::get('/login',  fn() => view('login'))->name('login');
Route::get('/logout', fn() => redirect()->route('login'))->name('logout');

Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
