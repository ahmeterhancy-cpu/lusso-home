<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use UnitEnum;

/** Mağaza ayarları: settings tablosundaki anahtar/değer kayıtları. */
class ManageSettings extends Page implements HasSchemas
{
    use \App\Filament\Support\RestrictedArea;

    protected static string $area = 'settings';

    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = 'Ayarlar';

    protected static ?string $title = 'Mağaza Ayarları';

    protected static ?string $navigationLabel = 'Mağaza Ayarları';

    protected string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    /** Açık/kapalı anahtarlar ('1' / '0' olarak saklanır). */
    private const BOOLS = ['pay_tiko', 'pay_havale', 'pay_kapida', 'tiko_test_mode', 'maintenance'];

    /** JSON olarak saklanan liste ayarları. */
    private const JSONS = ['shipping_zones'];

    private const KEYS = [
        'site_name', 'meta_description', 'support_phone', 'whatsapp', 'email', 'order_email',
        'facebook', 'instagram', 'footer_contact', 'contact_text', 'map_embed', 'copyright_year',
        'free_shipping_min', 'shipping_fee', 'shipping_text', 'shipping_zones', 'per_page',
        'pay_tiko', 'pay_havale', 'pay_kapida', 'bank_info',
        'tiko_merchant_id', 'tiko_secret_key', 'tiko_password', 'tiko_test_mode',
        'maintenance', 'maintenance_title', 'maintenance_text', 'robots_txt', 'head_code',
    ];

