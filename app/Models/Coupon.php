<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $guarded = [];

    protected $casts = [
        'value' => 'decimal:2',
        'min_subtotal' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'category_ids' => 'array',
        'exclude_sale_items' => 'bool',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'bool',
    ];

    public const TYPES = [
        'percent' => 'Yüzde indirim',
        'fixed' => 'Sabit tutar indirim',
        'free_shipping' => 'Ücretsiz kargo',
    ];

    protected static function booted(): void
    {
        // Kodlar büyük harf ve boşluksuz saklanır; müşteri nasıl yazarsa yazsın eşleşir.
        static::saving(fn (Coupon $c) => $c->code = self::normalize($c->code));
    }

    public static function normalize(?string $code): string
    {
        return mb_strtoupper(preg_replace('/\s+/', '', (string) $code), 'UTF-8');
    }

    public function summary(): string
    {
        return match ($this->type) {
            'percent' => '%'.rtrim(rtrim(number_format((float) $this->value, 2, ',', ''), '0'), ',').' indirim',
            'fixed' => money($this->value).' indirim',
            default => 'Ücretsiz kargo',
        };
    }
}
