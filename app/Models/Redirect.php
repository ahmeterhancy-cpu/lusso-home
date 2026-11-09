<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Redirect extends Model
{
    use \App\Concerns\LogsActivity;

    protected $guarded = [];

    protected $casts = ['is_active' => 'bool', 'last_hit_at' => 'datetime'];

    protected static function booted(): void
    {
        static::saving(fn (Redirect $r) => $r->from_path = self::normalize($r->from_path));
        $flush = fn () => Cache::forget('redirect-map');
        static::saved($flush);
        static::deleted($flush);
    }

    /** "/eski-sayfa/?a=1" → "eski-sayfa" (baş/son eğik çizgisiz, sorgusuz, küçük harf). */
    public static function normalize(?string $path): string
    {
        $path = (string) parse_url((string) $path, PHP_URL_PATH);

        return mb_strtolower(trim(rawurldecode($path), '/'), 'UTF-8');
    }

    /** @return array<string, array{0: string, 1: int, 2: int}> yol => [hedef, durum kodu, id] */
    public static function map(): array
    {
        return Cache::rememberForever('redirect-map', fn () => self::where('is_active', true)->get()
            ->mapWithKeys(fn ($r) => [$r->from_path => [$r->to_url, $r->status, $r->id]])->all());
    }
}
