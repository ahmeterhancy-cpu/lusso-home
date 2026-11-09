<?php

namespace App\Filament\Resources\Campaigns;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Campaign;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class CampaignResource extends Resource
{
    use \App\Filament\Support\RestrictedArea;

    protected static string $area = 'marketing';

    protected static ?string $model = Campaign::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static string|UnitEnum|null $navigationGroup = 'Pazarlama';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Kampanya';

    protected static ?string $pluralModelLabel = 'Kampanyalar';

    public static function summary(Campaign $c): string
    {
        $v = rtrim(rtrim(number_format((float) $c->value, 2, ',', ''), '0'), ',');
        $min = $c->min_subtotal ? money($c->min_subtotal).' ve üzeri sepette ' : 'Tüm sepetlerde ';

        return match ($c->type) {
            'category_percent' => 'Seçili kategorilerde %'.$v.' indirim',
            'cart_percent' => $min.'%'.$v.' indirim',
            'cart_fixed' => $min.money($c->value).' indirim',
            'free_shipping' => $min.'ücretsiz kargo',
            default => $c->type,
        };
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Kampanya')->columns(2)->columnSpan(2)
                ->description('Kampanyalar kod gerektirmez; koşul sağlanınca kendiliğinden uygulanır. Kupon da girilirse ikisi birlikte geçerli olur.')
                ->schema([
                    TextInput::make('name')->label('Kampanya adı')->required()->placeholder('Ör. Yılbaşı Sofra İndirimi')
                        ->helperText('Sepette indirim satırında müşteriye bu ad görünür.')->columnSpanFull(),
                    Select::make('type')->label('Kampanya türü')->options(Campaign::TYPES)->required()->live()->default('category_percent')->columnSpanFull(),
                    TextInput::make('value')->label(fn (Get $get) => $get('type') === 'cart_fixed' ? 'İndirim tutarı (₺)' : 'İndirim yüzdesi (%)')
                        ->numeric()->minValue(0)->maxValue(fn (Get $get) => $get('type') === 'cart_fixed' ? null : 90)
                        ->required(fn (Get $get) => $get('type') !== 'free_shipping')
                        ->visible(fn (Get $get) => $get('type') !== 'free_shipping'),
                    TextInput::make('min_subtotal')->label('En az sepet tutarı (₺)')->numeric()->minValue(0)
                        ->visible(fn (Get $get) => $get('type') !== 'category_percent'),
                    Select::make('category_ids')->label('Kategoriler')->multiple()->searchable()
                        ->options(fn () => ProductResource::categoryOptions())
                        ->required(fn (Get $get) => $get('type') === 'category_percent')
                        ->visible(fn (Get $get) => $get('type') === 'category_percent')->columnSpanFull()
                        ->helperText('Alt kategoriler dahil. Ürün fiyatları sitede indirimli görünür (üstü çizili eski fiyatla).'),
                    TextInput::make('banner_text')->label('Üst bar duyurusu')->maxLength(120)->columnSpanFull()
                        ->placeholder('Ör. Tüm Sofra Grubu ürünlerinde %20 indirim!')->helperText('Boş bırakılırsa duyuru gösterilmez.'),
                    TextInput::make('badge_text')->label('Ürün kartı etiketi')->maxLength(30)
                        ->placeholder('Ör. YILBAŞI')->visible(fn (Get $get) => $get('type') === 'category_percent'),
                ]),
            Section::make('Süre')->columnSpan(1)->schema([
                Toggle::make('is_active')->label('Aktif')->default(true),
                DateTimePicker::make('starts_at')->label('Başlangıç')->seconds(false)->helperText('Boş = hemen'),
                DateTimePicker::make('ends_at')->label('Bitiş')->seconds(false)->after('starts_at')->helperText('Boş = süresiz'),
                TextInput::make('priority')->label('Öncelik')->numeric()->default(0)->helperText('Birden fazla duyuru varsa yüksek olan gösterilir.'),
            ]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Kampanya')->weight(FontWeight::Bold)->searchable()
                    ->description(fn (Campaign $record) => self::summary($record)),
                TextColumn::make('banner_text')->label('Duyuru')->limit(40)->placeholder('—')->toggleable(),
                TextColumn::make('state')->label('Durum')->badge()
                    ->state(fn (Campaign $record) => match (true) {
                        ! $record->is_active => 'Kapalı',
                        $record->ends_at && $record->ends_at->isPast() => 'Bitti',
                        $record->starts_at && $record->starts_at->isFuture() => 'Planlandı',
                        default => 'Yayında',
                    })
                    ->color(fn ($state) => match ($state) { 'Yayında' => 'success', 'Planlandı' => 'info', default => 'gray' })
                    ->description(fn (Campaign $record) => trim(($record->starts_at ? $record->starts_at->format('d.m.Y H:i') : 'Hemen').' → '.($record->ends_at ? $record->ends_at->format('d.m.Y H:i') : 'süresiz'))),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->recordActions([EditAction::make()->iconButton(), DeleteAction::make()->iconButton()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'edit' => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }
}
