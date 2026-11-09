<?php

namespace App\Services;

use App\Models\WishlistItem;
use Illuminate\Support\Facades\Auth;

/** Favoriler: üyede veritabanında, misafirde oturumda. Giriş yapınca oturumdakiler aktarılır. */
class WishlistService
{
    private ?array $cache = null;

    public function ids(): array
    {
        if ($user = Auth::user()) {
            return $this->cache ??= WishlistItem::where('user_id', $user->id)->pluck('product_id')->all();
        }

        return session('wishlist', []);
    }

    public function has(int $productId): bool
    {
        return in_array($productId, $this->ids(), true);
    }

    /** @return bool eklendiyse true, çıkarıldıysa false */
    public function toggle(int $productId): bool
    {
        $this->cache = null;
        if ($user = Auth::user()) {
            $row = WishlistItem::where('user_id', $user->id)->where('product_id', $productId)->first();
            if ($row) {
                $row->delete();

                return false;
            }
            WishlistItem::create(['user_id' => $user->id, 'product_id' => $productId]);

            return true;
        }
        $ids = session('wishlist', []);
        if (in_array($productId, $ids, true)) {
            session(['wishlist' => array_values(array_diff($ids, [$productId]))]);

            return false;
        }
        $ids[] = $productId;
        session(['wishlist' => $ids]);

        return true;
    }

    public function mergeSessionIntoUser(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }
        foreach (session('wishlist', []) as $id) {
            WishlistItem::firstOrCreate(['user_id' => $user->id, 'product_id' => $id]);
        }
        session()->forget('wishlist');
    }
}
