<?php

namespace App\Filament\Resources\Coupons;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Coupon;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static string|UnitEnum|null $navigationGroup = 'Pazarlama';

    protected static ?int $navigationSort = 0;

    protected static ?string $modelLabel = 'Kupon';

    protected static ?string $pluralModelLabel = 'Kuponlar';

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Kupon')->columns(2)->columnSpan(2)->schema([
                TextInput::make('code')->label('Kupon kodu')->required()->maxLength(40)
                    ->unique(ignoreRecord: true)->extraInputAttributes(['style' => 'text-transform:uppercase;letter-spacing:1px'])
                    ->helperText('Müşteri büyük/küçük harf ayırmadan yazabilir.')
                    ->suffixAction(Action::make('generate')->icon('heroicon-o-sparkles')->tooltip('Rastgele kod üret')
                        ->action(fn (Set $set) => $set('code', 'LUSSO'.strtoupper(Str::random(5))))),
                TextInput::make('description')->label('Açıklama (iç kullanım)')->placeholder('Ör. Instagram çekilişi'),
                Select::make('type')->label('İndirim türü')->options(Coupon::TYPES)->required()->live()->default('percent'),
                TextInput::make('value')->label(fn (Get $get) => $get('type') === 'fixed' ? 'İndirim tutarı (₺)' : 'İndirim yüzdesi (%)')
                    ->numeric()->minValue(0)->required(fn (Get $get) => $get('type') !== 'free_shipping')
                    ->maxValue(fn (Get $get) => $get('type') === 'percent' ? 100 : null)
                    ->visible(fn (Get $get) => $get('type') !== 'free_shipping'),
                TextInput::make('min_subtotal')->label('En az sepet tutarı (₺)')->numeric()->minValue(0),
                TextInput::make('max_discount')->label('En fazla indirim (₺)')->numeric()->minValue(0)
                    ->visible(fn (Get $get) => $get('type') === 'percent')->helperText('Yüzdeli kuponda üst sınır.'),
                Select::make('category_ids')->label('Yalnız bu kategorilerde geçerli')->multiple()->searchable()
                    ->options(fn () => ProductResource::categoryOptions())->columnSpanFull()
                    ->helperText('Boş bırakılırsa tüm ürünlerde geçerli. Alt kategoriler dahil.'),
                Toggle::make('exclude_sale_items')->label('İndirimdeki ürünlerde geçmesin')->columnSpanFull(),
            ]),
            Section::make('Süre ve sınırlar')->columnSpan(1)->schema([
                Toggle::make('is_active')->label('Aktif')->default(true),
                DateTimePicker::make('starts_at')->label('Başlangıç')->seconds(false),
                DateTimePicker::make('ends_at')->label('Bitiş')->seconds(false)->after('starts_at'),
                TextInput::make('usage_limit')->label('Toplam kullanım sınırı')->numeric()->minValue(1)->helperText('Boş = sınırsız'),
                TextInput::make('per_customer_limit')->label('Müşteri başına sınır')->numeric()->minValue(1)->default(1)
                    ->helperText('E-posta / üyelik bazında. Boş = sınırsız'),
            ]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')->label('Kod')->weight(FontWeight::Bold)->searchable()->copyable()
                    ->description(fn (Coupon $record) => $record->description),
                TextColumn::make('type')->label('İndirim')->state(fn (Coupon $record) => $record->summary())
                    ->description(fn (Coupon $record) => $record->min_subtotal ? 'En az '.money($record->min_subtotal) : null),
                TextColumn::make('used_count')->label('Kullanım')->sortable()
                    ->formatStateUsing(fn ($state, Coupon $record) => $state.($record->usage_limit ? ' / '.$record->usage_limit : '')),
                TextColumn::make('revenue')->label('Getirdiği ciro')
                    ->state(fn (Coupon $record) => money(Order::where('coupon_code', $record->code)->where('payment_status', 'paid')->sum('total'))),
                TextColumn::make('validity')->label('Geçerlilik')->badge()
                    ->state(fn (Coupon $record) => match (true) {
                        ! $record->is_active => 'Kapalı',
                        $record->ends_at && $record->ends_at->isPast() => 'Süresi doldu',
                        $record->starts_at && $record->starts_at->isFuture() => 'Başlamadı',
                        $record->usage_limit && $record->used_count >= $record->usage_limit => 'Hakkı doldu',
                        default => 'Geçerli',
                    })
                    ->color(fn ($state) => $state === 'Geçerli' ? 'success' : ($state === 'Başlamadı' ? 'info' : 'gray'))
                    ->description(fn (Coupon $record) => $record->ends_at ? 'Bitiş: '.$record->ends_at->format('d.m.Y H:i') : 'Süresiz'),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->filters([TernaryFilter::make('is_active')->label('Aktif')])
            ->recordActions([
                EditAction::make()->iconButton(),
                ReplicateAction::make()->iconButton()->tooltip('Kopyala')
                    ->mutateRecordDataUsing(fn (array $data) => [...$data, 'code' => $data['code'].'-'.strtoupper(Str::random(3)), 'used_count' => 0]),
                DeleteAction::make()->iconButton(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
