<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\Stripe;
use App\Exceptions\InvalidCartDataException;

class ProcessStripeWebhook implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    protected $eventPayload;

    /**
     * Create a new job instance.
     */
    public function __construct(array $eventPayload)
    {
        $this->eventPayload = $eventPayload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $event = Event::constructFrom($this->eventPayload);

        // イベントタイプに基づいて処理を分岐
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $this->handleCheckoutSessionCompleted($session);
                break;
            default:
                Log::info('Job: Received unknown Stripe event type: ' . $event->type);
        }
    }

    protected function handleCheckoutSessionCompleted($session)
    {
        // StripeセッションIDと注文IDをメタデータから取得
        $orderId = $session->metadata->order_id ?? null;
        $stripeSessionId = $session->id;

        if (!$orderId) {
            Log::error('Webhook Job: Stripeセッションメタデータにorder_idが見つかりませんでした。Session ID: ' . $stripeSessionId);
            return;
        }

        // データベースから注文を取得
        $order = Order::find($orderId);

        if (!$order) {
            Log::error('Webhook Job: 注文ID ' . $orderId . ' の注文が見つかりませんでした。Session ID: ' . $stripeSessionId);
            return;
        }

        // 既に処理済みか、またはステータスがcompletedでないかを確認（冪等性）
        if ($order->stripe_status === 'completed') {
            Log::warning('Webhook Job: 注文ID ' . $orderId . ' は既に処理済みです。Session ID: ' . $stripeSessionId);
            return;
        }

        // 決済ステータスの確認
        if ($session->payment_status !== 'paid') {
            Log::warning('Stripe決済が完了していません。Session ID: ' . $session->id . ', Status：' . $session->payment_status);
            // 注文ステータスを'failed'に更新する
            $order->update(['stripe_status' => 'failed']);
            return;
        }

        try {
            $orderData = DB::transaction(function () use ($session, $stripeSessionId, $order) {

                // 注文情報を更新
                $order->update([
                    'stripe_session_id' => $stripeSessionId,
                    'stripe_pay_intent_id' => $session->payment_intent,
                    'stripe_status' => 'completed', // ステータスを完了に更新
                ]);

                // 注文詳細を保存 (cart_dataはOrderモデルに保存されている)
                $originalCart = $order->cart_data;

                if (empty($originalCart)) {
                    Log::error('Webhook Job: 注文ID ' . $order->id . ' のcart_dataが空です。');
                    throw new InvalidCartDataException('Cart情報が空です。');
                }

                // 注文詳細を保存(Bulk Insert)
                $now = now();

                $orderItems = collect($originalCart)->map(function ($item, $productId) use ($order, $now) {
                    if (!isset($item['quantity'], $item['price'])) {
                        Log::error('Webhook Job: cart_dataのアイテム構造が不正です。Order ID: ' . $order->id);
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

                Log::info('Webhook Job: Order processed successfully for Order ID: ' . $order->id . ', Session ID: ' . $stripeSessionId);

                // 管理者とユーザーにメールを送信
                // try {
                //     Mail::to($user->email)->send(new \App\Mail\OrderConfirmationMail($user, $cart, $totalPrice));
                //     Mail::to(env('ADMIN_EMAIL'))->send(new \App\Mail\AdminOrderNotificationMail($user, $cart, $totalPrice));
                // } catch (\Exception $e) {
                //     Log::error('メール送信エラー: ' . $e->getMessage());
                // }

                return $order;
            });
        } catch (\Exception $e) {
            Log::error('Stripe決済エラー：' . $e->getMessage());
            $order->update(['stripe_status' => 'failed']); // エラー時はステータスをfailedにする。
        }
    }
}
