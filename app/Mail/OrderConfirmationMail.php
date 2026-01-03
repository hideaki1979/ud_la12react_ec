<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $cart;
    public $totalPrice;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $cart, $totalPrice)
    {
        $this->user = $user;
        $this->cart = $cart;
        $this->totalPrice = $totalPrice;
    }

    public function build()
    {
        return $this->view('emails.order_confirmation')
            ->subject('【注文完了】ご注文ありがとうございました')
            ->with([
                'user' => $this->user,
                'cart' => $this->cart,
                'totalPrice' => $this->totalPrice,
            ]);
    }
}
