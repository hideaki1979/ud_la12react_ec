<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Auth::user()
            ->orders()
            ->with(['items.product:id,name,img,code'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
        ]);
    }

    public function show(int $id)
    {
        $order = Auth::user()
            ->orders()
            ->with(['items.product:id,name,img,code'])
            ->findOrFail($id);

        return Inertia::render('Orders/Show', [
            'order' => $order,
        ]);
    }
}
