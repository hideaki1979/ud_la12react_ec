<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::paginate(15);

        // 合計金額を計算
        $cart = session()->get('cart', []);
        $totalPrice = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);
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
        // ユーザーがログインしていることを確認
        $user = Auth::user();
        $cart = session()->get('cart', []);
        $totalPrice = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);

        // Inertiaを使用してビューにデータを渡す
        return inertia('Checkout/Step1', [
            'user' => $user,    // ログインユーザー情報
            'totalPrice' => $totalPrice
        ]);
    }

    public function confirm(Request $request)
    {
        $method = $request->input('method');
        session()->put('selectedPaymentMethod', $method);

        if ($method === 'cash_on_delivery') {
            return redirect('/checkout/cash-on-delivery');
        } elseif ($method === 'stripe') {
            return redirect('/checkout/stripe');
        }

        return back();
    }

    public function cashOnDelivery()
    {
        $user = Auth::user();
        $cart = session()->get('cart', []);
        $totalPrice = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);

        return Inertia::render('Checkout/CashOnDelivery', [
            'user' => $user,
            'totalPrice' => $totalPrice,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        //
    }
}
