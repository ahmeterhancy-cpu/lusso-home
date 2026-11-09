<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $guarded = [];

    protected $casts = ['changes' => 'array'];

    public const ACTIONS = ['created' => 'Oluşturdu', 'updated' => 'Güncelledi', 'deleted' => 'Sildi', 'custom' => 'İşlem'];

    public const SUBJECTS = [
        Product::class => 'Ürün', Category::class => 'Kategori', Order::class => 'Sipariş', Coupon::class => 'Kupon',
        Campaign::class => 'Kampanya', Page::class => 'Sayfa', Post::class => 'Blog', Slide::class => 'Slayt',
        Redirect::class => 'Yönlendirme', User::class => 'Kullanıcı', Setting::class => 'Ayar',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, ?Model $subject, ?string $label = null, ?array $changes = null): void
    {
        try {
            self::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'subject_label' => mb_substr((string) ($label ?? ''), 0, 250),
                'changes' => $changes ?: null,
                'ip' => request()?->ip(),
            ]);
        } catch (\Throwable) {
            // Günlük yazılamadı diye asıl işlem bozulmasın.
        }
    }
}
