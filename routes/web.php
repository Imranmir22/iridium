<?php

use App\Http\Controllers\UserImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('import-users', [UserImportController::class, 'import']);
