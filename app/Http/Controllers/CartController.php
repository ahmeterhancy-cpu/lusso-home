<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartService;
use App\Services\WishlistService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function index()
    {
        $q = $this->cart->quote();
        if ($q['coupon_error']) {
            // Kupon artık geçerli değilse (süre doldu, tutar düştü …) sessizce değil, açıklamayla düşür.
            $this->cart->setCoupon(null);
            session()->now('error', $q['coupon_error'].' Kupon sepetten kaldırıldı.');
        }

        return view('shop.cart', [
            'lines' => $q['lines'],
            'q' => $q,
            'freeMin' => (float) setting('free_shipping_min', 5000),
        ]);
    }

    public function applyCoupon(Request $request)
    {
        $code = trim((string) $request->validate(['code' => 'required|string|max:40'], ['code.required' => 'Kupon kodunu yazın.'])['code']);
        $q = app(\App\Services\PricingService::class)->quote($this->cart->lines(), $code, null, auth()->id());
        if (! $q['coupon']) {
            return back()->with('error', $q['coupon_error'] ?? 'Kupon uygulanamadı.')->withInput();
        }
        $this->cart->setCoupon($q['coupon']->code);

        return back()->with('success', '“'.$q['coupon']->code.'” kuponu uygulandı: '.$q['coupon']->summary().'.');
    }

    public function removeCoupon()
    {
        $this->cart->setCoupon(null);

        return back()->with('success', 'Kupon kaldırıldı.');
    }

    public function add(Request $request)
    {
        $data = $request->validate(['product_id' => 'required|integer', 'qty' => 'nullable|integer|min:1|max:99']);
        $product = Product::visible()->findOrFail($data['product_id']);

        if (! $product->purchasable()) {
            $msg = $product->in_stock ? 'Bu ürün için fiyat bilgisi almak üzere bizimle iletişime geçin.' : 'Bu ürün stokta yok.';

            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $msg], 422)
                : back()->with('error', $msg);
        }
        $this->cart->add($product, (int) ($data['qty'] ?? 1));

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'message' => '“'.$product->name.'” sepetinize eklendi.', 'cart' => $this->cart->summary()])
            : redirect()->route('cart')->with('success', '“'.$product->name.'” sepetinize eklendi.');
    }

    public function update(Request $request)
    {
        foreach ((array) $request->input('qty', []) as $id => $qty) {
            $this->cart->update((int) $id, (int) $qty);
        }

        return redirect()->route('cart')->with('success', 'Sepet güncellendi.');
    }

    public function remove(Request $request, int $id)
    {
        $this->cart->remove($id);

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'cart' => $this->cart->summary()])
            : redirect()->route('cart')->with('success', 'Ürün sepetten çıkarıldı.');
    }

    public function summary()
    {
        return response()->json($this->cart->summary());
    }

    public function toggleWishlist(Request $request, WishlistService $wishlist)
    {
        $data = $request->validate(['product_id' => 'required|integer|exists:products,id']);
        $added = $wishlist->toggle((int) $data['product_id']);

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'added' => $added, 'count' => count($wishlist->ids())])
            : back()->with('success', $added ? 'Ürün favorilerinize eklendi.' : 'Ürün favorilerinizden çıkarıldı.');
    }

    public function wishlist(WishlistService $wishlist)
    {
        $products = Product::visible()->with(['images', 'categories:id'])->whereIn('id', $wishlist->ids())->get();

        return view('shop.wishlist', compact('products'));
    }
}
