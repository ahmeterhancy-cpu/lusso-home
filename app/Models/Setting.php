<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $guarded = [];

    /** Tüm ayarlar düz dizi olarak. Önbelleğe model değil dizi yazılır. */
    public static function map(): array
    {
        return Cache::rememberForever('settings', fn () => self::query()->pluck('value', 'key')->all());
    }

    public static function put(array $values): void
    {
        foreach ($values as $k => $v) {
            self::updateOrCreate(['key' => $k], ['value' => is_array($v) ? json_encode($v) : $v]);
        }
        Cache::forget('settings');
    }
}
