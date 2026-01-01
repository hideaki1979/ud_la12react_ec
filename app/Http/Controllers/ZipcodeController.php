<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ZipcodeController extends Controller
{
    public function search(Request $request)
    {
        $validated = $request->validate(['zipcode' => 'required|digits:7']);
        try {
            $response = Http::get(config('services.zipcloud.url'), [
                'zipcode' => $validated['zipcode'],
            ]);

            if ($response->failed()) {
                return response()->json(['error' => '郵便番号の検索に失敗しました。'], 500);
            }

            $response->throw();

            return $response->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            \Illuminate\Support\Facades\Log::error('Zipcode API connection error', ['error' => $e->getMessage()]);
            return response()->json(['message' => '住所検索サービスに接続できませんでした。'], 503);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            \Illuminate\Support\Facades\Log::error('Zipcode API request error', ['status' => $e->response->status(), 'body' => $e->response->body()]);
            return response()->json(['message' => '住所の検索に失敗しました。'], $e->response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => '郵便番号の検索に失敗しました。'], 500);
        }
    }
}
