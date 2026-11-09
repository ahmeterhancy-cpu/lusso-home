<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'in_stock' => 'bool',
        'is_active' => 'bool',
        'is_featured' => 'bool',
        'published_at' => 'datetime',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function scopeVisible(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function url(): string
    {
        return route('product', $this->slug);
    }

    private ?array $categoryIdsMemo = null;

    /** Ürünün kategori kimlikleri (ilişki yüklüyse sorgusuz). */
    public function categoryIds(): array
    {
        if ($this->categoryIdsMemo === null) {
            $this->categoryIdsMemo = $this->relationLoaded('categories')
                ? $this->categories->pluck('id')->all()
                : ($this->exists ? $this->categories()->pluck('categories.id')->all() : []);
        }

        return $this->categoryIdsMemo;
    }

    /** Ürüne uygulanan en yüksek kategori kampanyası (yoksa null). */
    public function campaign(): ?array
    {
        if ((float) $this->price <= 0) {
            return null;
        }
        $best = null;
        foreach (Campaign::active() as $c) {
            if ($c['type'] === 'category_percent' && $c['value'] > 0 && array_intersect($c['cats'], $this->categoryIds())) {
                if (! $best || $c['value'] > $best['value']) {
                    $best = $c;
                }
            }
        }

        return $best;
    }

    /** Satış fiyatı: normal, panelden girilen indirimli fiyat ve kategori kampanyasının en düşüğü. */
    public function finalPrice(): float
    {
        $price = (float) $this->price;
        $sale = (float) $this->sale_price;
        $final = $sale > 0 && $sale < $price ? $sale : $price;
        if ($c = $this->campaign()) {
            $final = min($final, round($price * (1 - $c['value'] / 100)));
        }

        return $final;
    }

    public function onSale(): bool
    {
        return $this->finalPrice() < (float) $this->price;
    }

    /** Eski sitedeki gibi: fiyatı olmayan ürün satın alınamaz, "Devamını oku" gösterilir. */
    public function purchasable(): bool
    {
        return $this->finalPrice() > 0 && $this->in_stock;
    }

    public function mainImage(): ?string
    {
        $img = $this->relationLoaded('images') ? $this->images->first() : $this->images()->first();

        return $img ? media_url($img->path) : null;
    }

    public function hoverImage(): ?string
    {
        $img = $this->relationLoaded('images') ? $this->images->get(1) : $this->images()->skip(1)->first();

        return $img ? media_url($img->path) : null;
    }
}
