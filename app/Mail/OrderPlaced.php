<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlaced extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public bool $forAdmin = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->forAdmin ? 'Yeni sipariş: ' : 'Siparişiniz alındı: ').$this->order->number);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.order', with: ['order' => $this->order->load('items'), 'forAdmin' => $this->forAdmin]);
    }
}
