<?php

namespace App\Filament\Support;

/**
 * Panel ekranını rol tabanlı kısıtlar. Kullanan sınıf `protected static string $area`
 * tanımlar (sales, reports, catalog, marketing, content, settings). Bkz. User::ROLES.
 */
trait RestrictedArea
{
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->canArea(static::$area);
    }
}
