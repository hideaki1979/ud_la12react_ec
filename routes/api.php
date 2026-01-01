<?php

use App\Http\Controllers\ZipcodeController;
use Illuminate\Support\Facades\Route;

Route::get('/zipcode/search', [ZipcodeController::class, 'search']);
