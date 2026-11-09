<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// is_admin yalnız panelden atanır; vitrin denetleyicileri alanları tek tek seçiyor (only()).
#[Fillable(['name', 'email', 'password', 'phone', 'address', 'city', 'is_admin', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'bool',
        ];
    }

    /** Personel rolleri ve açabildikleri panel bölümleri. */
    public const ROLES = [
        'admin' => ['label' => 'Yönetici (tam yetki)', 'areas' => ['*']],
        'manager' => ['label' => 'Mağaza müdürü', 'areas' => ['sales', 'reports', 'catalog', 'marketing', 'content']],
        'orders' => ['label' => 'Sipariş sorumlusu', 'areas' => ['sales']],
        'catalog' => ['label' => 'Ürün / içerik editörü', 'areas' => ['catalog', 'content']],
    ];

    public const AREAS = [
        'sales' => 'Siparişler, üyeler, mesajlar', 'reports' => 'Raporlar', 'catalog' => 'Ürünler, kategoriler, fiyat/stok',
        'marketing' => 'Kupon, kampanya, yönlendirme', 'content' => 'Sayfalar, blog, slider, site görünümü',
        'settings' => 'Mağaza ayarları, ödeme, personel, işlem günlüğü',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_admin;
    }

    /** Rolü boş olan yönetici eski kayıtlarla uyum için tam yetkili sayılır. */
    public function roleKey(): ?string
    {
        return $this->is_admin ? ($this->role ?: 'admin') : null;
    }

    public function canArea(string $area): bool
    {
        $role = $this->roleKey();
        if (! $role) {
            return false;
        }
        $areas = self::ROLES[$role]['areas'] ?? [];

        return in_array('*', $areas, true) || in_array($area, $areas, true);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
