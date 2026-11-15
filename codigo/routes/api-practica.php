<?php

use App\Practica31\Http\Controllers\ReportePedidosController;
use Illuminate\Support\Facades\Route;

Route::prefix('practica/v1/reportes/pedidos')->group(function (): void {
    Route::post('/', [ReportePedidosController::class, 'encolar']);
    Route::post('/sincrono', [ReportePedidosController::class, 'sincronico']);
});
