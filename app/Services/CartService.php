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
        session()->forget(self::KEY);
    }

    /** @return Collection<int, array{product: Product, qty: int, price: float, total: float}> */
    public function lines(): Collection
    {
        $raw = $this->raw();
        if (! $raw) {
            return collect();
        }
        $products = Product::visible()->with('images')->whereIn('id', array_keys($raw))->get()->keyBy('id');

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
        if ($subtotal <= 0) {
            return 0;
        }
        $free = (float) setting('free_shipping_min', 5000);
        $fee = (float) setting('shipping_fee', 0);

        return $subtotal >= $free ? 0 : $fee;
    }

    public function summary(): array
    {
        $lines = $this->lines();
        $subtotal = (float) $lines->sum('total');

        return [
            'count' => (int) $lines->sum('qty'),
            'subtotal' => $subtotal,
            'subtotal_text' => money($subtotal),
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
