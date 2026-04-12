<?php

use App\Http\Controllers\Api\AsistenciaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatIaController;
use App\Http\Controllers\Api\PagoController;
use App\Http\Controllers\Api\RutinaController;
use App\Http\Controllers\Api\SocioController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - SaaSGym MVP
|--------------------------------------------------------------------------
*/

// Autenticación (pública)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

// Rutas protegidas con Sanctum
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Socios (admin y entrenador pueden ver/crear/editar; solo admin elimina)
    Route::apiResource('socios', SocioController::class)->except(['destroy']);
    Route::delete('/socios/{socio}', [SocioController::class, 'destroy'])->middleware('es_admin');

    // Pagos (solo admin)
    Route::middleware('es_admin')->group(function () {
        Route::get('/pagos', [PagoController::class, 'index']);
        Route::post('/pagos', [PagoController::class, 'store']);
        Route::get('/pagos/{pago}', [PagoController::class, 'show']);
        Route::put('/pagos/{pago}', [PagoController::class, 'update']);
        Route::delete('/pagos/{pago}', [PagoController::class, 'destroy']);
    });

    // Asistencias (admin y entrenador)
    Route::get('/asistencias', [AsistenciaController::class, 'index']);
    Route::post('/asistencias', [AsistenciaController::class, 'store']);

    // Rutinas (admin y entrenador)
    Route::apiResource('rutinas', RutinaController::class);

    // Usuarios/Staff (solo admin)
    Route::middleware('es_admin')->group(function () {
        Route::get('/usuarios', [UserController::class, 'index']);
        Route::post('/usuarios', [UserController::class, 'store']);
        Route::get('/usuarios/{user}', [UserController::class, 'show']);
        Route::put('/usuarios/{user}', [UserController::class, 'update']);
        Route::delete('/usuarios/{user}', [UserController::class, 'destroy']);
    });

    // IA Chat (admin y entrenador)
    Route::post('/ia/chat', [ChatIaController::class, 'chat'])->middleware('throttle:ia');
    Route::get('/ia/historial', [ChatIaController::class, 'index']);
});
