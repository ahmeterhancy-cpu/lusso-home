<?php

namespace App\Filament\Resources\Products;

use App\Models\Category;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextInputColumn;
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
    use \App\Filament\Support\RestrictedArea;

    protected static string $area = 'catalog';

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
            \App\Filament\Support\SeoFields::section(),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['images', 'categories']))
            ->defaultSort('published_at', 'desc')
            ->columns([
                ImageColumn::make('thumb')->label('')->square()->size(56)->extraImgAttributes(['style' => 'object-fit:contain;background:#fff'])
                    ->getStateUsing(fn (Product $record) => $record->images->first()?->path)->disk('medya'),
                TextColumn::make('name')->label('Ürün')->searchable(['name', 'sku'])->sortable()->wrap()->extraAttributes(['style' => 'min-width:280px'])
                    ->description(fn (Product $record) => $record->sku),
                TextColumn::make('categories.name')->label('Kategori')->badge()->color('gray')->limitList(2)->toggleable(),
                // Satırda düzenlenebilir fiyat: 600 fiyatsız ürünü tek tek açmadan girebilmek için.
                TextInputColumn::make('price')->label('Fiyat (₺)')->type('number')->rules(['numeric', 'min:0'])->sortable()->width('120px')
                    ->afterStateUpdated(fn () => Notification::make()->title('Fiyat kaydedildi')->success()->send()),
                TextInputColumn::make('sale_price')->label('İndirimli (₺)')->type('number')->rules(['nullable', 'numeric', 'min:0'])->width('120px')->toggleable(),
                TextInputColumn::make('stock_qty')->label('Adet')->type('number')->rules(['nullable', 'integer', 'min:0'])->width('90px')
                    ->tooltip('Boş = adet takibi yok')->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('in_stock')->label('Stokta'),
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
                EditAction::make()->iconButton(),
                ActionGroup::make([
                    Action::make('view')->label('Sitede gör')->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn (Product $record) => route('product', $record->slug), true),
                    ReplicateAction::make()->label('Kopyala')
                        ->excludeAttributes(['wc_id'])
                        ->mutateRecordDataUsing(fn (array $data) => [...$data, 'name' => $data['name'].' (kopya)', 'slug' => $data['slug'].'-kopya-'.Str::lower(Str::random(4)), 'is_active' => false])
                        ->after(function (Product $replica, Product $record) {
                            $replica->categories()->sync($record->categories->pluck('id'));
                            $replica->tags()->sync($record->tags->pluck('id'));
                            foreach ($record->images as $img) {
                                $replica->images()->create(['path' => $img->path, 'sort' => $img->sort]);
                            }
                        })
                        ->successRedirectUrl(fn (Product $replica) => self::getUrl('edit', ['record' => $replica])),
                    \Filament\Actions\DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('price')->label('Fiyat güncelle')->icon('heroicon-o-currency-dollar')
                        ->schema([
                            Select::make('mode')->label('İşlem')->required()->live()->options([
                                'set' => 'Fiyatı şu tutar yap',
                                'percent' => 'Yüzde artır / azalt',
                                'sale_percent' => 'Yüzde indirim uygula (indirimli fiyat)',
                                'clear_sale' => 'İndirimi kaldır',
                            ]),
                            TextInput::make('value')->label(fn ($get) => match ($get('mode')) {
                                'set' => 'Yeni fiyat (₺)', 'percent' => 'Yüzde (ör. 10 veya -15)', 'sale_percent' => 'İndirim yüzdesi (ör. 20)', default => 'Değer',
                            })->numeric()->required(fn ($get) => $get('mode') !== 'clear_sale')->visible(fn ($get) => $get('mode') && $get('mode') !== 'clear_sale'),
                            Toggle::make('round')->label('Fiyatı tam liraya yuvarla')->default(true)->visible(fn ($get) => in_array($get('mode'), ['percent', 'sale_percent'], true)),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $v = (float) ($data['value'] ?? 0);
                            $r = fn ($x) => ! empty($data['round']) ? round($x) : round($x, 2);
                            foreach ($records as $p) {
                                match ($data['mode']) {
                                    'set' => $p->update(['price' => max(0, $v)]),
                                    'percent' => (float) $p->price > 0 ? $p->update(['price' => max(0, $r($p->price * (1 + $v / 100)))]) : null,
                                    'sale_percent' => (float) $p->price > 0 ? $p->update(['sale_price' => $r($p->price * (1 - $v / 100))]) : null,
                                    'clear_sale' => $p->update(['sale_price' => null]),
                                };
                            }
                            Notification::make()->title($records->count().' ürünün fiyatı güncellendi')->success()->send();
                        })->deselectRecordsAfterCompletion(),
                    BulkAction::make('category')->label('Kategoriye ekle / taşı')->icon('heroicon-o-squares-plus')
                        ->schema([
                            Select::make('categories')->label('Kategoriler')->options(fn () => self::categoryOptions())->multiple()->searchable()->required(),
                            Toggle::make('replace')->label('Mevcut kategorileri kaldır (taşı)'),
                        ])
                        ->action(function (Collection $records, array $data) {
                            foreach ($records as $p) {
                                $data['replace'] ? $p->categories()->sync($data['categories']) : $p->categories()->syncWithoutDetaching($data['categories']);
                            }
                            Notification::make()->title('Kategoriler güncellendi')->success()->send();
                        })->deselectRecordsAfterCompletion(),
                    BulkAction::make('stock_in')->label('Stokta var yap')->icon('heroicon-o-check-circle')
                        ->action(fn (Collection $records) => $records->each->update(['in_stock' => true]))->deselectRecordsAfterCompletion(),
                    BulkAction::make('stock_out')->label('Stokta yok yap')->icon('heroicon-o-x-circle')
                        ->action(fn (Collection $records) => $records->each->update(['in_stock' => false]))->deselectRecordsAfterCompletion(),
                    BulkAction::make('publish')->label('Yayına al')->icon('heroicon-o-eye')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))->deselectRecordsAfterCompletion(),
                    BulkAction::make('unpublish')->label('Yayından kaldır')->icon('heroicon-o-eye-slash')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))->deselectRecordsAfterCompletion(),
                    BulkAction::make('feature')->label('Öne çıkar')->icon('heroicon-o-star')
                        ->action(fn (Collection $records) => $records->each->update(['is_featured' => true]))->deselectRecordsAfterCompletion(),
                    BulkAction::make('unfeature')->label('Öne çıkanlardan kaldır')->icon('heroicon-o-star')
                        ->action(fn (Collection $records) => $records->each->update(['is_featured' => false]))->deselectRecordsAfterCompletion(),
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
