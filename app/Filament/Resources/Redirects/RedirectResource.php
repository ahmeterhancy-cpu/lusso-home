<?php

namespace App\Filament\Resources\Redirects;

use App\Models\Redirect;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class RedirectResource extends Resource
{
    use \App\Filament\Support\RestrictedArea;

    protected static string $area = 'marketing';

    protected static ?string $model = Redirect::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string|UnitEnum|null $navigationGroup = 'Pazarlama';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Yönlendirme';

    protected static ?string $pluralModelLabel = 'Yönlendirmeler (301)';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('from_path')->label('Eski adres')->required()->placeholder('/eski-urun-adresi')
                ->helperText('Sadece yol yeterli; alan adı ve ?… kısmı yok sayılır.')
                ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule, $state) => $rule->where('from_path', Redirect::normalize($state))),
            TextInput::make('to_url')->label('Yeni adres')->required()->placeholder('/product/yeni-urun veya https://…'),
            Select::make('status')->label('Tür')->options([301 => '301 — kalıcı (SEO için önerilen)', 302 => '302 — geçici'])->default(301)->required(),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('from_path')->label('Eski adres')->prefix('/')->searchable()->copyable(),
                TextColumn::make('to_url')->label('Yeni adres')->searchable()->limit(50),
                TextColumn::make('status')->label('Tür')->badge(),
                TextColumn::make('hits')->label('Kullanım')->sortable()->description(fn (Redirect $record) => $record->last_hit_at?->diffForHumans()),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->recordActions([EditAction::make()->iconButton(), DeleteAction::make()->iconButton()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageRedirects::route('/')];
    }
}
