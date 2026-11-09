<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $guarded = [];

    protected $casts = ['paid_at' => 'datetime', 'subtotal' => 'decimal:2', 'shipping' => 'decimal:2', 'total' => 'decimal:2'];

    public const STATUSES = [
        'pending' => 'Beklemede',
        'processing' => 'Hazırlanıyor',
        'shipped' => 'Kargoda',
        'completed' => 'Tamamlandı',
        'cancelled' => 'İptal edildi',
        'failed' => 'Ödeme başarısız',
    ];

    public const METHODS = [
        'tiko' => 'Kredi / Banka Kartı (Tiko)',
        'havale' => 'Havale / EFT',
        'kapida' => 'Kapıda Ödeme',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->latest();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->payment_method] ?? $this->payment_method;
    }

    public function log(string $message, array $data = []): void
    {
        $this->events()->create(['message' => $message, 'data' => $data ?: null]);
    }
}
