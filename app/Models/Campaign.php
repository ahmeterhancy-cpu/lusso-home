<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Campaign extends Model
{
    protected $guarded = [];

    protected $casts = [
        'value' => 'decimal:2',
        'min_subtotal' => 'decimal:2',
        'category_ids' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'bool',
    ];

    public const TYPES = [
        'category_percent' => 'Kategoride yüzde indirim (fiyatlara yansır)',
        'cart_percent' => 'Sepette yüzde indirim (tutar üzerine)',
        'cart_fixed' => 'Sepette sabit tutar indirim (tutar üzerine)',
        'free_shipping' => 'Ücretsiz kargo (tutar üzerine)',
    ];

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget('active-campaigns');
        static::saved($flush);
        static::deleted($flush);
    }

    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function isRunning(): bool
    {
        return $this->is_active && (! $this->starts_at || $this->starts_at->isPast()) && (! $this->ends_at || $this->ends_at->isFuture());
    }

    /**
     * Yürürlükteki kampanyalar, düz dizi olarak (önbellekte Eloquent nesnesi tutulmaz).
     * Kategori kampanyalarında alt kategoriler de dahil edilmiş kimlikler gelir.
     * Önbellek 5 dk: başlangıç/bitiş saatleri en geç 5 dk gecikmeyle devreye girer.
     */
    public static function active(): array
    {
        return Cache::remember('active-campaigns', 300, function () {
            return self::running()->orderByDesc('priority')->get()->map(function (Campaign $c) {
                $ids = [];
                foreach ((array) $c->category_ids as $id) {
                    $cat = Category::find($id);
                    $ids = array_merge($ids, $cat ? $cat->descendantIds() : []);
                }

                return [
                    'id' => $c->id, 'name' => $c->name, 'type' => $c->type, 'value' => (float) $c->value,
                    'min' => (float) $c->min_subtotal, 'cats' => array_values(array_unique($ids)),
                    'banner' => $c->banner_text, 'badge' => $c->badge_text,
                ];
            })->all();
        });
    }
}
