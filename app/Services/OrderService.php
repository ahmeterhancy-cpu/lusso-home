<?php

namespace App\Services;

use App\Mail\OrderPlaced;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

class OrderService
{
    public function __construct(private CartService $cart) {}

    /** Sepetten sipariş oluşturur. Stok adedi tanımlı ürünlerde stok kilitlenerek düşülür. */
    public function createFromCart(array $data, string $method, ?int $userId): Order
    {
        $lines = $this->cart->lines();
        if ($lines->isEmpty()) {
            throw new RuntimeException('Sepetiniz boş.');
        }

        $order = DB::transaction(function () use ($lines, $data, $method, $userId) {
            $subtotal = 0;
            foreach ($lines as $l) {
                $p = Product::whereKey($l['product']->id)->lockForUpdate()->first();
                if (! $p || ! $p->purchasable()) {
                    throw new RuntimeException("“{$l['product']->name}” artık satışta değil.");
                }
                if ($p->stock_qty !== null) {
                    if ($p->stock_qty < $l['qty']) {
                        throw new RuntimeException("“{$p->name}” için yeterli stok yok (kalan: {$p->stock_qty}).");
                    }
                    $p->stock_qty -= $l['qty'];
                    if ($p->stock_qty === 0) {
                        $p->in_stock = false;
                    }
                    $p->save();
                }
                $subtotal += $p->finalPrice() * $l['qty'];
            }
            $shipping = $this->cart->shipping($subtotal);

            $order = Order::create([
                'number' => $this->newNumber(),
                'user_id' => $userId,
                'status' => 'pending',
                'payment_method' => $method,
                'payment_status' => 'unpaid',
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'city' => $data['city'],
                'note' => $data['note'] ?? null,
                'subtotal' => $subtotal,
                'shipping' => $shipping,
                'total' => $subtotal + $shipping,
                'track_token' => Str::random(32),
            ]);
            foreach ($lines as $l) {
                $order->items()->create([
                    'product_id' => $l['product']->id,
                    'name' => $l['product']->name,
                    'sku' => $l['product']->sku,
                    'price' => $l['price'],
                    'quantity' => $l['qty'],
                    'total' => $l['price'] * $l['qty'],
                ]);
            }
            $order->log('Sipariş oluşturuldu ('.$order->methodLabel().').');

            return $order;
        });

        $this->cart->clear();

        // Kart ödemesinde e-posta ödeme onayından sonra gider.
        if ($method !== 'tiko') {
            $this->notify($order);
        }

        return $order;
    }

    public function markPaid(Order $order, string $ref, string $note): void
    {
        if ($order->payment_status === 'paid') {
            return; // dönüş + callback ikisi birden gelebilir
        }
        $order->update(['payment_status' => 'paid', 'payment_ref' => $ref, 'paid_at' => now(), 'status' => 'processing']);
        $order->log($note, ['trans_id' => $ref]);
        $this->notify($order);
    }

    public function markFailed(Order $order, string $reason): void
    {
        if ($order->payment_status === 'paid' || $order->status === 'failed') {
            return;
        }
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $p = $item->product_id ? Product::whereKey($item->product_id)->lockForUpdate()->first() : null;
                if ($p && $p->stock_qty !== null) {
                    $p->update(['stock_qty' => $p->stock_qty + $item->quantity, 'in_stock' => true]);
                }
            }
            $order->update(['status' => 'failed', 'payment_status' => 'failed']);
        });
        $order->log('Ödeme başarısız: '.$reason);
    }

    /** Panelden durum değişikliği; istenirse müşteriye e-posta gider. İptalde stok iade edilir. */
    public function changeStatus(Order $order, string $status, ?string $note, bool $notifyCustomer, ?string $tracking = null): void
    {
        $old = $order->status;
        if ($status === 'cancelled' && $old !== 'cancelled') {
            $this->restock($order);
        }
        $order->update(['status' => $status] + ($tracking !== null ? ['tracking_no' => $tracking] : []));
        $msg = 'Durum: '.(Order::STATUSES[$old] ?? $old).' → '.(Order::STATUSES[$status] ?? $status)
            .($tracking ? ' · Takip no: '.$tracking : '')
            .($note ? ' · Not: '.$note : '')
            .' ('.(auth()->user()?->name ?? 'sistem').')';
        $order->log($msg);

        if ($notifyCustomer) {
            try {
                Mail::to($order->email)->send(new \App\Mail\OrderStatusChanged($order, $note));
                $order->log('Müşteriye durum e-postası gönderildi.');
            } catch (\Throwable $e) {
                Log::error('Durum e-postası gönderilemedi', ['order' => $order->number, 'e' => $e->getMessage()]);
                $order->log('Durum e-postası gönderilemedi: '.Str::limit($e->getMessage(), 150));
            }
        }
    }

    private function restock(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $p = $item->product_id ? Product::whereKey($item->product_id)->lockForUpdate()->first() : null;
                if ($p && $p->stock_qty !== null) {
                    $p->update(['stock_qty' => $p->stock_qty + $item->quantity, 'in_stock' => true]);
                }
            }
        });
    }

    public function notify(Order $order): void
    {
        try {
            Mail::to($order->email)->send(new OrderPlaced($order));
            if ($admin = setting('order_email', setting('email'))) {
                Mail::to($admin)->send(new OrderPlaced($order, true));
            }
        } catch (\Throwable $e) {
            Log::error('Sipariş e-postası gönderilemedi', ['order' => $order->number, 'e' => $e->getMessage()]);
            $order->log('E-posta gönderilemedi: '.Str::limit($e->getMessage(), 150));
        }
    }

    private function newNumber(): string
    {
        do {
            $n = 'LHC-'.now()->format('ymd').'-'.strtoupper(Str::random(5));
        } while (Order::where('number', $n)->exists());

        return $n;
    }
}
