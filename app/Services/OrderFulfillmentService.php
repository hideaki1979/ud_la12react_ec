<?php

namespace App\Services;

use App\Exceptions\InvalidCartDataException;
use App\Mail\AdminOrderNotificationMail;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

class OrderFulfillmentService
{
    /**
     * 注文のfulfillment処理を実行
     * Stripe公式推奨: WebhookとSuccessページの両方から呼び出し可能
     * 冪等性を保証し、競合状態を排他ロックで防止
     *
     * @param  string|null  $stripeSessionId  Stripeから状態を取得する場合に指定
     * @return array{success: bool, message: string, already_processed: bool}
     */
    public function fulfill(Order $order, ?string $stripeSessionId = null): array
    {
        // Stripeセッションから支払い状況を確認（指定された場合）
        if ($stripeSessionId) {
            $paymentStatus = $this->verifyPaymentStatus($stripeSessionId);
            if (! $paymentStatus['paid']) {
                Log::warning('OrderFulfillment: 決済が完了していません。Order ID: '.$order->id.', Status: '.$paymentStatus['status']);

                return [
                    'success' => false,
                    'message' => '決済が完了していません',
                    'already_processed' => false,
                ];
            }
        }

        try {
            $result = DB::transaction(function () use ($order) {
                // 排他ロックを取得して競合状態を防止
                $lockedOrder = Order::lockForUpdate()->find($order->id);

                if (! $lockedOrder) {
                    Log::error('OrderFulfillment: 注文が見つかりません。Order ID: '.$order->id);

                    return [
                        'success' => false,
                        'message' => '注文が見つかりません',
                        'already_processed' => false,
                    ];
                }

                // 冪等性チェック: 既に処理済みならスキップ
                if ($lockedOrder->stripe_status === 'completed') {
                    Log::info('OrderFulfillment: 注文は既に処理済みです。Order ID: '.$order->id);

                    return [
                        'success' => true,
                        'message' => '注文は既に処理済みです',
                        'already_processed' => true,
                    ];
                }

                // cart_dataの検証
                $cartData = $lockedOrder->cart_data;
                if (empty($cartData)) {
                    Log::error('OrderFulfillment: cart_dataが空です。Order ID: '.$order->id);
                    throw new InvalidCartDataException('Cart情報が空です。');
                }

                // stripe_statusをcompletedに更新
                $lockedOrder->update([
                    'stripe_status' => 'completed',
                ]);

                // order_itemsを作成
                $this->createOrderItems($lockedOrder, $cartData);

                Log::info('OrderFulfillment: 注文処理が完了しました。Order ID: '.$order->id);

                return [
                    'success' => true,
                    'message' => '注文処理が完了しました',
                    'already_processed' => false,
                    'order' => $lockedOrder,
                ];
            });

            // トランザクション成功後にメール送信（トランザクション外で実行）
            if ($result['success'] && ! $result['already_processed'] && isset($result['order'])) {
                $this->sendOrderEmails($result['order']);
            }

            return $result;

        } catch (InvalidCartDataException $e) {
            Log::error('OrderFulfillment: カートデータエラー: '.$e->getMessage().' Order ID: '.$order->id);
            $order->update(['stripe_status' => 'failed']);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'already_processed' => false,
            ];
        } catch (\Exception $e) {
            Log::error('OrderFulfillment: エラー発生: '.$e->getMessage().' Order ID: '.$order->id);
            $order->update(['stripe_status' => 'failed']);

            return [
                'success' => false,
                'message' => '処理中にエラーが発生しました',
                'already_processed' => false,
            ];
        }
    }

    /**
     * Stripeセッションから支払い状況を確認
     */
    private function verifyPaymentStatus(string $sessionId): array
    {
        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $session = StripeSession::retrieve($sessionId);

            return [
                'paid' => $session->payment_status === 'paid',
                'status' => $session->payment_status,
                'session' => $session,
            ];
        } catch (\Exception $e) {
            Log::error('OrderFulfillment: Stripeセッション取得エラー: '.$e->getMessage());

            return [
                'paid' => false,
                'status' => 'error',
                'session' => null,
            ];
        }
    }

    /**
     * order_itemsを作成
     */
    private function createOrderItems(Order $order, array $cartData): void
    {
        $now = now();

        $orderItems = collect($cartData)->map(function ($item, $productId) use ($order, $now) {
            if (! isset($item['quantity'], $item['price'])) {
                Log::error('OrderFulfillment: cart_dataのアイテム構造が不正です。Order ID: '.$order->id);
                throw new InvalidCartDataException('Cart itemの構造が不正です。');
            }

            return [
                'order_id' => $order->id,
                'product_id' => $productId,
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->values()->all();

        OrderItem::insert($orderItems);
    }

    /**
     * 注文完了メールを送信
     */
    private function sendOrderEmails(Order $order): void
    {
        // ユーザー情報を取得（N+1回避）
        $order->loadMissing('user');
        $user = $order->user;
        $cart = $order->cart_data;
        $totalPrice = $order->total_price;

        if (! $user) {
            Log::error('OrderFulfillment: メール送信時にユーザーが見つかりませんでした。Order ID: '.$order->id);

            return;
        }

        try {
            // ユーザーに注文確認メールを送信
            Mail::to($user->email)->send(new OrderConfirmationMail($user, $cart, $totalPrice));
            Log::info('OrderFulfillment: ユーザーへの注文確認メール送信完了。Order ID: '.$order->id);

            // 管理者に注文通知メールを送信
            $adminEmail = config('mail.admin_email');
            if ($adminEmail) {
                Mail::to($adminEmail)->send(new AdminOrderNotificationMail($user, $cart, $totalPrice));
                Log::info('OrderFulfillment: 管理者への注文通知メール送信完了。Order ID: '.$order->id);
            }
        } catch (\Exception $e) {
            Log::error('OrderFulfillment: メール送信エラー: '.$e->getMessage().' Order ID: '.$order->id);
        }
    }
}
