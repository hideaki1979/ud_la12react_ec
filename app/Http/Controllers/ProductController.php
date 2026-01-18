<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use App\Mail\OrderConfirmationMail;
use App\Mail\AdminOrderNotificationMail;

use function Symfony\Component\Clock\now;

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
    public function index(Request $request)
    {
        // 価格範囲バリデーション
        $request->validate([
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    $minPrice = $request->input('min_price');
                    if ($minPrice !== null && $value !== null && (int) $minPrice > (int) $value) {
                        $fail('最低価格は最高価格以下にしてください');
                    }
                },
            ],
        ]);

        $query = Product::query()->where('active', false);

        // キーワード検索
        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        // カテゴリフィルター
        if ($categoryId = $request->input('category')) {
            $query->whereHas('categories', fn($q) => $q->where('categories.id', $categoryId));
        }

        // 価格範囲
        if ($request->filled('min_price') && is_numeric($request->input('min_price'))) {
            $query->where('price', '>=', (int) $request->input('min_price'));
        }
        if ($request->filled('max_price') && is_numeric($request->input('max_price'))) {
            $query->where('price', '<=', (int) $request->input('max_price'));
        }

        // ソート
        $sortField = $request->input('sort', 'created_at');
        $sortDir = $request->input('direction', 'desc');

        // 許可されたソートフィールドのみ
        $allowedSorts = ['created_at', 'price', 'name'];
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'created_at';
        }
        $sortDir = $sortDir === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortField, $sortDir);

        $products = $query->paginate(15)->withQueryString();
        ['cart' => $cart, 'totalPrice' => $totalPrice] = $this->getCartWithTotal();

        // カテゴリ一覧
        $categories = Category::active()->get(['id', 'name', 'slug']);

        return Inertia::render('Products/Index', [
            'products' => $products,
            'cartInfo' => $cart,
            'totalPrice' => $totalPrice,
            'categories' => $categories,
            'filters' => [
                'search' => $request->input('search', ''),
                'min_price' => $request->input('min_price', ''),
                'max_price' => $request->input('max_price', ''),
                'category' => $request->input('category', ''),
                'sort' => $sortField,
                'direction' => $sortDir,
            ],
        ]);
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
            // For Inertia XHR requests, use Inertia::location to trigger a
            // full-page/navigation redirect on the client so the browser
            // performs a top-level navigation to Stripe (avoids CORS/XHR issues).
            return Inertia::location(route('checkout.stripe'));
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
        // 支払い方法チェック
        $selectedMethod = session('selectedPaymentMethod');
        if ($selectedMethod !== 'cash_on_delivery') {
            return redirect()->route('checkout.step1')->with('error', '不正なアクセスです。');
        }

        // カート情報とユーザー情報を取得, 合計金額を計算
        $user = Auth::user();
        ['cart' => $cart, 'totalPrice' => $totalPrice] = $this->getCartWithTotal();

        // カートが空の場合はエラー
        if (empty($cart)) {
            return redirect()->route('products.index')->with('error', 'カートが空です。');
        }

        try {
            // トランザクション内で注文を保存
            DB::transaction(function () use ($user, $cart, $totalPrice) {
                // 注文情報を保存
                $order = Order::create([
                    'user_id' => $user->id,
                    'payment_method' => 'cash_on_delivery',
                    'total_price' => $totalPrice,
                ]);

                // 注文詳細を保存(Bulk Insert)
                $now = now();
                $orderItems = collect($cart)->map(fn($item, $productId) => [
                    'order_id' => $order->id,
                    'product_id' => $productId,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                OrderItem::insert($orderItems);
            });
        } catch (\Throwable $e) {
            Log::error('注文処理エラー：' . $e->getMessage());
            return redirect()->route('checkout.step1')->with('error', '注文処理中にエラーが発生しました。');
        }

        // トランザクション成功後にメールを送信（トランザクション外で実行）
        $this->sendOrderEmails($user, $cart, $totalPrice);

        // カートをクリア
        session()->forget('cart');
        session()->forget('selectedPaymentMethod');

        // 注文完了画面にリダイレクト
        return Inertia::render('Checkout/OrderComplete');
    }

    /**
     * 注文完了メールを送信
     *
     * @param mixed $user
     * @param array $cart
     * @param int|float $totalPrice
     * @return void
     */
    private function sendOrderEmails($user, array $cart, $totalPrice): void
    {
        try {
            // ユーザーに注文確認メールを送信
            Mail::to($user->email)->send(new OrderConfirmationMail($user, $cart, $totalPrice));
            Log::info('ユーザーへの注文確認メール送信完了。User ID: ' . $user->id);

            // 管理者に注文通知メールを送信
            $adminEmail = config('mail.admin_email');
            if ($adminEmail) {
                Mail::to($adminEmail)->send(new AdminOrderNotificationMail($user, $cart, $totalPrice));
                Log::info('管理者への注文通知メール送信完了。User ID: ' . $user->id);
            } else {
                Log::warning('管理者メールアドレスが設定されていません。User ID: ' . $user->id);
            }
        } catch (\Exception $e) {
            Log::error('メール送信エラー: ' . $e->getMessage() . ' User ID: ' . $user->id);
        }
    }
}
