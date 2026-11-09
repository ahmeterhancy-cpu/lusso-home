<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class XmlFeed extends Model
{
    use \App\Concerns\LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'category_ids' => 'array',
        'include_children' => 'bool',
        'in_stock_only' => 'bool',
        'priced_only' => 'bool',
        'is_active' => 'bool',
        'last_hit_at' => 'datetime',
    ];

    public const FORMATS = [
        'google' => 'Google Merchant / Meta (Facebook, Instagram) / TikTok — RSS 2.0',
        'genel' => 'Genel ürün XML\'i (bayi, pazar yeri entegrasyonu)',
    ];

    public function url(): string
    {
        return route('xml.feed', $this->slug).($this->token ? '?key='.$this->token : '');
    }

    /** Akışa giren kategori kimlikleri; boş dizi = kategori sınırı yok. */
    public function resolvedCategoryIds(): array
    {
        $ids = array_values(array_filter(array_map('intval', (array) $this->category_ids)));
        if (! $ids || ! $this->include_children) {
            return $ids;
        }
        $all = $ids;
        $level = $ids;
        while ($level) {
            $level = Category::whereIn('parent_id', $level)->pluck('id')->all();
            $all = array_merge($all, $level);
        }

        return array_values(array_unique($all));
    }

    /** Akıştaki ürünler (fiyat filtresi ürün düzeyinde, finalPrice ile ayrıca uygulanır). */
    public function productQuery(): Builder
    {
        $q = Product::visible();
        if ($ids = $this->resolvedCategoryIds()) {
            $q->whereHas('categories', fn (Builder $c) => $c->whereIn('categories.id', $ids));
        }
        if ($this->in_stock_only) {
            $q->where('in_stock', true);
        }
        if ($this->priced_only) {
            $q->where('price', '>', 0);
        }

        return $q;
    }
}
