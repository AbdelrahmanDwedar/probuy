<?php

use App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum'])->get('/user', fn (Request $request) => $request->user());

Route::prefix('products')->group(function () {
    Route::get('/', [Controllers\ProductController::class, 'index']);
    Route::post('/', [Controllers\ProductController::class, 'store']);
    Route::get('/{id}', [Controllers\ProductController::class, 'show']);
    Route::put('/{id}', [Controllers\ProductController::class, 'update']);
    Route::delete('/{id}', [Controllers\ProductController::class, 'delete']);
    Route::get('/{id}/add', [Controllers\ProductController::class, 'addToCart']);
});
