<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Panelden açılan bakım modu. Yöneticiler siteyi normal görür (değişiklikleri
 * kontrol edebilsin diye); ödeme bildirimi ve panel her zaman çalışır.
 */
class ShopMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (setting('maintenance') === '1'
            && ! $request->user()?->is_admin
            && ! $request->is('admin*', 'livewire*', 'tiko/*', 'filament*', 'up')) {
            return response()->view('maintenance', [], 503)->header('Retry-After', '3600');
        }

        return $next($request);
    }
}
