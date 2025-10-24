<?php

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

// 'rating.limit'

Route::prefix('v1')->middleware(['rate.limit', ])->group(function () {
    Route::get('comptes', [CompteController::class, 'index']);
    Route::get('comptes/{numero}', [CompteController::class, 'show']);
    Route::get('comptes/client/{telephone}', [CompteController::class, 'getComptesByTelephone']);
    Route::post('comptes', [CompteController::class, 'store']);
    // Route::patch('comptes/{compte}', [CompteController::class, 'update']);
});
