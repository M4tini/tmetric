<?php

use App\Http\Controllers\TmetricController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TmetricController::class, 'index']);
