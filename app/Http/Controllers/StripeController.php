<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

use function Symfony\Component\Clock\now;

class StripeController extends Controller
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

    public function createSession(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'ログインが必要です。');
        }

        Stripe::setApiKey(config('services.stripe.secret'));
        ['cart' => $cart, 'totalPrice' => $totalPrice] = $this->getCartWithTotal();

        if (empty($cart)) {
            return redirect()->route('products.index')->with('error', 'カートが空です。');
        }

        // 注文を'pending'ステータスで事前に作成し、カートデータを保存
        $order = Order::create([
            'user_id' => $user->id,
            'payment_method' => 'stripe',
            'total_price' => $totalPrice,
            'stripe_status' => 'pending',   // Stripe決済待ち
            'cart_data' => $cart,   // カートデータをJSONとして保存
        ]);

        $lineItems = [];

        foreach ($cart as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'jpy',
                    'product_data' => [
                        'name' => $item['name'],
                    ],
                    'unit_amount' => $item['price'],
                ],
                'quantity' => $item['quantity'],
            ];
        }

        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems, // 商品の配列
            'mode' => 'payment',    // 一括支払（定額課金なら'subscription'）
            'success_url' => route('stripe.success') . '?session_id={CHECKOUT_SESSION_ID}',   // 決済成功後のリダイレクト
            'cancel_url' => route('stripe.cancel'), // キャンセル時のリダイレクト
            'customer_email' => $user->email,
            'metadata' => [
                'cart_hash' => md5(json_encode($cart)),
                'user_id' => $user->id,
            ]
        ]);

        // Stripeリダイレクト前にカートをセッションに保存
        $order->stripe_session_id = $session->id;
        $order->save();

        return redirect()->away($session->url);
    }

    public function success(Request $request)
    {

        // StripeセッションIDの取得
        $sessionId = $request->get('session_id');

        if (!$sessionId) {
            // セッションIDがない場合は不正なアクセスと判断
            Log::warning('Stripe successコールバックでsession_idが見つかりませんでした。');
            return Inertia::render('Checkout/OrderComplete', [
                'error' => '決済情報が見つかりませんでした',
            ]);
        }

        // Webhookで注文が処理されるのを待つか、既に処理済みかを確認
        // stripe_session_idで注文を検索
        $order = Order::where('stripe_session_id', $sessionId)->first();

        if ($order) {
            if ($order->stripe_status === 'completed') {
                // 注文がWebhookによって正常に処理された場合
                session()->forget(['cart', 'selectedPaymentMethod', 'cart_for_stripe_session_' . $sessionId]);
                return Inertia::render('Checkout/OrderComplete', [
                    'message' => 'ご注文ありがとうございます。',
                ]);
            } else if ($order->status === 'pending') {
                // 注文がまだWebhookで処理中の場合（稀だが、発生しうる）
                // ユーザーに処理中であることを伝える
                return Inertia::render('Checkout/OrderComplete', [
                    'message' => 'ご注文を処理中です。しばらくお待ちください。',
                ]);
            } else {
                // その他のステータス（例: failed）
                return Inertia::render('Checkout/OrderComplete', [
                    'error' => '注文処理中に問題が発生しました。後ほど注文履歴をご確認ください。',
                ]);
            }
        } else {
            // 注文が見つからない場合
            Log::error('Stripe successコールバックで注文が見つかりませんでした。Session ID: ' . $sessionId);
            return Inertia::render('Checkout/OrderComplete', [
                'error' => '注文情報が見つかりませんでした。',
            ]);
        }
    }

    public function cancel()
    {
        return redirect('/products')->with('error', '支払いキャンセルされました。');
    }
}
