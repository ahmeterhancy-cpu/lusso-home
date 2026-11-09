<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use \App\Concerns\LogsActivity;

    protected $guarded = [];

    protected $casts = ['is_active' => 'bool', 'in_menu' => 'bool', 'is_trend' => 'bool'];

    protected static function booted(): void
    {
        $flush = fn () => \Illuminate\Support\Facades\Cache::forget('menu-tree');
        static::saved($flush);
        static::deleted($flush);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort')->orderBy('name');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function url(): string
    {
        return route('category', $this->slug);
    }

    public function imageUrl(): ?string
    {
        return $this->image ? media_url($this->image) : null;
    }

    /** Kendisi + tüm alt kategorilerin kimlikleri. */
    public function descendantIds(): array
    {
        $ids = [$this->id];
        $level = [$this->id];
        while ($level) {
            $level = self::whereIn('parent_id', $level)->pluck('id')->all();
            $ids = array_merge($ids, $level);
        }

        return $ids;
    }

    /** Kökten kendisine kadar zincir (breadcrumb). */
    public function trail(): array
    {
        $trail = [];
        $c = $this;
        while ($c) {
            array_unshift($trail, $c);
            $c = $c->parent;
        }

        return $trail;
    }
}
