<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\LogisticsController;
use App\Http\Controllers\VictimaController;
use App\Http\Middleware\ApiAuthenticate;
use App\Http\Middleware\EnsureApiAuthority;
use App\Http\Middleware\RedirectIfApiAuthenticated;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return session()->has('api_token')
        ? redirect()->to(route('reports.index', [], false))
        : redirect()->to(route('login', [], false));
})->name('home');

Route::middleware(RedirectIfApiAuthenticated::class)->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/reporte-rapido', function () {
    // Solo id, latitud y longitud — intensidad_actual fue eliminada (cálculo dinámico).
    $activas = \App\Models\Inundacion::where('estado', 'activa')->get(['id', 'latitud', 'longitud']);
    return view('reports.rapido', ['inundacionesActivas' => $activas]);
})->name('reports.rapido');

Route::middleware(ApiAuthenticate::class)->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/maps', [MapController::class, 'index'])->name('maps.index');
    Route::get('/reports/create', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
    Route::get('/reports/notifications/feed', [ReportController::class, 'notificationsFeed'])->name('reports.notifications.feed');
    Route::get('/reports/{id}', [ReportController::class, 'show'])->name('reports.show');

    Route::middleware(EnsureApiAuthority::class)->group(function () {
        Route::post('/reports/{id}/responses', [ReportController::class, 'storeResponse'])->name('reports.responses.store');
        Route::post('/reports/{id}/status', [ReportController::class, 'updateestado'])->name('reports.status.update');
        Route::post('/reports/{id}/desactivar', [ReportController::class, 'desactivar'])->name('reports.desactivar');
        Route::post('/reports/rechazados/{id}/estado-validacion', [ReportController::class, 'updateEstadoValidacion'])->name('reports.rechazados.estado_validacion.update');
        Route::get('/reports/notifications/latest', [ReportController::class, 'latestForNotifications'])->name('reports.notifications.latest');
    });

    // ── Logística (Centros de Asistencia) ────────────────────────────────
    Route::get('/logistica', [LogisticsController::class, 'index'])->name('logistica.index');

    // ── Módulo de Víctimas ────────────────────────────────────────────────
    // Las rutas GET con segmentos estáticos deben ir ANTES de la ruta con {id}
    Route::get('/victimas', [VictimaController::class, 'index'])->name('victimas.index');
    Route::get('/victimas/create', [VictimaController::class, 'create'])->name('victimas.create');
    Route::get('/victimas/{id}', [VictimaController::class, 'show'])->name('victimas.show')->where('id', '[0-9]+');

    // Operaciones de escritura — solo autoridad
    Route::middleware(EnsureApiAuthority::class)->group(function () {
        // Logística
        Route::post('/logistica', [LogisticsController::class, 'store'])->name('logistica.store');
        Route::patch('/logistica/{id}', [LogisticsController::class, 'update'])->name('logistica.update');
        Route::delete('/logistica/{id}', [LogisticsController::class, 'destroy'])->name('logistica.destroy');

        // Víctimas — CRUD completo
        Route::post('/victimas', [VictimaController::class, 'store'])->name('victimas.store');
        Route::get('/victimas/{id}/edit', [VictimaController::class, 'edit'])->name('victimas.edit')->where('id', '[0-9]+');
        Route::put('/victimas/{id}', [VictimaController::class, 'update'])->name('victimas.update')->where('id', '[0-9]+');
        Route::delete('/victimas/{id}', [VictimaController::class, 'destroy'])->name('victimas.destroy')->where('id', '[0-9]+');
    });
});
