<?php

namespace App\Filament\Resources\Pages;

use App\Models\Page;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'İçerik';

    protected static ?string $modelLabel = 'Sayfa';

    protected static ?string $pluralModelLabel = 'Sayfalar';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Başlık')->required(),
            TextInput::make('slug')->label('URL adı')->required()->unique(ignoreRecord: true)->regex('/^[a-z0-9-]+$/')
                ->helperText('Sadece küçük harf, rakam ve tire.'),
            RichEditor::make('content')->label('İçerik')->columnSpanFull(),
            Toggle::make('is_active')->label('Yayında')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Başlık')->searchable()->description(fn (Page $record) => '/'.$record->slug),
                ToggleColumn::make('is_active')->label('Yayında'),
                TextColumn::make('updated_at')->label('Güncellendi')->since(),
            ])
            ->recordActions([
                Action::make('view')->label('Gör')->icon('heroicon-o-arrow-top-right-on-square')->url(fn (Page $record) => route('page', $record->slug), true),
                EditAction::make()->modalWidth('5xl'),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManagePages::route('/')];
    }
}
