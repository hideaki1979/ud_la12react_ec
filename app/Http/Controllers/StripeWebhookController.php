<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessStripeWebhook;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            Log::error("StripeWebhook署名認証でエラー発生：" . $e->getMessage());
            return response()->json(['error' => '署名認証不正エラー'], 403);
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            Log::error('Stripe Webhook ペイロード不正：' . $e->getMessage());
            return response()->json(['error' => 'ペイロード不正'], 400);
        }

        // Webhookイベントを非同期で処理するためにJobをディスパッチ
        ProcessStripeWebhook::dispatch($event->toArray());  // Eventオブジェクトを配列に変換して渡す

        response()->json(['status' => 'success'], 200);
    }

    protected function handleCheckoutSessionCompleted($session)
    {
        // 既に同じセッションIDで注文が存在するかチェック
        $existingOrder = Order::where('stripe_session_id', $session->id)->first();
        if ($existingOrder) {
            Log::warning("Stripe決済はすでに処理されています。Session ID: " . $session->id);
            session()->forget('cart');
            return; // 既に処理済みなので何もしない
        }

        // 決済ステータスの確認
        if ($session->payment_status !== 'paid') {
            Log::warning('Stripe決済が完了していません。Session ID: ' . $session->id . ', Status：' . $session->payment_status);
            return;
        }
        try {
            $order = DB::transaction(function () use ($session) {
                // カートとユーザー情報の取得、合計金額を計算
                $userId = $session->metadata->user_id;
                $cartHash = $session->metadata->cart_hash;

                // Stripeリダイレクト前に保存したカート情報を取得
                // ここでは、StripeControllerのcreateSessionで保存したセッションキーを使用
                $originalCart = session('cart_for_stripe_session_' . $session->id);

                // カート改ざんチェック
                // カート改ざんチェック
                if (empty($originalCart) || md5(json_encode($originalCart)) !== $cartHash) {
                    Log::error('セッションID：' . $session->id . 'でカートの改ざんの可能性を検出しました。');
                    return;
                }

                // 検証済みの 'originalCart' を使用して注文を作成し、ライブセッションカートは使用しない
                $totalOriginalCart = collect($originalCart)->sum(fn($item) => $item['price'] * $item['quantity']);

                // 注文情報を保存
                $order = Order::create([
                    'user_id' => $userId,
                    'payment_method' => 'stripe',   // Stripe決済
                    'total_price' => $totalOriginalCart,
                    'stripe_session_id' => $session->id,
                    'stripe_pay_intent_id' => $session->payment_intent,
                ]);

                // 注文詳細を保存(Bulk Insert)
                $now = now();

                $orderItems = collect($originalCart)->map(fn($item, $productId) => [
                    'order_id' => $order->id,
                    'product_id' => $productId,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                OrderItem::insert($orderItems);

                return $order;
            });

            // 管理者とユーザーにメールを送信
            // try {
            //     Mail::to($user->email)->send(new \App\Mail\OrderConfirmationMail($user, $cart, $totalPrice));
            //     Mail::to(env('ADMIN_EMAIL'))->send(new \App\Mail\AdminOrderNotificationMail($user, $cart, $totalPrice));
            // } catch (\Exception $e) {
            //     Log::error('メール送信エラー: ' . $e->getMessage());
            // }

            // カート、支払選択クリア
            session()->forget(['cart', 'selectedPaymentMethod', 'cart_for_stripe_session_' . $session->id]);
            Log::info('Webhook: Order processed successfully for Session ID: ' . $session->id);
        } catch (\Exception $e) {
            Log::error('Stripe決済エラー：' . $e->getMessage());
        }
    }
}
