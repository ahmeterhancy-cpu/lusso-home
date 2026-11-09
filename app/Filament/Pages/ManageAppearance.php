<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Setting;
use App\Support\SiteContent;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use UnitEnum;

/** Ana sayfa blokları, üst bilgi, menü ve alt bilgi — kod bilmeden düzenlenir. */
class ManageAppearance extends Page implements HasSchemas
{
    use \App\Filament\Support\RestrictedArea;

    protected static string $area = 'content';

    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paint-brush';

    protected static string|UnitEnum|null $navigationGroup = 'İçerik';

    protected static ?int $navigationSort = -1;

    protected static ?string $title = 'Site Görünümü';

    protected static ?string $navigationLabel = 'Site Görünümü';

    protected string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $state = SiteContent::all();
        // Slogan satırları düz metin olarak saklanır; formda {text: …} öğeleri.
        $state['header_lines'] = array_map(fn ($l) => ['text' => is_array($l) ? ($l['text'] ?? '') : $l], (array) $state['header_lines']);
        $this->form->fill($state + ['home_grid_category' => setting('home_grid_category', 'sofra-grubu')]);
    }

    private static function link(string $name, string $label = 'Bağlantı'): TextInput
    {
        return TextInput::make($name)->label($label)->placeholder('/product-category/… veya https://…')->maxLength(300);
    }

    private static function iconSelect(): Select
    {
        return Select::make('icon')->label('Simge')->options(SiteContent::ICONS)->native(false)->default('check');
    }

    private static function linkRepeater(string $name, string $label): Repeater
    {
        return Repeater::make($name)->label($label)->schema([
            TextInput::make('label')->label('Yazı')->required(),
            self::link('url')->required(),
        ])->columns(2)->reorderable()->collapsible()->itemLabel(fn (array $state) => $state['label'] ?? null)->addActionLabel('Bağlantı ekle')->defaultItems(0);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()->persistTabInQueryString()->columnSpanFull()->tabs([
                Tab::make('Ana sayfa bölümleri')->icon('heroicon-o-squares-2x2')->schema([
                    Section::make('Hangi bölümler görünsün?')->columns(3)->schema([
                        Toggle::make('sections.slider')->label('Slider'),
                        Toggle::make('sections.trio')->label('Üçlü afiş'),
                        Toggle::make('sections.trend')->label('Trend kategoriler'),
                        Toggle::make('sections.latest')->label('Son eklenen ürünler'),
                        Toggle::make('sections.info')->label('Bilgi kutuları'),
                        Toggle::make('sections.featured')->label('Öne çıkan ürünler'),
                        Toggle::make('sections.promo')->label('Tanıtım bloğu'),
                        Toggle::make('sections.strip')->label('Altın şerit'),
                        Toggle::make('sections.grid')->label('Kategori ızgarası (alt)'),
                    ]),
                    Section::make('Başlıklar ve ürün sayıları')->columns(3)->schema([
                        TextInput::make('subtitle')->label('Bölüm üst yazısı')->helperText('Başlıkların üstündeki küçük yazı'),
                        TextInput::make('trend_title')->label('Trend kategoriler başlığı'),
                        TextInput::make('latest_title')->label('Son eklenenler başlığı'),
                        TextInput::make('latest_count')->label('Son eklenen ürün sayısı')->numeric()->minValue(4)->maxValue(24),
                        TextInput::make('featured_title')->label('Öne çıkanlar başlığı'),
                        TextInput::make('featured_count')->label('Öne çıkan ürün sayısı')->numeric()->minValue(4)->maxValue(24),
                        Select::make('home_grid_category')->label('Alt ızgaradaki kategori')
                            ->options(fn () => Category::whereNull('parent_id')->orderBy('sort')->pluck('name', 'slug')->all()),
                        TextInput::make('grid_count')->label('Alt ızgara ürün sayısı')->numeric()->minValue(4)->maxValue(24),
                    ]),
                    Section::make('Trend kategoriler')->description('Hangi kategorilerin görüneceği ve sırası: Katalog → Kategoriler ekranında "Trend" anahtarı ve "Trend sırası".')
                        ->collapsible()->collapsed()->schema([]),
                ]),
                Tab::make('Afiş ve bloklar')->icon('heroicon-o-photo')->schema([
                    Section::make('Üçlü afiş (slider altı)')->schema([
                        Repeater::make('trio')->hiddenLabel()->schema([
                            FileUpload::make('image')->label('Görsel (kare önerilir)')->image()->disk('medya')->directory('site')->imageEditor()
                                ->helperText('Boş bırakılırsa mevcut görsel kalır.'),
                            self::link('link'),
                        ])->grid(3)->minItems(3)->maxItems(3)->addable(false)->deletable(false)->reorderable(),
                    ]),
                    Section::make('Bilgi kutuları (gri alan)')->schema([
                        Repeater::make('info')->hiddenLabel()->schema([
                            self::iconSelect(),
                            TextInput::make('title')->label('Başlık')->required(),
                            TextInput::make('text')->label('Metin')->helperText(':kargo = ücretsiz kargo alt sınırı'),
                        ])->grid(3)->minItems(1)->maxItems(3)->reorderable(),
                    ]),
                    Section::make('Tanıtım bloğu ("Evinizin Tercihi")')->columns(2)->schema([
                        FileUpload::make('promo.image')->label('Görsel')->image()->disk('medya')->directory('site')->imageEditor()->helperText('Boş = mevcut görsel'),
                        Grid::make(1)->schema([
                            TextInput::make('promo.kicker')->label('Üst yazı'),
                            TextInput::make('promo.title')->label('Başlık'),
                            TextInput::make('promo.text')->label('Metin'),
                            TextInput::make('promo.button')->label('Bağlantı yazısı'),
                            self::link('promo.link'),
                        ]),
                    ]),
                    Section::make('Altın şerit')->schema([
                        Repeater::make('strip')->hiddenLabel()->schema([
                            self::iconSelect(),
                            TextInput::make('text')->label('Yazı')->required(),
                        ])->grid(3)->minItems(1)->maxItems(3)->reorderable(),
                    ]),
                ]),
                Tab::make('Üst bilgi ve menü')->icon('heroicon-o-bars-3')->schema([
                    Section::make('Başlıktaki slogan (sol üst)')->schema([
                        Repeater::make('header_lines')->hiddenLabel()->schema([TextInput::make('text')->hiddenLabel()->required()])
                            ->maxItems(3)->addActionLabel('Satır ekle')->reorderable()->helperText('İlk iki satır kalın ve italik yazılır.'),
                    ]),
                    self::linkRepeater('topbar_links', 'Üst bar bağlantıları (sağ üst)'),
                    Section::make('Ana menü')->description('Menüdeki kategoriler: Katalog → Kategoriler ekranında "Menüde" anahtarı ve "Sıra". Buraya ek bağlantı (ör. Kampanyalar sayfası) ekleyebilirsiniz.')->schema([
                        self::linkRepeater('menu_extra', 'Menünün sonuna eklenecek bağlantılar'),
                    ]),
                ]),
                Tab::make('Alt bilgi')->icon('heroicon-o-queue-list')->schema([
                    Repeater::make('footer_columns')->label('Bağlantı sütunları')->schema([
                        TextInput::make('title')->label('Sütun başlığı')->required(),
                        self::linkRepeater('links', 'Bağlantılar'),
                    ])->maxItems(3)->collapsible()->itemLabel(fn (array $state) => $state['title'] ?? null)->addActionLabel('Sütun ekle'),
                    Section::make('İletişim sütunu ve telif')->columns(2)->schema([
                        TextInput::make('contact_title')->label('İletişim sütunu başlığı')
                            ->helperText('İçeriği: Ayarlar → Mağaza Ayarları → İletişim'),
                        TextInput::make('copyright')->label('Telif yazısı')->helperText(':yil = telif yılı'),
                        TextInput::make('credit')->label('Yapımcı yazısı'),
                        TextInput::make('credit_url')->label('Yapımcı bağlantısı')->url(),
                    ]),
                ]),
            ]),
        ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        Setting::put(['home_grid_category' => $data['home_grid_category'] ?? 'sofra-grubu']);
        unset($data['home_grid_category']);

        // Filament tekrarlayıcıları öğeleri rastgele anahtarlarla döndürür: düz listeye çevir.
        foreach (['trio', 'info', 'strip', 'header_lines', 'topbar_links', 'menu_extra', 'footer_columns'] as $k) {
            $data[$k] = array_values((array) ($data[$k] ?? []));
        }
        $data['header_lines'] = array_values(array_filter(array_map(fn ($l) => trim((string) (is_array($l) ? ($l['text'] ?? '') : $l)), $data['header_lines'])));
        foreach ($data['footer_columns'] as $i => $col) {
            $data['footer_columns'][$i]['links'] = array_values((array) ($col['links'] ?? []));
        }

        // Yüklenmemiş görseller için varsayılanları koru.
        $defaults = SiteContent::defaults();
        foreach ($data['trio'] ?? [] as $i => $b) {
            $data['trio'][$i]['default'] = $defaults['trio'][$i]['default'] ?? 'img/logo.png';
        }
        $data['promo']['default'] = $defaults['promo']['default'];
        foreach (['latest_count', 'featured_count', 'grid_count'] as $k) {
            $data[$k] = (int) ($data[$k] ?? $defaults[$k]);
        }

        SiteContent::save($data);
        Notification::make()->title('Site görünümü kaydedildi')->success()
            ->actions([Action::make('view')->label('Siteyi aç')->url(url('/'), true)])->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reset')->label('Varsayılana döndür')->color('gray')->icon('heroicon-o-arrow-uturn-left')
                ->requiresConfirmation()->modalDescription('Ana sayfa blokları, menü ve alt bilgi eski sitedeki hâline döner. Yüklediğiniz görseller silinmez.')
                ->action(function () {
                    Setting::put(['site_content' => '']);
                    SiteContent::flush();
                    $this->mount();
                    Notification::make()->title('Varsayılan görünüme dönüldü')->success()->send();
                }),
            Action::make('preview')->label('Siteyi aç')->icon('heroicon-o-arrow-top-right-on-square')->url(url('/'), true),
        ];
    }
}
