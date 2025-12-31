<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ZipcodeController extends Controller
{
    public function search(Request $request)
    {
        $zipcode = $request->input('zipcode');
        $response = Http::get("https://zipcloud.ibsnet.co.jp/api/search", [
            'zipcode' => $zipcode,
        ]);

        return response($response->body(), $response->status())
            ->header('Content-Type', 'application/json');
    }
}
