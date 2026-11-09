<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Oturumda tutulan sepet: [product_id => adet]. Fiyatlar her okumada
 * veritabanından gelir; oturumda fiyat saklanmaz.
 */
class CartService
{
    private const KEY = 'cart';

    /** Aynı istek içinde sepet satırlarını tekrar sorgulamamak için. */
    private ?Collection $linesMemo = null;

    private ?array $linesMemoKey = null;

    public function raw(): array
    {
        return session(self::KEY, []);
    }

    public function add(Product $product, int $qty = 1): void
    {
        $cart = $this->raw();
        $cart[$product->id] = min(99, ($cart[$product->id] ?? 0) + max(1, $qty));
        session([self::KEY => $cart]);
    }

    public function update(int $productId, int $qty): void
    {
        $cart = $this->raw();
        if ($qty <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = min(99, $qty);
        }
        session([self::KEY => $cart]);
    }

    public function remove(int $productId): void
    {
        $this->update($productId, 0);
    }

    public function clear(): void
    {
        session()->forget([self::KEY, 'coupon']);
        $this->linesMemo = null;
    }

    /** @return Collection<int, array{product: Product, qty: int, price: float, total: float}> */
    public function lines(): Collection
    {
        $raw = $this->raw();
        if (! $raw) {
            return collect();
        }
        if ($this->linesMemo !== null && $this->linesMemoKey === $raw) {
            return $this->linesMemo;
        }
        $products = Product::visible()->with(['images', 'categories:id'])->whereIn('id', array_keys($raw))->get()->keyBy('id');

        $lines = collect();
        $clean = [];
        foreach ($raw as $id => $qty) {
            $p = $products->get($id);
            if (! $p || ! $p->purchasable()) {
                continue; // satıştan kalkan ürün sepetten sessizce düşer
            }
            $clean[$id] = $qty;
            $price = $p->finalPrice();
            $lines->push(['product' => $p, 'qty' => $qty, 'price' => $price, 'total' => $price * $qty]);
        }
        if (count($clean) !== count($raw)) {
            session([self::KEY => $clean]);
        }
        $this->linesMemo = $lines;
        $this->linesMemoKey = $this->raw();

        return $lines;
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    public function subtotal(): float
    {
        return (float) $this->lines()->sum('total');
    }

    public function shipping(float $subtotal): float
    {
        return app(PricingService::class)->shipping($subtotal);
    }

    public function couponCode(): ?string
    {
        return session('coupon');
    }

    public function setCoupon(?string $code): void
    {
        $code ? session(['coupon' => $code]) : session()->forget('coupon');
    }

    /** Sepetin tam tutar dökümü (kampanya, kupon, kargo). Geçersizleşen kupon oturumdan düşer. */
    public function quote(?string $email = null, ?int $userId = null): array
    {
        $q = app(PricingService::class)->quote($this->lines(), $this->couponCode(), $email, $userId ?? auth()->id());
        $q['lines'] = $this->lines();

        return $q;
    }

    public function summary(): array
    {
        $lines = $this->lines();
        $subtotal = (float) $lines->sum('total');
        $q = app(PricingService::class)->quote($lines, $this->couponCode(), null, auth()->id());

        return [
            'count' => (int) $lines->sum('qty'),
            'subtotal' => $subtotal,
            'subtotal_text' => money($subtotal),
            'discount_text' => $q['discount'] > 0 ? '−'.money($q['discount']) : null,
            'total_text' => money($q['subtotal'] - $q['discount']),
            'items' => $lines->map(fn ($l) => [
                'id' => $l['product']->id,
                'name' => $l['product']->name,
                'url' => $l['product']->url(),
                'image' => $l['product']->mainImage(),
                'qty' => $l['qty'],
                'price_text' => money($l['price']),
            ])->values()->all(),
        ];
    }
}
