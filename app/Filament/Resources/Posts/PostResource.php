<?php

namespace App\Filament\Resources\Posts;

use App\Models\Post;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class PostResource extends Resource
{
    use \App\Filament\Support\RestrictedArea;

    protected static string $area = 'content';

    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-newspaper';

    protected static string|UnitEnum|null $navigationGroup = 'İçerik';

    protected static ?string $modelLabel = 'Blog yazısı';

    protected static ?string $pluralModelLabel = 'Blog';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Başlık')->required()->live(onBlur: true)
                ->afterStateUpdated(fn ($state, $set, $get) => $get('slug') ? null : $set('slug', Str::slug($state ?? ''))),
            TextInput::make('slug')->label('URL adı')->required()->unique(ignoreRecord: true),
            FileUpload::make('image')->label('Kapak görseli')->image()->disk('medya')->directory('blog')->imageEditor(),
            Textarea::make('excerpt')->label('Özet')->rows(4),
            RichEditor::make('content')->label('İçerik')->columnSpanFull(),
            DateTimePicker::make('published_at')->label('Yayın tarihi')->default(now()),
            Toggle::make('is_active')->label('Yayında')->default(true),
            \App\Filament\Support\SeoFields::section(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                ImageColumn::make('image')->label('')->disk('medya')->square()->size(48),
                TextColumn::make('title')->label('Başlık')->searchable(),
                TextColumn::make('published_at')->label('Tarih')->date('d.m.Y')->sortable(),
                ToggleColumn::make('is_active')->label('Yayında'),
            ])
            ->recordActions([EditAction::make()->modalWidth('5xl'), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManagePosts::route('/')];
    }
}
