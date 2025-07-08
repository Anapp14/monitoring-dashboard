<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MonitoringController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('web')->group(function () {
    Route::get('/monitoring-data', [MonitoringController::class, 'getMonitoringData']);
});

// Alternative route if using different middleware
Route::get('/monitoring-data', [MonitoringController::class, 'getMonitoringData'])
    ->middleware(['web']);

Route::get('/admin-monitoring-data', [MonitoringController::class, 'getAdminMonitoringData'])->name('api.admin.monitoring.data');
