<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * カート情報と合計金額を取得
     *
     * @return array{cart: array, totalPrice: int|float}
     */
    private function getCartWithTotal(): array
    {
        $cart = session()->get('cart', []);
        $totalPrice = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);

        return compact('cart', 'totalPrice');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::paginate(15);
        ['totalPrice' => $totalPrice] = $this->getCartWithTotal();

        return Inertia::render(
            'Products/Index',
            ['products' => $products, 'totalPrice' => $totalPrice]
        );
    }

    public function addToCart(int $id)
    {
        $product = Product::findOrFail($id);

        if ($product->active) {
            return redirect()->route('products.index')->with('error', 'この商品は現在購入出来ません。');
        }

        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            $cart[$id]['quantity']++;
        } else {
            $cart[$id] = [
                "name" => $product->name,
                "price" => $product->price,
                "code" => $product->code,
                "img" => $product->img,
                "quantity" => 1
            ];
        }

        session()->put('cart', $cart);
        return redirect()->route('products.index')->with('success', '商品をカートに追加しました');
    }

    public function addCartPlus(int $id)
    {
        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            $cart[$id]['quantity']++;
        } else {
            return redirect()->route('products.index')->with('error', 'カートに商品が見つかりません。');
        }
        session()->put('cart', $cart);
        return redirect()->route('products.index');
    }

    public function cartMinus(int $id)
    {
        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            if ($cart[$id]['quantity'] > 1) {
                $cart[$id]['quantity']--;
            }
        } else {
            return redirect()->route('products.index')->with('error', 'カートに商品が見つかりません。');
        }
        session()->put('cart', $cart);
        return redirect()->route('products.index');
    }

    public function removeCart(int $id)
    {
        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            unset($cart[$id]);
            session()->put('cart', $cart);
            return redirect()->route('products.index')->with('success', 'カートから商品を削除しました。');
        } else {
            return redirect()->route('products.index')->with('error', 'カートに商品が見つかりません。');
        }
    }

    public function step1()
    {
        $user = Auth::user();
        ['cart' => $cart, 'totalPrice' => $totalPrice] = $this->getCartWithTotal();

        return inertia('Checkout/Step1', [
            'user' => $user,
            'cartInfo' => $cart,
            'totalPrice' => $totalPrice
        ]);
    }

    public function confirm(Request $request)
    {
        $validated = $request->validate([
            'method' => ['required', 'string', Rule::in(['cash_on_delivery', 'stripe'])],
        ]);

        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('products.index')->with('error', 'カートが空です。');
        }

        $method = $validated['method'];
        session()->put('selectedPaymentMethod', $method);

        if ($method === 'cash_on_delivery') {
            return redirect()->route('checkout.cash-on-delivery');
        } elseif ($method === 'stripe') {
            return redirect()->route('checkout.stripe');
        }
    }

    public function cashOnDelivery()
    {
        $selectedMethod = session('selectedPaymentMethod');
        if ($selectedMethod !== 'cash_on_delivery') {
            return redirect()->route('checkout.step1')->with('error', '不正なアクセスです。');
        }

        $user = Auth::user();
        ['cart' => $cart, 'totalPrice' => $totalPrice] = $this->getCartWithTotal();

        return Inertia::render('Checkout/CashOnDelivery', [
            'user' => $user,
            'cartInfo' => $cart,
            'totalPrice' => $totalPrice,
        ]);
    }

    public function orderDone(Request $request)
    {
        // カート情報とユーザー情報を取得, 合計金額を計算
        $user = Auth::user();
        ['cart' => $cart, 'totalPrice' => $totalPrice] = $this->getCartWithTotal();

        // 管理者とユーザーにメールを送信
        Mail::to($user->email)->send(new \App\Mail\OrderConfirmationMail($user, $cart, $totalPrice));
        Mail::to('管理者のメールアドレス')->send(new \App\Mail\AdminOrderNotificationMail($user, $cart, $totalPrice));

        // カートをクリア
        session()->forget('cart');

        // 注文完了画面にリダイレクト
    }
}
