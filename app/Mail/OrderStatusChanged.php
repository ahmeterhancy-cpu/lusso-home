<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public ?string $note = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Siparişiniz '.$this->order->number.': '.$this->order->statusLabel());
    }

    public function content(): Content
    {
        return new Content(view: 'mail.order-status', with: ['order' => $this->order, 'note' => $this->note]);
    }
}
