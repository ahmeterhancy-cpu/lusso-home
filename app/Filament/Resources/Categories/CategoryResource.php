<?php

namespace App\Filament\Resources\Categories;

use App\Models\Category;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Kategori';

    protected static ?string $pluralModelLabel = 'Kategoriler';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->label('Ad')->required()->maxLength(150)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set, $get) => $get('slug') ? null : $set('slug', Str::slug($state ?? ''))),
                TextInput::make('slug')->label('URL adı')->required()->unique(ignoreRecord: true),
                Select::make('parent_id')->label('Üst kategori')->relationship('parent', 'name', fn ($query, $record) => $record ? $query->whereKeyNot($record->id) : $query)
                    ->searchable()->preload()->placeholder('— Ana kategori —'),
                TextInput::make('sort')->label('Sıra')->numeric()->default(0),
                RichEditor::make('description')->label('Açıklama')->columnSpanFull()
                    ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'undo', 'redo']),
                FileUpload::make('image')->label('Görsel')->image()->disk('medya')->directory('kategoriler')->imageEditor(),
                Section::make('Görünürlük')->schema([
                    Toggle::make('is_active')->label('Aktif')->default(true),
                    Toggle::make('in_menu')->label('Üst menüde göster')->default(true)
                        ->helperText('Ana kategoriler menüde, alt kategoriler açılır listede görünür.'),
                    Toggle::make('is_trend')->label('"Trend Kategoriler"de göster'),
                    TextInput::make('trend_sort')->label('Trend sırası')->numeric()->default(0),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->modifyQueryUsing(fn ($query) => $query->with('parent')->withCount('products'))
            ->columns([
                ImageColumn::make('image')->label('')->disk('medya')->square()->size(40),
                TextColumn::make('name')->label('Ad')->searchable()->sortable()
                    ->description(fn (Category $record) => $record->parent ? 'Üst: '.$record->parent->name : 'Ana kategori'),
                TextColumn::make('products_count')->label('Ürün')->sortable(),
                TextColumn::make('sort')->label('Sıra')->sortable(),
                ToggleColumn::make('in_menu')->label('Menüde')->afterStateUpdated(fn () => Cache::forget('menu-tree')),
                ToggleColumn::make('is_trend')->label('Trend'),
                ToggleColumn::make('is_active')->label('Aktif')->afterStateUpdated(fn () => Cache::forget('menu-tree')),
            ])
            ->filters([
                TernaryFilter::make('parent_id')->label('Düzey')->nullable()
                    ->trueLabel('Alt kategoriler')->falseLabel('Ana kategoriler'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
