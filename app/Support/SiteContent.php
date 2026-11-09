<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Panelden düzenlenen site içeriği (ana sayfa blokları, üst/alt bilgi, menü).
 * Tek bir `site_content` ayarında JSON olarak durur; eksik alan varsayılandan gelir,
 * böylece panelde hiç dokunulmamış site eski sitenin birebir aynısı görünür.
 */
class SiteContent
{
    /** Alt bilgi iletişim metninin varsayılanı (eski sitedeki şubeler). */
    public const FOOTER_CONTACT = '<p>Şube 1: Neopark no 6, şht Orhan Durusoy sokak, Girne (Eziç Premierin karşısında)<br>Tel: 0392 815 82 83</p><p class="gap">Şube 2 : Merit Royal Diamond Hotel<br>Tel: 0539 109 99 00</p><p>Şube 3 : The Arkın Iskele Hotel<br>Tel: 0539 109 99 00<br>E-Mail:info@lussohomeconcept.com</p>';

    private static ?array $cache = null;

    public const ICONS = [
        'truck' => 'Kamyon (kargo)', 'diamond' => 'Elmas', 'magic' => 'Sihirli değnek', 'support' => 'Destek',
        'card' => 'Kart (ödeme)', 'lock' => 'Kilit', 'heart' => 'Kalp', 'phone' => 'Telefon', 'check' => 'Onay',
        'home' => 'Ev', 'user' => 'Kişi', 'image' => 'Görsel',
    ];

    public static function defaults(): array
    {
        return [
            'header_lines' => ['Evinizi küçük dokunuşlarla daha güzel,', 'daha şık ve daha lükse ulaştırın…', '#MyLussoLife'],
            'topbar_links' => [
                ['label' => 'Ana Sayfa', 'url' => '/'], ['label' => 'Hesabım', 'url' => '/hesabim'],
                ['label' => 'Blog', 'url' => '/blog'], ['label' => 'Sepetim', 'url' => '/cart'],
            ],
            'menu_extra' => [],
            'sections' => [
                'slider' => true, 'trio' => true, 'trend' => true, 'latest' => true, 'info' => true,
                'featured' => true, 'promo' => true, 'strip' => true, 'grid' => true, 'stores' => true,
            ],
            'subtitle' => '#MyLussoLife',
            'trend_title' => 'Trend Kategoriler',
            'latest_title' => 'Son Eklenen Ürünler',
            'latest_count' => 8,
            'featured_title' => 'Öne Çıkan Ürünler',
            'featured_count' => 12,
            'grid_count' => 12,
            'trio' => [
                ['image' => null, 'default' => 'img/banner-1.jpg', 'link' => '/product-category/mackenzie-childs'],
                ['image' => null, 'default' => 'img/banner-2.jpg', 'link' => '/product-category/sofra-grubu'],
                ['image' => null, 'default' => 'img/banner-3.jpg', 'link' => '/product-category/aksesuar'],
            ],
            'info' => [
                ['icon' => 'truck', 'title' => 'Ücretsiz Kargo', 'text' => ':kargo TL ve üzeri alışverişlerinizde kargo bedava.'],
                ['icon' => 'diamond', 'title' => 'Mükemmel Ürünler', 'text' => '#MyLussoLife'],
                ['icon' => 'magic', 'title' => 'Evinizin Modası', 'text' => 'Lusso Home Concept'],
            ],
            'promo' => [
                'image' => null, 'default' => 'img/promo.jpg', 'kicker' => 'EVİNİZİN MODASINI YAKINDAN TAKİP EDİN',
                'title' => 'EVİNİZİN TERCİHİ', 'text' => 'Trend modellerimizi görmek için',
                'button' => 'Koleksiyonumuzu İnceleyin.', 'link' => '/shop',
            ],
            'strip' => [
                ['icon' => 'truck', 'text' => 'Hızlı teslimat'],
                ['icon' => 'support', 'text' => 'Destek Paneli'],
                ['icon' => 'card', 'text' => 'Güvenli Ödeme'],
            ],
            'footer_columns' => [
                ['title' => 'Hızlı Linkler', 'links' => [
                    ['label' => 'Ana Sayfa', 'url' => '/'], ['label' => 'Hakkımızda', 'url' => '/hakkimizda'],
                    ['label' => 'İletişim', 'url' => '/iletisim'], ['label' => 'Üye Ol', 'url' => '/hesabim?action=register'],
                    ['label' => 'Giriş Yap!', 'url' => '/hesabim'], ['label' => 'Sipariş Takibi', 'url' => '/siparis-takibi'],
                ]],
                ['title' => 'Kurallar', 'links' => [
                    ['label' => 'Üyelik', 'url' => '/uyelik'], ['label' => 'Alışveriş', 'url' => '/alisveris'],
                    ['label' => 'Teslimat', 'url' => '/teslimat-kargo'], ['label' => 'İade Politikası', 'url' => '/iade-politikasi'],
                    ['label' => 'Güvenli Alışveriş', 'url' => '/guvenli-alisveris'], ['label' => 'Gizlilik Sözleşmesi', 'url' => '/gizlilik-sozlesmesi'],
                ]],
                ['title' => 'Kategoriler', 'links' => [
                    ['label' => 'Aksesuar', 'url' => '/product-category/aksesuar'], ['label' => 'Kitap', 'url' => '/product-category/kitap'],
                    ['label' => 'Mackenzie Childs', 'url' => '/product-category/mackenzie-childs'], ['label' => 'Sofra Grubu', 'url' => '/product-category/sofra-grubu'],
                    ['label' => 'Tekstil', 'url' => '/product-category/tekstil'],
                ]],
            ],
            // Şube konumları (iletişim haritası). Etiket, iletişim metnindeki "Şube 1" vb. ile eşleşir.
            'store_pins' => [
                ['label' => 'Şube 1', 'city' => 'Girne', 'lat' => '35.3279792', 'lng' => '33.3087578'],
                ['label' => 'Şube 2', 'city' => 'Alsancak', 'lat' => '35.3542', 'lng' => '33.2102'],
                ['label' => 'Şube 3', 'city' => 'İskele', 'lat' => '35.2675', 'lng' => '33.9153'],
            ],
            'contact_title' => 'İletşim',
            'copyright' => '© :yil Lusso Home Concept. All rights reserved',
            'credit' => 'Web Site By Amesis Dijital Ajans',
            'credit_url' => 'https://amesis.com.tr',
        ];
    }

