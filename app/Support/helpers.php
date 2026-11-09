<?php

use App\Models\Setting;

if (! function_exists('media_url')) {
    /** public/medya altındaki dosyanın adresi (dış adresse olduğu gibi). */
    function media_url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('medya/'.ltrim($path, '/'));
    }
}

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        $all = Setting::map();

        return isset($all[$key]) && $all[$key] !== '' ? $all[$key] : $default;
    }
}

if (! function_exists('icon')) {
    /** Çizgi simgeleri (eski temadaki simple-line-icons karşılıkları). */
    function icon(string $name, string $class = ''): \Illuminate\Support\HtmlString
    {
        $paths = [
            'search' => '<circle cx="11" cy="11" r="7"/><path d="m20.5 20.5-4.5-4.5"/>',
            'heart' => '<path d="M12 20.5s-7.5-4.6-9.2-9.4C1.6 7.6 3.9 4 7.4 4c2 0 3.6 1.1 4.6 2.7C13 5.1 14.6 4 16.6 4c3.5 0 5.8 3.6 4.6 7.1C19.5 15.9 12 20.5 12 20.5z"/>',
            'cart' => '<path d="M2.5 3.5h3l2.4 11.2a1.5 1.5 0 0 0 1.5 1.2h8.4a1.5 1.5 0 0 0 1.5-1.1l1.8-7.3H6.6"/><circle cx="10" cy="20" r="1.3"/><circle cx="17.5" cy="20" r="1.3"/>',
            'eye' => '<path d="M1.5 12S5.5 5 12 5s10.5 7 10.5 7-4 7-10.5 7S1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3"/>',
            'down' => '<path d="m5 9 7 7 7-7"/>',
            'left' => '<path d="m15 5-7 7 7 7"/>',
            'right' => '<path d="m9 5 7 7-7 7"/>',
            'up' => '<path d="m5 15 7-7 7 7"/>',
            'arrow-left' => '<path d="M22 12H3m4-4-4 4 4 4"/>',
            'arrow-right' => '<path d="M2 12h19m-4-4 4 4-4 4"/>',
            'home' => '<path d="M3 11 12 3l9 8v10h-6v-6H9v6H3z"/>',
            'zoom' => '<circle cx="11" cy="11" r="7"/><path d="m20.5 20.5-4.5-4.5M11 8v6M8 11h6"/>',
            'refresh' => '<path d="M20 11a8 8 0 0 0-14.6-4.5M4 4v3h3M4 13a8 8 0 0 0 14.6 4.5M20 20v-3h-3"/>',
            'truck' => '<path d="M1.5 5.5h12v11h-12zM13.5 9.5h4.5l3.5 3.5v3.5h-8"/><circle cx="6" cy="18" r="1.8"/><circle cx="17" cy="18" r="1.8"/>',
            'diamond' => '<path d="M6 3.5h12l4 5.5-10 11.5L2 9z"/><path d="M2 9h20M9 3.5 12 20.5 15 3.5"/>',
            'magic' => '<path d="m3 21 12-12M14 4l1-2 1 2 2 1-2 1-1 2-1-2-2-1zM19 10l.7-1.3L21 8l-1.3-.7L19 6l-.7 1.3L17 8l1.3.7zM8 3l.5-1 .5 1 1 .5-1 .5L8.5 5 8 4l-1-.5z"/>',
            'support' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/><path d="m5.6 5.6 3.6 3.6m5.6 5.6 3.6 3.6m0-12.8-3.6 3.6m-5.6 5.6-3.6 3.6"/>',
            'card' => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/>',
            'lock' => '<rect x="4" y="10.5" width="16" height="10" rx="1.5"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/>',
            'login' => '<path d="M10 3.5h9.5v17H10M3 12h11m-3.5-3.5L14 12l-3.5 3.5"/>',
            'paper-plane' => '<path d="M22 2 2 10.5l8 3 3 8.5z"/><path d="M22 2 10 13.5"/>',
            'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
            'close' => '<path d="M5 5l14 14M19 5 5 19"/>',
            'check' => '<path d="m4 12.5 5 5L20 6.5"/>',
            'cart-x' => '<path d="M2.5 3.5h3l2.4 11.2a1.5 1.5 0 0 0 1.5 1.2h8.4a1.5 1.5 0 0 0 1.5-1.1l1.8-7.3H6.6"/><circle cx="10" cy="20" r="1.3"/><circle cx="17.5" cy="20" r="1.3"/><path d="m11.5 8.5 4 4m0-4-4 4"/>',
            'facebook' => '<path class="f" d="M14 8h3V4.5h-3c-2.5 0-4 1.7-4 4V11H7.5v3.5H10V22h3.5v-7.5h3l.5-3.5h-3.5V8.8c0-.5.3-.8.5-.8z"/>',
            'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".6"/>',
            'whatsapp' => '<path d="M3.5 20.5 5 16a8.5 8.5 0 1 1 3.2 3.1z"/><path d="M9 8.5c0 3.5 3 6.5 6.5 6.5l1-1.5-2-1-1 .8c-1-.4-2.4-1.8-2.8-2.8l.8-1-1-2z"/>',
            'phone' => '<path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A17 17 0 0 1 3 5a2 2 0 0 1 2-2"/>',
            'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
            'pin' => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
            'shield' => '<path d="M12 3 4.5 6v5.5c0 4.6 3.2 8.3 7.5 9.5 4.3-1.2 7.5-4.9 7.5-9.5V6z"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
            'image' => '<rect x="3" y="4" width="18" height="16" rx="1"/><circle cx="9" cy="10" r="1.8"/><path d="m3 18 6-5 4 3 3-2 5 4"/>',
        ];
        $p = $paths[$name] ?? '';
        $solid = in_array($name, ['facebook'], true) ? ' solid' : '';

        return new \Illuminate\Support\HtmlString('<svg class="icon'.$solid.($class ? ' '.$class : '').'" viewBox="0 0 24 24" aria-hidden="true">'.$p.'</svg>');
    }
}

if (! function_exists('site')) {
    /** Panelden düzenlenen site içeriği (App\Support\SiteContent). */
    function site(string $key, mixed $default = null): mixed
    {
        return \App\Support\SiteContent::get($key, $default);
    }
}

if (! function_exists('money')) {
    /** Eski sitedeki biçim: "1900 ₺" (binlik ayırıcı yok, kuruş yalnız varsa). */
    function money(float|int|string|null $value): string
    {
        $v = (float) $value;
        $txt = fmod($v, 1.0) == 0.0 ? number_format($v, 0, ',', '') : number_format($v, 2, ',', '');

        return $txt.' ₺';
    }
}