    public function mount(): void
    {
        $values = [];
        foreach (self::KEYS as $k) {
            $v = Setting::map()[$k] ?? null;
            $values[$k] = match (true) {
                in_array($k, self::BOOLS, true) => $v === '1',
                in_array($k, self::JSONS, true) => array_values((array) json_decode((string) $v, true)),
                default => $v,
            };
        }
        $this->form->fill($values);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('cache')->label('Önbelleği temizle')->icon('heroicon-o-arrow-path')->color('gray')
                ->requiresConfirmation()->modalDescription('Menü, kampanya, ayar ve sayfa önbellekleri temizlenir. Sitede bir değişiklik görünmüyorsa kullanın.')
                ->action(function () {
                    \Illuminate\Support\Facades\Cache::flush();
                    \Illuminate\Support\Facades\Artisan::call('view:clear');
                    Notification::make()->title('Önbellek temizlendi')->success()->send();
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()->tabs([
                Tab::make('Genel')->icon('heroicon-o-building-storefront')->schema([
                    TextInput::make('site_name')->label('Site adı'),
                    Textarea::make('meta_description')->label('Arama motoru açıklaması')->rows(2)->maxLength(160),
                    TextInput::make('support_phone')->label('Destek hattı (üst bar)'),
                    TextInput::make('whatsapp')->label('WhatsApp numarası')->helperText('Ülke koduyla, boşluksuz: 905391099900'),
                    TextInput::make('email')->label('E-posta')->email(),
                    TextInput::make('order_email')->label('Sipariş bildirimi e-postası')->email()->helperText('Boşsa yukarıdaki adres kullanılır.'),
                    TextInput::make('facebook')->label('Facebook')->url(),
                    TextInput::make('instagram')->label('Instagram')->url(),
                    TextInput::make('copyright_year')->label('Telif yılı (alt bilgi)'),
                ])->columns(2),
                Tab::make('İletişim')->icon('heroicon-o-map-pin')->schema([
                    Textarea::make('footer_contact')->label('Alt bilgi "İletişim" sütunu (HTML)')->rows(8),
                    Textarea::make('contact_text')->label('İletişim sayfası bilgileri (HTML)')->rows(4),
                    TextInput::make('map_embed')->label('Harita gömme adresi')->helperText('Google Haritalar → Paylaş → Harita yerleştir → src="…" içindeki adres'),
                ]),
                Tab::make('Kargo ve vitrin')->icon('heroicon-o-truck')->schema([
                    TextInput::make('free_shipping_min')->label('Ücretsiz kargo alt sınırı (₺)')->numeric(),
                    TextInput::make('shipping_fee')->label('Kargo ücreti (₺)')->numeric()->helperText('0 = her zaman ücretsiz'),
                    Textarea::make('shipping_text')->label('Ürün sayfası "Kargo & Teslimat" sekmesi (HTML)')->rows(4)->columnSpanFull(),
                    TextInput::make('per_page')->label('Kategori sayfasında ürün sayısı')->numeric(),
                    \Filament\Forms\Components\Repeater::make('shipping_zones')->label('Şehre göre kargo')->columnSpanFull()
                        ->helperText('Tanımlanırsa kasadaki şehir listesi buradan gelir. Boş "ücretsiz kargo alt sınırı" = genel sınır geçerli.')
                        ->schema([
                            TextInput::make('city')->label('Şehir / bölge')->required(),
                            TextInput::make('fee')->label('Kargo ücreti (₺)')->numeric()->minValue(0)->required(),
                            TextInput::make('free_min')->label('Ücretsiz kargo alt sınırı (₺)')->numeric()->minValue(0),
                        ])->columns(3)->reorderable()->defaultItems(0)->addActionLabel('Şehir ekle')
                        ->itemLabel(fn (array $state) => $state['city'] ?? null),
                ])->columns(2),
                Tab::make('Bakım ve SEO')->icon('heroicon-o-wrench-screwdriver')->schema([
                    Section::make('Bakım modu')->columns(2)->schema([
                        Toggle::make('maintenance')->label('Siteyi bakım moduna al')->columnSpanFull()
                            ->helperText('Ziyaretçiler bakım sayfasını görür. Siz (yönetici) siteyi normal görmeye devam edersiniz; ödeme bildirimleri çalışır.'),
                        TextInput::make('maintenance_title')->label('Bakım başlığı')->placeholder('Kısa bir ara veriyoruz'),
                        TextInput::make('maintenance_text')->label('Bakım metni')->placeholder('Sitemizi sizin için yeniliyoruz…'),
                    ]),
                    Textarea::make('robots_txt')->label('robots.txt')->rows(6)
                        ->placeholder("User-agent: *\nDisallow: /admin\nDisallow: /cart\nDisallow: /checkout\nDisallow: /hesabim")
                        ->helperText('Site haritası satırı otomatik eklenir.'),
                    Textarea::make('head_code')->label('<head> içine eklenecek kod')->rows(5)
                        ->helperText('Google Analytics, Meta Pixel, Search Console doğrulama gibi kodlar. Yalnız güvendiğiniz kodu yapıştırın.'),
                ]),
                Tab::make('Ödeme')->icon('heroicon-o-credit-card')->schema([
                    Section::make('Ödeme yöntemleri')->columns(3)->schema([
                        Toggle::make('pay_tiko')->label('Kartla ödeme (Tiko)'),
                        Toggle::make('pay_havale')->label('Havale / EFT'),
                        Toggle::make('pay_kapida')->label('Kapıda ödeme'),
                    ]),
                    Textarea::make('bank_info')->label('Banka hesap bilgileri (havale)')->rows(4)
                        ->placeholder("Banka: …\nHesap sahibi: …\nIBAN: …"),
                    Section::make('Tiko sanal POS')->description('Tiko’nun verdiği mağaza bilgileri. Boş bırakılırsa kartla ödeme seçeneği gizlenir.')->columns(2)->schema([
                        TextInput::make('tiko_merchant_id')->label('Mağaza no (MerchantId)'),
                        TextInput::make('tiko_secret_key')->label('Mağaza gizli anahtarı')->password()->revealable(),
                        TextInput::make('tiko_password')->label('Mağaza şifresi')->password()->revealable(),
                        Toggle::make('tiko_test_mode')->label('Test modu (sandbox)')->inline(false),
                    ]),
                ]),
            ])->columnSpanFull(),
        ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        foreach (self::BOOLS as $b) {
            $data[$b] = ! empty($data[$b]) ? '1' : '0';
        }
        foreach (self::JSONS as $j) {
            $data[$j] = json_encode(array_values((array) ($data[$j] ?? [])), JSON_UNESCAPED_UNICODE);
        }
        Setting::put($data);
        Notification::make()->title('Ayarlar kaydedildi')->success()->send();
    }
}
