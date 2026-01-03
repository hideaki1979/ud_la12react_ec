<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminOrderNotificationMail extends Mailable
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
        return $this->view('emails.admin_order_notification')
            ->subject('【注文通知】新しい注文がありました')
            ->with([
                'user' => $this->user,
                'cart' => $this->cart,
                'totalPrice' => $this->totalPrice,
            ]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Admin Order Notification Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'view.name',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
