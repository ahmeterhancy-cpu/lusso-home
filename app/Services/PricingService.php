<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Support\Collection;

/**
 * Sepet tutarı: ürün fiyatları (kategori kampanyaları dahil) → sepet kampanyası
 * → kupon → kargo. Hem sepet/kasa ekranı hem sipariş oluşturma bunu kullanır,
 * böylece müşterinin gördüğü tutar ile tahsil edilen tutar hep aynıdır.
 */
class PricingService
{
    /**
     * @param  Collection<int, array{product: \App\Models\Product, qty: int, price: float, total: float}>  $lines
     * @return array{subtotal: float, discounts: array<int, array{label: string, amount: float}>, discount: float, shipping: float, total: float, coupon: ?Coupon, coupon_error: ?string, free_shipping: bool}
     */
    public function quote(Collection $lines, ?string $couponCode = null, ?string $email = null, ?int $userId = null): array
    {
        $subtotal = round((float) $lines->sum('total'), 2);
        $discounts = [];
        $freeShipping = false;

        // 1) Sepet kampanyaları: en çok kazandıran tek indirim + ücretsiz kargo
        $bestCart = null;
        foreach (Campaign::active() as $c) {
            if ($subtotal <= 0 || $subtotal < $c['min']) {
                continue;
            }
            if ($c['type'] === 'free_shipping') {
                $freeShipping = true;
            } elseif (in_array($c['type'], ['cart_percent', 'cart_fixed'], true)) {
                $amount = $c['type'] === 'cart_percent' ? round($subtotal * $c['value'] / 100, 2) : min($c['value'], $subtotal);
                if (! $bestCart || $amount > $bestCart['amount']) {
                    $bestCart = ['label' => $c['name'], 'amount' => $amount];
                }
            }
        }
        if ($bestCart && $bestCart['amount'] > 0) {
            $discounts[] = $bestCart;
        }

        // 2) Kupon
        $coupon = null;
        $error = null;
        if ($couponCode) {
            [$coupon, $error, $amount, $couponFreeShip] = $this->applyCoupon($couponCode, $lines, $subtotal, $email, $userId);
            if ($coupon) {
                if ($amount > 0) {
                    $discounts[] = ['label' => 'Kupon '.$coupon->code, 'amount' => $amount];
                }
                $freeShipping = $freeShipping || $couponFreeShip;
            }
        }

        $discount = min($subtotal, round(array_sum(array_column($discounts, 'amount')), 2));
        $shipping = $this->shipping($subtotal, $freeShipping);

        return [
            'subtotal' => $subtotal,
            'discounts' => $discounts,
            'discount' => $discount,
            'shipping' => $shipping,
            'total' => round($subtotal - $discount + $shipping, 2),
            'coupon' => $coupon,
            'coupon_error' => $error,
            'free_shipping' => $freeShipping,
        ];
    }

    public function shipping(float $subtotal, bool $free = false): float
    {
        if ($subtotal <= 0 || $free) {
            return 0;
        }
        $min = (float) setting('free_shipping_min', 5000);
        $fee = (float) setting('shipping_fee', 0);

        return $subtotal >= $min ? 0 : $fee;
    }

    /** @return array{0: ?Coupon, 1: ?string, 2: float, 3: bool} */
    public function applyCoupon(string $code, Collection $lines, float $subtotal, ?string $email, ?int $userId): array
    {
        $coupon = Coupon::where('code', Coupon::normalize($code))->first();
        $fail = fn (string $msg) => [null, $msg, 0.0, false];

        if (! $coupon || ! $coupon->is_active) {
            return $fail('Bu kupon kodu geçerli değil.');
        }
        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return $fail('Bu kupon '.$coupon->starts_at->format('d.m.Y').' tarihinden itibaren geçerli.');
        }
        if ($coupon->ends_at && $coupon->ends_at->isPast()) {
            return $fail('Bu kuponun süresi dolmuş.');
        }
        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            return $fail('Bu kuponun kullanım hakkı dolmuş.');
        }
        if ($coupon->per_customer_limit !== null && ($email || $userId)) {
            $used = Order::where('coupon_code', $coupon->code)->whereNotIn('status', ['cancelled', 'failed'])
                ->where(fn ($q) => $q->when($email, fn ($w) => $w->where('email', $email))->when($userId, fn ($w) => $w->orWhere('user_id', $userId)))
                ->count();
            if ($used >= $coupon->per_customer_limit) {
                return $fail('Bu kuponu kullanım hakkınızı doldurdunuz.');
            }
        }

        // Kuponun geçerli olduğu ürünler
        $allowed = null;
        if ($coupon->category_ids) {
            $allowed = [];
            foreach ($coupon->category_ids as $id) {
                $allowed = array_merge($allowed, Category::find($id)?->descendantIds() ?? []);
            }
        }
        $eligible = $lines->filter(function ($l) use ($allowed, $coupon) {
            if ($coupon->exclude_sale_items && $l['product']->onSale()) {
                return false;
            }

            return $allowed === null || array_intersect($allowed, $l['product']->categoryIds());
        });
        $eligibleSum = (float) $eligible->sum('total');

        if ($eligibleSum <= 0) {
            return $fail('Sepetinizde bu kuponun geçerli olduğu ürün yok.');
        }
        if ($coupon->min_subtotal && $subtotal < (float) $coupon->min_subtotal) {
            return $fail('Bu kupon '.money($coupon->min_subtotal).' ve üzeri alışverişlerde geçerli.');
        }

        $amount = match ($coupon->type) {
            'percent' => round($eligibleSum * (float) $coupon->value / 100, 2),
            'fixed' => min((float) $coupon->value, $eligibleSum),
            default => 0.0,
        };
        if ($coupon->max_discount && $amount > (float) $coupon->max_discount) {
            $amount = (float) $coupon->max_discount;
        }

        return [$coupon, null, $amount, $coupon->type === 'free_shipping'];
    }
}
