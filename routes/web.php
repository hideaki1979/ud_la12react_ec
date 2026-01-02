<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/checkout/step1', [ProductController::class, 'step1'])->name('checkout.step1');
    Route::post('/checkout/confirm', [ProductController::class, 'confirm']);
    Route::get('/checkout/cash-on-delivery', [ProductController::class, 'cashOnDelivery']);
});

Route::get('products', [ProductController::class, 'index'])->name('products.index');
Route::post('/products/add/{id}', [ProductController::class, 'addToCart'])->name('products.add')->where('id', '[0-9]+');
Route::post('/products/plus/{id}', [ProductController::class, 'addCartPlus'])->name('products.plus')->where('id', '[0-9]+');
Route::post('/products/minus/{id}', [ProductController::class, 'cartMinus'])->name('products.minus')->where('id', '[0-9]+');
Route::post('/products/remove/{id}', [ProductController::class, 'removeCart'])->name('products.remove')->where('id', '[0-9]+');

require __DIR__ . '/auth.php';
