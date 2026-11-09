<?php

namespace App\Filament\Resources\Products;

use App\Models\Category;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 0;

    protected static ?string $modelLabel = 'Ürün';

    protected static ?string $pluralModelLabel = 'Ürünler';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'sku'];
    }

    public static function categoryOptions(): array
    {
        return Category::with('parent')->orderBy('name')->get()
            ->mapWithKeys(fn (Category $c) => [$c->id => ($c->parent ? $c->parent->name.' › ' : '').$c->name])
            ->sort()->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ürün bilgileri')->columns(2)->columnSpan(2)->schema([
                TextInput::make('name')->label('Ürün adı')->required()->maxLength(250)->columnSpanFull()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set, $get) => $get('slug') ? null : $set('slug', Str::slug($state ?? ''))),
                TextInput::make('slug')->label('URL adı')->required()->unique(ignoreRecord: true)->maxLength(200)
                    ->helperText('Adres: /product/url-adi'),
                TextInput::make('sku')->label('Stok kodu')->maxLength(100),
                RichEditor::make('short_description')->label('Kısa açıklama')->columnSpanFull()
                    ->toolbarButtons(['bold', 'italic', 'bulletList', 'link', 'undo', 'redo']),
                RichEditor::make('description')->label('Açıklama')->columnSpanFull(),
            ]),
            Section::make('Satış')->columnSpan(1)->schema([
                TextInput::make('price')->label('Fiyat (₺)')->numeric()->minValue(0)->default(0)->required()
                    ->helperText('0 bırakılırsa ürün "Devamını oku" olarak görünür, sepete eklenemez.'),
                TextInput::make('sale_price')->label('İndirimli fiyat (₺)')->numeric()->minValue(0)
                    ->lt('price')->validationMessages(['lt' => 'İndirimli fiyat normal fiyattan düşük olmalı.']),
                Toggle::make('in_stock')->label('Stokta var')->default(true),
                TextInput::make('stock_qty')->label('Stok adedi')->numeric()->minValue(0)
                    ->helperText('Boş bırakılırsa adet takibi yapılmaz.'),
                Toggle::make('is_active')->label('Yayında')->default(true),
                Toggle::make('is_featured')->label('Öne çıkan')->helperText('Ana sayfadaki "Öne Çıkan Ürünler" rayında görünür.'),
                DateTimePicker::make('published_at')->label('Yayın tarihi')->default(now())
                    ->helperText('"Son Eklenen Ürünler" bu tarihe göre sıralanır.'),
            ]),
            Section::make('Kategoriler ve etiketler')->columns(2)->columnSpan(2)->schema([
                Select::make('categories')->label('Kategoriler')->relationship('categories', 'name')
                    ->options(fn () => self::categoryOptions())->multiple()->searchable()->preload(),
                Select::make('tags')->label('Etiketler')->relationship('tags', 'name')->multiple()->searchable()
                    ->createOptionForm([
                        TextInput::make('name')->label('Etiket')->required(),
                        TextInput::make('slug')->label('URL adı')->required(),
                    ]),
            ]),
            Section::make('Görseller')->columnSpan(2)->schema([
                Repeater::make('images')->relationship()->label('')->orderColumn('sort')->reorderable()
                    ->grid(4)->addActionLabel('Görsel ekle')
                    ->schema([
                        FileUpload::make('path')->label('')->image()->disk('medya')->directory('urunler')
                            ->imageEditor()->maxSize(8192)->required(),
                    ])
                    ->helperText('İlk görsel kapak, ikincisi kartın üzerine gelince görünen görseldir.'),
            ]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['images', 'categories']))
            ->defaultSort('published_at', 'desc')
            ->columns([
                ImageColumn::make('thumb')->label('')->square()->size(48)
                    ->getStateUsing(fn (Product $record) => $record->images->first()?->path)->disk('medya'),
                TextColumn::make('name')->label('Ürün')->searchable(['name', 'sku'])->sortable()->wrap()
                    ->description(fn (Product $record) => $record->sku),
                TextColumn::make('categories.name')->label('Kategori')->badge()->limitList(2)->toggleable(),
                TextColumn::make('price')->label('Fiyat')->sortable()
                    ->formatStateUsing(fn ($state, Product $record) => (float) $state > 0 ? money($record->finalPrice()) : '—')
                    ->description(fn (Product $record) => $record->onSale() ? 'Normal: '.money($record->price) : null),
                ToggleColumn::make('in_stock')->label('Stok'),
                ToggleColumn::make('is_featured')->label('Öne çıkan')->toggleable(),
                ToggleColumn::make('is_active')->label('Yayında'),
                TextColumn::make('published_at')->label('Tarih')->date('d.m.Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')->label('Kategori')->options(fn () => self::categoryOptions())->searchable()
                    ->query(fn (Builder $query, array $data) => $data['value']
                        ? $query->whereHas('categories', fn ($c) => $c->whereIn('categories.id', Category::find($data['value'])?->descendantIds() ?? []))
                        : $query),
                TernaryFilter::make('is_active')->label('Yayında'),
                TernaryFilter::make('in_stock')->label('Stokta'),
                Filter::make('no_price')->label('Fiyatı olmayanlar')->query(fn (Builder $query) => $query->where('price', 0)),
                Filter::make('no_image')->label('Görseli olmayanlar')->query(fn (Builder $query) => $query->doesntHave('images')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')->label('Yayına al')->icon('heroicon-o-eye')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))->deselectRecordsAfterCompletion(),
                    BulkAction::make('unpublish')->label('Yayından kaldır')->icon('heroicon-o-eye-slash')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))->deselectRecordsAfterCompletion(),
                    BulkAction::make('feature')->label('Öne çıkar')->icon('heroicon-o-star')
                        ->action(fn (Collection $records) => $records->each->update(['is_featured' => true]))->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
