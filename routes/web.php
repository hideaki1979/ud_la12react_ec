<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\StripeWebhookController;
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
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/checkout/step1', [ProductController::class, 'step1'])->name('checkout.step1');
    Route::post('/checkout/confirm', [ProductController::class, 'confirm'])->name('checkout.confirm');
    Route::get('/checkout/cash-on-delivery', [ProductController::class, 'cashOnDelivery'])->name('checkout.cash-on-delivery');
    Route::post('/checkout/order-done', [ProductController::class, 'orderDone'])->name('checkout.order_done');
    Route::get('/checkout/stripe', [StripeController::class, 'createSession'])->name('checkout.stripe');
    Route::get('/checkout/stripe/success', [StripeController::class, 'success'])->name('stripe.success');
    Route::get('/checkout/stripe/cancel', [StripeController::class, 'cancel'])->name('stripe.cancel');
});

Route::get('products', [ProductController::class, 'index'])->name('products.index');
Route::post('/products/add/{id}', [ProductController::class, 'addToCart'])->name('products.add')->where('id', '[0-9]+');
Route::post('/products/plus/{id}', [ProductController::class, 'addCartPlus'])->name('products.plus')->where('id', '[0-9]+');
Route::post('/products/minus/{id}', [ProductController::class, 'cartMinus'])->name('products.minus')->where('id', '[0-9]+');
Route::post('/products/remove/{id}', [ProductController::class, 'removeCart'])->name('products.remove')->where('id', '[0-9]+');

// Stripe Webhook endpoint for receiving events from Stripe
// Note: Laravel 12 uses framework middleware classes directly
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
    ->name('stripe.webhook');

require __DIR__ . '/auth.php';
