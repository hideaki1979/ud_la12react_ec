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

        return response()->json(['status' => 'success'], 200);
    }
}
