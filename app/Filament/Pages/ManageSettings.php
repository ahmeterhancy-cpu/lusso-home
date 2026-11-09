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
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = 'Ayarlar';

    protected static ?string $title = 'Mağaza Ayarları';

    protected static ?string $navigationLabel = 'Mağaza Ayarları';

    protected string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    /** Açık/kapalı anahtarlar ('1' / '0' olarak saklanır). */
    private const BOOLS = ['pay_tiko', 'pay_havale', 'pay_kapida', 'tiko_test_mode'];

    private const KEYS = [
        'site_name', 'meta_description', 'support_phone', 'whatsapp', 'email', 'order_email',
        'facebook', 'instagram', 'footer_contact', 'contact_text', 'map_embed', 'copyright_year',
        'free_shipping_min', 'shipping_fee', 'shipping_text', 'per_page', 'home_grid_category',
        'pay_tiko', 'pay_havale', 'pay_kapida', 'bank_info',
        'tiko_merchant_id', 'tiko_secret_key', 'tiko_password', 'tiko_test_mode',
    ];

    public function mount(): void
    {
        $values = [];
        foreach (self::KEYS as $k) {
            $v = Setting::map()[$k] ?? null;
            $values[$k] = in_array($k, self::BOOLS, true) ? $v === '1' : $v;
        }
        $this->form->fill($values);
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
                    TextInput::make('home_grid_category')->label('Ana sayfa alt ızgara kategorisi (URL adı)')->helperText('Örn. sofra-grubu'),
                ])->columns(2),
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
        Setting::put($data);
        Notification::make()->title('Ayarlar kaydedildi')->success()->send();
    }
}
