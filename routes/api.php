<?php

use App\Http\Controllers\ZipcodeController;
use Illuminate\Support\Facades\Route;

Route::get('/zipcode/search', [ZipcodeController::class, 'search'])
    ->middleware('throttle:30,1');  // 1分間に30リクエストまで。
