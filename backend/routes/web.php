<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectServerController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ServerActionController;
use App\Http\Controllers\SettingsController;
use App\Services\Contracts\PendingActionTrackerInterface;
use Illuminate\Support\Facades\Route;

/*
 * Login-Route: Leitet direkt zum Shibboleth SSO-Handler weiter.
 * Eine eigene Login-Seite wird nicht benötigt, da Shibboleth die Anmeldung
 * komplett übernimmt — der Benutzer wird automatisch zum IDP der TH Rosenheim
 * weitergeleitet und kommt nach erfolgreicher Anmeldung zurück.
 */
Route::get('/login', function () {
    $targetUrl = url('/dashboard');

    return redirect(config('shibboleth.login_url').'?target='.urlencode($targetUrl));
})->name('login');

/*
 * Logout-Route: Zerstört die lokale Laravel-Session und leitet anschließend
 * zum Shibboleth Logout-Handler weiter, damit auch die SSO-Session am IDP
 * der TH Rosenheim beendet wird (Single Logout).
 */
Route::get('/logout', function () {
    session()->flush();

    $logoutUrl = config('shibboleth.logout_url');
    $returnUrl = url('/login');

    return redirect($logoutUrl.'?return='.urlencode($returnUrl));
})->name('logout');

/*
 * Geschützte Routen: Alle Routen innerhalb dieser Gruppe erfordern eine gültige
 * Shibboleth-Authentifizierung. Die Middleware prüft bei jedem Request, ob der
 * Benutzer über den Shibboleth SP authentifiziert ist und speichert die
 * Benutzerdaten in der Session.
 */
Route::middleware('shibboleth')->group(function () {
    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/servers', [ProjectServerController::class, 'index'])->name('servers');
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');
    Route::post('/inventory/run', [InventoryController::class, 'run'])->name('inventory.run');
    Route::post('/inventory/run/{project}', [InventoryController::class, 'runForProject'])->name('inventory.run.project');
    Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules');

    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    Route::post('/server-actions', [ServerActionController::class, 'store'])->name('server-actions.store');
    Route::put('/servers/{server}/server-actions', [ServerActionController::class, 'updateForServer'])->name('server-actions.update-for-server');
    Route::delete('/servers/{server}/server-actions', [ServerActionController::class, 'destroyForServer'])->name('server-actions.destroy-for-server');
    Route::post('/servers/{server}/server-actions/toggle', [ServerActionController::class, 'toggleForServer'])->name('server-actions.toggle-for-server');
    Route::patch('/servers/{server}/label', [ProjectServerController::class, 'updateLabel'])->name('servers.label');

    Route::post('/servers/{server}/start', [ProjectServerController::class, 'start'])->name('servers.start');
    Route::post('/servers/{server}/stop', [ProjectServerController::class, 'stop'])->name('servers.stop');
    Route::get('/servers/{server}/status', [ProjectServerController::class, 'status'])->name('servers.status');

    Route::get('/pending-actions/check', fn (PendingActionTrackerInterface $tracker) => response()->json($tracker->pendingServerIds()))
        ->name('pending-actions.check');

    Route::put('/settings/schedule-poll-interval', [SettingsController::class, 'updateSchedulePollInterval'])
        ->name('settings.schedule-poll-interval');
    Route::put('/settings/inventory-interval', [SettingsController::class, 'updateInventoryInterval'])
        ->name('settings.inventory-interval');
});