    public static function all(): array
    {
        if (self::$cache === null) {
            $saved = json_decode((string) (Setting::map()['site_content'] ?? ''), true) ?: [];
            self::$cache = array_replace(self::defaults(), $saved);
            self::$cache['sections'] = array_replace(self::defaults()['sections'], (array) ($saved['sections'] ?? []));
            self::$cache['promo'] = array_replace(self::defaults()['promo'], (array) ($saved['promo'] ?? []));
        }

        return self::$cache;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return data_get(self::all(), $key, $default);
    }

    public static function show(string $section): bool
    {
        return (bool) (self::all()['sections'][$section] ?? true);
    }

    /** Yüklenen görsel yoksa eski sitedeki varsayılan görsel. */
    public static function image(?array $block): string
    {
        if (! empty($block['image'])) {
            return media_url($block['image']);
        }

        return asset($block['default'] ?? 'img/logo.png');
    }

    /** Metindeki :kargo / :yil yer tutucuları. */
    public static function text(?string $text): string
    {
        return strtr((string) $text, [':kargo' => (string) setting('free_shipping_min', 5000), ':yil' => (string) setting('copyright_year', '2021')]);
    }

    /**
     * Şubeler: paneldeki alt bilgi iletişim metninin her paragrafı bir şube.
     * "Şube 1: Adres (not)<br>Tel: …<br>E-Mail: …" → etiket, yer, not, telefonlar, e-postalar.
     */
    public static function stores(): \Illuminate\Support\Collection
    {
        return collect(preg_split('~</p>~i', (string) setting('footer_contact', self::FOOTER_CONTACT)))
            ->map(fn ($p) => trim(strip_tags($p, '<br>')))
            ->filter()
            ->map(function ($p) {
                $lines = array_values(array_filter(array_map('trim', preg_split('~<br\s*/?>~i', $p))));
                [$label, $place] = array_pad(array_map('trim', explode(':', (string) array_shift($lines), 2)), 2, null);
                if (! $place) {
                    [$label, $place] = ['Mağaza', $label];
                }
                $note = preg_match('~\(([^)]+)\)~u', $place, $m) ? $m[1] : null;
                $place = trim(preg_replace('~\s*\([^)]*\)~u', '', $place));
                $store = ['label' => $label, 'place' => $place, 'note' => $note, 'phones' => [], 'emails' => [], 'lines' => []];
                foreach ($lines as $line) {
                    if (preg_match('/^(tel|telefon)\s*:?\s*(.+)$/iu', $line, $m)) {
                        $store['phones'][] = trim($m[2]);
                    } elseif (preg_match('/^e-?mail\s*:?\s*(.+)$/iu', $line, $m)) {
                        $store['emails'][] = trim($m[1]);
                    } else {
                        $store['lines'][] = $line;
                    }
                }
                $pin = self::pin($label);
                $store['city'] = $pin['city'] ?? null;
                $store['lat'] = isset($pin['lat']) && is_numeric($pin['lat']) ? (float) $pin['lat'] : null;
                $store['lng'] = isset($pin['lng']) && is_numeric($pin['lng']) ? (float) $pin['lng'] : null;
                $store['map'] = $store['lat'] !== null
                    ? 'https://www.google.com/maps/dir/?api=1&destination='.$store['lat'].','.$store['lng']
                    : 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($place.', Kıbrıs');

                return $store;
            })->values();
    }

    /** Etikete göre şube konumu ("Şube 1" = "şube  1"). */
    private static function pin(string $label): ?array
    {
        $norm = fn ($s) => mb_strtolower(preg_replace('/\s+/u', '', (string) $s));
        foreach ((array) self::get('store_pins', []) as $p) {
            if ($norm($p['label'] ?? '') === $norm($label)) {
                return $p;
            }
        }

        return null;
    }

    public static function save(array $data): void
    {
        Setting::put(['site_content' => json_encode($data, JSON_UNESCAPED_UNICODE)]);
        self::$cache = null;
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
