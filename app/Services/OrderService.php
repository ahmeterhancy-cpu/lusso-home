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

        $couponCode = $this->cart->couponCode();

        $order = DB::transaction(function () use ($lines, $data, $method, $userId, $couponCode) {
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
            }

            // Tutar, müşterinin sepette gördüğü hesapla aynı servisle hesaplanır.
            $q = app(PricingService::class)->quote($lines, $couponCode, $data['email'], $userId, $data['city'] ?? null);
            if ($couponCode && ! $q['coupon']) {
                throw new RuntimeException(($q['coupon_error'] ?? 'Kupon geçersiz.').' Kuponu kaldırıp tekrar deneyin.');
            }
            if ($q['coupon']) {
                // Kullanım sınırı yarışına karşı kilitle ve yeniden say.
                $c = \App\Models\Coupon::whereKey($q['coupon']->id)->lockForUpdate()->first();
                if ($c->usage_limit !== null && $c->used_count >= $c->usage_limit) {
                    throw new RuntimeException('Bu kuponun kullanım hakkı az önce doldu. Kuponu kaldırıp tekrar deneyin.');
                }
                $c->increment('used_count');
            }

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
                'subtotal' => $q['subtotal'],
                'discount' => $q['discount'],
                'coupon_code' => $q['coupon']?->code,
                'discount_lines' => $q['discounts'] ?: null,
                'shipping' => $q['shipping'],
                'total' => $q['total'],
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
            $order->log('Sipariş oluşturuldu ('.$order->methodLabel().').'
                .($q['discounts'] ? ' İndirimler: '.collect($q['discounts'])->map(fn ($d) => $d['label'].' −'.money($d['amount']))->join(', ') : ''));

            return $order;
        });

        $this->cart->clear();

        // Kart ödemesinde e-posta ödeme onayından sonra gider.
        if ($method !== 'tiko') {
            $this->notify($order);
        }

        return $order;
    }

    /**
     * Panelden elle sipariş (telefon, mağaza, WhatsApp). Stok düşer; fiyatlar formda
     * değiştirilebilir. $data['items'] = [['product_id' => .., 'quantity' => .., 'price' => ..], ...]
     */
    public function createManual(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $items = [];
            $subtotal = 0.0;
            foreach ($data['items'] as $row) {
                $p = Product::whereKey($row['product_id'])->lockForUpdate()->firstOrFail();
                $qty = max(1, (int) $row['quantity']);
                if ($p->stock_qty !== null) {
                    if ($p->stock_qty < $qty) {
                        throw new RuntimeException("“{$p->name}” için yeterli stok yok (kalan: {$p->stock_qty}).");
                    }
                    $p->stock_qty -= $qty;
                    $p->in_stock = $p->stock_qty > 0;
                    $p->save();
                }
                $price = (float) ($row['price'] ?? $p->finalPrice());
                $items[] = ['product_id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'price' => $price, 'quantity' => $qty, 'total' => $price * $qty];
                $subtotal += $price * $qty;
            }
            $discount = min($subtotal, max(0, (float) ($data['discount'] ?? 0)));
            $shipping = max(0, (float) ($data['shipping'] ?? 0));
            $paid = ! empty($data['paid']);

            $order = Order::create([
                'number' => $this->newNumber(),
                'source' => 'panel',
                'user_id' => $data['user_id'] ?? null,
                'status' => $data['status'] ?? ($paid ? 'processing' : 'pending'),
                'payment_method' => $data['payment_method'],
                'payment_status' => $paid ? 'paid' : 'unpaid',
                'paid_at' => $paid ? now() : null,
                'payment_ref' => $paid ? 'panel' : null,
                'first_name' => $data['first_name'], 'last_name' => $data['last_name'],
                'email' => $data['email'] ?: 'magaza@lussohomeconcept.com', 'phone' => $data['phone'],
                'address' => $data['address'] ?: 'Mağazadan teslim', 'city' => $data['city'] ?: 'Girne',
                'note' => $data['note'] ?? null, 'admin_note' => $data['admin_note'] ?? null,
                'subtotal' => $subtotal, 'discount' => $discount,
                'discount_lines' => $discount > 0 ? [['label' => 'Mağaza indirimi', 'amount' => $discount]] : null,
                'shipping' => $shipping, 'total' => $subtotal - $discount + $shipping,
                'track_token' => Str::random(32),
            ]);
            foreach ($items as $it) {
                $order->items()->create($it);
            }
            $order->log('Sipariş panelden oluşturuldu ('.(auth()->user()?->name ?? 'yönetici').').');

            return $order;
        });
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
        $this->restock($order);
        $order->update(['status' => 'failed', 'payment_status' => 'failed']);
        $order->log('Ödeme başarısız: '.$reason);
    }

    /** Panelden durum değişikliği; istenirse müşteriye e-posta gider. İptalde stok iade edilir. */
    public function changeStatus(Order $order, string $status, ?string $note, bool $notifyCustomer, ?string $tracking = null): void
    {
        $old = $order->status;
        // Başarısız siparişin stoğu/kuponu zaten iade edildi; ikinci kez iade etme.
        if ($status === 'cancelled' && ! in_array($old, ['cancelled', 'failed'], true)) {
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

    /** Stoğu ve kupon kullanım hakkını geri verir (iptal / ödeme başarısız). */
    private function restock(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $p = $item->product_id ? Product::whereKey($item->product_id)->lockForUpdate()->first() : null;
                if ($p && $p->stock_qty !== null) {
                    $p->update(['stock_qty' => $p->stock_qty + $item->quantity, 'in_stock' => true]);
                }
            }
            if ($order->coupon_code) {
                \App\Models\Coupon::where('code', $order->coupon_code)->where('used_count', '>', 0)->decrement('used_count');
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
