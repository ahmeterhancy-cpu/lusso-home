<?php

namespace App\Filament\Resources\XmlFeeds;

use App\Models\Category;
use App\Models\XmlFeed;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class XmlFeedResource extends Resource
{
    use \App\Filament\Support\RestrictedArea;

    protected static string $area = 'marketing';

    protected static ?string $model = XmlFeed::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-code-bracket';

    protected static string|UnitEnum|null $navigationGroup = 'Pazarlama';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'XML akışı';

    protected static ?string $pluralModelLabel = 'XML Akışları';

    /** "Üst › Alt" etiketli, alfabetik kategori listesi. */
    public static function categoryOptions(): array
    {
        $cats = Category::get(['id', 'name', 'parent_id'])->keyBy('id');
        $label = function ($c) use ($cats) {
            $parts = [$c->name];
            $guard = 0;
            while ($c->parent_id && ($c = $cats->get($c->parent_id)) && $guard++ < 10) {
                array_unshift($parts, $c->name);
            }

            return implode(' › ', $parts);
        };

        return $cats->map($label)->sort(fn ($a, $b) => strcoll($a, $b))->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Akış')->columns(2)->schema([
                TextInput::make('name')->label('Ad')->required()->maxLength(120)->placeholder('ör. Google Merchant, Meta katalog, Bayi XML')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, Set $set, ?XmlFeed $record) {
                        if (! $record) {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                TextInput::make('slug')->label('Adres')->required()->maxLength(80)->prefix(url('/xml').'/')->suffix('.xml')
                    ->regex('/^[a-z0-9-]+$/')->unique(ignoreRecord: true)
                    ->helperText('Küçük harf, rakam ve tire.'),
                Select::make('format')->label('Biçim')->options(XmlFeed::FORMATS)->default('google')->required()->native(false)->live()->columnSpanFull(),
            ]),
            Section::make('Hangi ürünler?')->columns(3)->schema([
                Select::make('category_ids')->label('Kategoriler')->multiple()->searchable()
                    ->options(fn () => self::categoryOptions())
                    ->helperText('Boş bırakırsanız tüm kategoriler girer.')
                    ->columnSpanFull(),
                Toggle::make('include_children')->label('Alt kategorileri de kat')->default(true)
                    ->helperText('"Sofra Grubu" seçilince altındaki Kupa, Tepsi… da girer.'),
                Toggle::make('in_stock_only')->label('Yalnız stoktakiler')->default(false),
                Toggle::make('priced_only')->label('Fiyatı olmayanları çıkar')->default(true)
                    ->helperText('Google ve Meta fiyatsız ürünü reddeder.'),
            ]),
            Section::make('Google / Meta alanları')->columns(2)->visible(fn ($get) => $get('format') !== 'genel')->schema([
                TextInput::make('brand')->label('Marka (g:brand)')->placeholder('Lusso Home Concept')->maxLength(120),
                TextInput::make('google_category')->label('Google ürün kategorisi')->placeholder('ör. 536 (Ev ve Bahçe)')->maxLength(200)
                    ->helperText('Google taksonomisinden kimlik ya da yol; boş bırakılabilir.'),
            ]),
            Section::make('Erişim')->columns(2)->schema([
                TextInput::make('token')->label('Gizli anahtar (isteğe bağlı)')->maxLength(64)
                    ->helperText('Doluysa adres yalnız ?key=… ile açılır. Google/Meta için boş bırakın.')
                    ->suffixAction(Action::make('generate')->icon('heroicon-o-arrow-path')->tooltip('Rastgele üret')
                        ->action(fn (Set $set) => $set('token', Str::random(32)))),
                Toggle::make('is_active')->label('Yayında')->default(true)->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Akış')->weight('bold')->searchable()
                    ->description(fn (XmlFeed $record) => $record->format === 'genel' ? 'Genel XML' : 'Google / Meta'),
                TextColumn::make('url')->label('Adres')->state(fn (XmlFeed $record) => $record->url())
                    ->copyable()->copyMessage('Adres kopyalandı')->limit(48)->color('primary'),
                TextColumn::make('category_ids')->label('Kategoriler')
                    ->state(function (XmlFeed $record) {
                        $ids = (array) $record->category_ids;
                        if (! $ids) {
                            return 'Tümü';
                        }
                        $names = Category::whereIn('id', $ids)->pluck('name')->all();

                        return implode(', ', $names).($record->include_children ? ' (+ alt)' : '');
                    })->wrap()->limit(80),
                TextColumn::make('products')->label('Ürün')->state(fn (XmlFeed $record) => number_format($record->productQuery()->count(), 0, ',', '.'))
                    ->tooltip('Fiyat ve kampanya süzgeci akış üretilirken ayrıca uygulanır.'),
                TextColumn::make('hits')->label('Okunma')->sortable()->description(fn (XmlFeed $record) => $record->last_hit_at?->diffForHumans()),
                ToggleColumn::make('is_active')->label('Yayında'),
            ])
            ->recordActions([
                Action::make('open')->label('Aç')->icon('heroicon-o-arrow-top-right-on-square')->url(fn (XmlFeed $record) => $record->url(), shouldOpenInNewTab: true),
                EditAction::make()->iconButton()->modalWidth('4xl'),
                DeleteAction::make()->iconButton(),
            ])
            ->emptyStateHeading('Henüz XML akışı yok')
            ->emptyStateDescription('Google Merchant, Facebook/Instagram kataloğu ya da bayi entegrasyonu için bir akış ekleyin; içine girecek kategorileri seçin.');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageXmlFeeds::route('/')];
    }
}
