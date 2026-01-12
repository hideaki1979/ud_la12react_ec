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
            'success_url' => route('stripe.success'),   // 決済成功後のリダイレクト
            'cancel_url' => route('stripe.cancel'), // キャンセル時のリダイレクト
            'customer_email' => $user->email,
        ]);

        return redirect()->away($session->url);
    }

    public function success(Request $request)
    {
        try {
            // トランザクション
            DB::beginTransaction();

            // StripeセッションIDの取得
            $sessionId = $request->get('session_id');
            if (!$sessionId) {
                // セッションIDがない場合は不正なアクセスと判断
                DB::rollBack();
                Log::warning('Stripe successコールバックでsession_idが見つかりませんでした。');
                return Inertia::render('Checkout/OrderComplete', [
                    'error' => '決済情報が見つかりませんでした',
                ]);
            }

            Stripe::setApiKey(config('services.stripe.secret'));
            $session = StripeSession::retrieve($sessionId);

            // 決済ステータスの確認
            if ($session->payment_status !== 'paid') {
                DB::rollBack();
                Log::warning('Stripe決済が完了していません。Session ID: ' . $sessionId . ', Status：' . $session->payment_status);
                return Inertia::render('Checkout/OrderComplete', [
                    'error' => '決済が完了していません。再度お試しください。',
                ]);
            }

            // カートとユーザー情報の取得、合計金額を計算
            $user = Auth::user();
            ['cart' => $cart, 'totalPrice' => $totalPrice] = $this->getCartWithTotal();

            // カートが空の場合の処理を改善
            if (empty($cart)) {
                DB::rollBack();
                Log::warning('決済成功後、カートが空でした。ユーザーID: ' . $user->id);
                return Inertia::render('Checkout/OrderComplete', [
                    'error' => 'カートに商品がありませんでした。',
                ]);
            }

            // 注文情報を保存
            $order = Order::create([
                'user_id' => $user->id,
                'payment_method' => 'stripe',   // Stripe決済
                'total_price' => $totalPrice,
                'stripe_session_id' => $sessionId,
                'stripe_pay_intent_id' => $session->payment_intent,
            ]);

            // 注文詳細を保存
            foreach ($cart as $productId => $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $productId,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            // 管理者とユーザーにメールを送信
            // try {
            //     Mail::to($user->email)->send(new \App\Mail\OrderConfirmationMail($user, $cart, $totalPrice));
            //     Mail::to(env('ADMIN_EMAIL'))->send(new \App\Mail\AdminOrderNotificationMail($user, $cart, $totalPrice));
            // } catch (\Exception $e) {
            //     Log::error('メール送信エラー: ' . $e->getMessage());
            // }

            // カートクリア
            session()->forget('cart');

            // コミット
            DB::commit();

            // 注文完了画面に遷移
            return Inertia::render('Checkout/OrderComplete');
        } catch (\Exception $e) {
            // ロールバック
            DB::rollBack();

            Log::error('Stripe決済エラー：' . $e->getMessage());

            return Inertia::render('Checkout/OrderComplete', [
                'error' => '決済完了後の注文処理でエラーが発生しました。',
            ]);
        }
    }

    public function cancel()
    {
        return redirect('/products')->with('error', '支払いキャンセルされました。');
    }
}
