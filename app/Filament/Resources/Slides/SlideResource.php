<?php

namespace App\Filament\Resources\Slides;

use App\Models\Slide;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class SlideResource extends Resource
{
    use \App\Filament\Support\RestrictedArea;

    protected static string $area = 'content';

    protected static ?string $model = Slide::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static string|UnitEnum|null $navigationGroup = 'İçerik';

    protected static ?string $modelLabel = 'Slayt';

    protected static ?string $pluralModelLabel = 'Ana Sayfa Slider';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('image')->label('Görsel')->image()->disk('medya')->directory('slider')->imageEditor()->required()->columnSpanFull(),
            TextInput::make('kicker')->label('Üst yazı (italik)')->placeholder('#MyLussoLife'),
            TextInput::make('title')->label('Başlık')->placeholder('LUSSO HOME CONCEPT'),
            TextInput::make('link')->label('Bağlantı')->placeholder('/product-category/mackenzie-childs'),
            TextInput::make('sort')->label('Sıra')->numeric()->default(0),
            Toggle::make('show_logo')->label('Logoyu göster')->default(true),
            Toggle::make('is_active')->label('Yayında')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                ImageColumn::make('image')->label('')->disk('medya')->height(60),
                TextColumn::make('title')->label('Başlık')->description(fn (Slide $record) => $record->kicker),
                ToggleColumn::make('is_active')->label('Yayında'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageSlides::route('/')];
    }
}
