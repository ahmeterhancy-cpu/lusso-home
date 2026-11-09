<?php

namespace App\Filament\Resources\Tags;

use App\Models\Tag;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class TagResource extends Resource
{
    use \App\Filament\Support\RestrictedArea;

    protected static string $area = 'catalog';

    protected static ?string $model = Tag::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-hashtag';

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Etiket';

    protected static ?string $pluralModelLabel = 'Etiketler';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Etiket')->required()->live(onBlur: true)
                ->afterStateUpdated(fn ($state, $set, $get) => $get('slug') ? null : $set('slug', Str::slug($state ?? ''))),
            TextInput::make('slug')->label('URL adı')->required()->unique(ignoreRecord: true)->helperText('Adres: /product-tag/url-adi'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('products'))
            ->defaultSort('products_count', 'desc')
            ->columns([
                TextColumn::make('name')->label('Etiket')->searchable(),
                TextColumn::make('slug')->label('Adres')->prefix('/product-tag/')->toggleable(),
                TextColumn::make('products_count')->label('Ürün')->sortable(),
            ])
            ->recordActions([EditAction::make()->iconButton(), DeleteAction::make()->iconButton()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageTags::route('/')];
    }
}
