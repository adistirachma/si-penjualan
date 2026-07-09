<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataPenjualanController;
use App\Http\Controllers\ForecastController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {

    // ─── Dashboard: semua role ─────────────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ─── Khusus Admin ─────────────────────────────────────────────────────
    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });

    // ─── Admin & Petugas Gudang ────────────────────────────────────────────
    Route::middleware('role:admin,petugas_gudang')->group(function () {
        // Produk
        Route::resource('products', ProductController::class)->except(['show']);

        // ─── API: data penjualan bulanan (dipakai JS di halaman Data Penjualan) ─
        Route::get('/sales/monthly-data', [DataPenjualanController::class, 'getMonthlyData'])->name('sales.getMonthlyData');

        // ─── Data Penjualan (satu halaman terpadu) ─────────────────────────
        Route::get('/data-penjualan',         [DataPenjualanController::class, 'index'])     ->name('penjualan.index');
        Route::post('/data-penjualan',        [DataPenjualanController::class, 'storeSales'])->name('penjualan.sales.store');
        Route::post('/data-penjualan/import', [DataPenjualanController::class, 'import'])    ->name('penjualan.import');

        // ─── Peramalan ──────────────────────────────────────────────────────
        // Static routes harus sebelum wildcard {forecast}
        Route::get('/forecasting',              [ForecastController::class, 'index'])        ->name('forecasting.index');
        Route::post('/forecasting',             [ForecastController::class, 'calculate'])    ->name('forecasting.calculate');
        Route::post('/forecasting/auto-optimize',[ForecastController::class, 'autoOptimize'])->name('forecasting.auto-optimize');
        Route::get('/forecasting/testing',      [ForecastController::class, 'testingIndex']) ->name('forecasting.testing');
        Route::post('/forecasting/test/parameters',[ForecastController::class,'testParameters'])->name('forecasting.test.parameters');
        Route::post('/forecasting/test/periods',[ForecastController::class, 'testPeriods'])  ->name('forecasting.test.periods');
        Route::get('/forecasting/history',      [ForecastController::class, 'history'])      ->name('forecasting.history');
        Route::delete('/forecasting/history/{forecast}', [ForecastController::class, 'historyDestroy'])->name('forecasting.history.destroy');
    });
});
