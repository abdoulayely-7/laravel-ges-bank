<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\CompteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
//
//// Routes d'authentification
//Route::prefix('v1/auth')->group(function () {
//    Route::post('login', [App\Http\Controllers\Api\AuthController::class, 'login']);
//    Route::post('refresh', [App\Http\Controllers\Api\AuthController::class, 'refresh']);
//    Route::middleware('auth:api')->post('logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);
//});
//
//// Routes protégées de l'API
//Route::middleware(['auth:api'])->prefix('v1')->group(function () {
//    // Routes pour les comptes - accès client
//    Route::middleware(['role:view_own_comptes'])->group(function () {
//        Route::get('comptes', [CompteController::class, 'index']);
//        Route::get('comptes/{numero}', [CompteController::class, 'show']);
//        Route::get('comptes/id/{compte}', [CompteController::class, 'getCompteById'])->name('comptes.show.id');
//        Route::get('comptes/client/{telephone}', [CompteController::class, 'getComptesByTelephone']);
//    });
//
//    // Routes nécessitant des permissions spéciales
//    Route::middleware(['role:create_transaction'])->group(function () {
//        Route::post('comptes', [CompteController::class, 'store']);
//    });
//
//    // Routes d'administration - seulement pour les admins
//    Route::middleware(['role:manage_comptes'])->group(function () {
//        Route::delete('comptes/{compte}', [CompteController::class, 'destroy']);
//        // Route::patch('comptes/{compte}', [CompteController::class, 'update']);
//    });
//});
//
//Route::prefix('v1/auth')->group(function () {
//    Route::post('/login', [AuthController::class, 'login']);
//    Route::post('/refresh', [AuthController::class, 'refresh']);
//    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
//});

Route::prefix('v1')->group(function () {
    Route::get('comptes', [CompteController::class, 'index']);
    Route::post('comptes', [CompteController::class, 'store']);
    Route::get('comptes/{numero}', [CompteController::class, 'show']);
    Route::get('comptes/id/{compte}', [CompteController::class, 'getCompteById'])->name('comptes.show.id');
    Route::get('comptes/client/{telephone}', [CompteController::class, 'getComptesByTelephone']);
    Route::delete('comptes/{compte}', [CompteController::class, 'destroy']);
    Route::patch('comptes/{compte}', [CompteController::class, 'updateClient']);
    Route::post('comptes/{compte}/bloquer', [CompteController::class, 'bloquer']);
    Route::post('comptes/{compte}/planifier-blocage', [CompteController::class, 'planifierBlocage']);
    Route::post('comptes/{compte}/debloquer', [CompteController::class, 'debloquer']);
    Route::post('trigger-block-job', function () {
        \App\Jobs\ProcessScheduledAccountBlocks::dispatch();
        return response()->json(['message' => 'Block job triggered']);
    });
});
