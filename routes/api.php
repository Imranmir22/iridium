<?php

use App\Http\Controllers\UserImportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('import-users', [UserImportController::class, 'import']);
