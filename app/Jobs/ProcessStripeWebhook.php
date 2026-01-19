<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrderFulfillmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\Stripe;

class ProcessStripeWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
    public function handle(OrderFulfillmentService $fulfillmentService): void
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $event = Event::constructFrom($this->eventPayload);

        // イベントタイプに基づいて処理を分岐
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $this->handleCheckoutSessionCompleted($session, $fulfillmentService);
                break;
            default:
                Log::info('Job: Received unknown Stripe event type: '.$event->type);
        }
    }

    protected function handleCheckoutSessionCompleted($session, OrderFulfillmentService $fulfillmentService): void
    {
        // StripeセッションIDと注文IDをメタデータから取得
        $orderId = $session->metadata->order_id ?? null;
        $stripeSessionId = $session->id;

        if (! $orderId) {
            Log::error('Webhook Job: Stripeセッションメタデータにorder_idが見つかりませんでした。Session ID: '.$stripeSessionId);

            return;
        }

        // データベースから注文を取得
        $order = Order::find($orderId);

        if (! $order) {
            Log::error('Webhook Job: 注文ID '.$orderId.' の注文が見つかりませんでした。Session ID: '.$stripeSessionId);

            return;
        }

        // 決済ステータスの確認
        if ($session->payment_status !== 'paid') {
            Log::warning('Webhook Job: Stripe決済が完了していません。Session ID: '.$session->id.', Status：'.$session->payment_status);
            $order->update(['stripe_status' => 'failed']);

            return;
        }

        // stripe_session_idとpayment_intent_idを更新
        $order->update([
            'stripe_session_id' => $stripeSessionId,
            'stripe_pay_intent_id' => $session->payment_intent,
        ]);

        // FulfillmentServiceでfulfillment処理を実行
        $result = $fulfillmentService->fulfill($order);

        if ($result['already_processed']) {
            Log::info('Webhook Job: 注文は既にSuccessページで処理済みです。Order ID: '.$orderId);
        } elseif ($result['success']) {
            Log::info('Webhook Job: 注文処理が完了しました。Order ID: '.$orderId);
        } else {
            Log::error('Webhook Job: 注文処理に失敗しました。Order ID: '.$orderId.', Message: '.$result['message']);
        }
    }
}
