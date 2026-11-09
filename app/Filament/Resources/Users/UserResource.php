<?php

namespace App\Filament\Resources\Users;

use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = 'Satış';

    protected static ?string $modelLabel = 'Üye';

    protected static ?string $pluralModelLabel = 'Üyeler';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Ad soyad')->required(),
            TextInput::make('email')->label('E-posta')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('phone')->label('Telefon'),
            TextInput::make('city')->label('Şehir'),
            TextInput::make('address')->label('Adres')->columnSpanFull(),
            TextInput::make('password')->label('Yeni şifre')->password()->revealable()->minLength(8)
                ->dehydrated(fn ($state) => filled($state))->dehydrateStateUsing(fn ($state) => Hash::make($state))
                ->required(fn (string $operation) => $operation === 'create')
                ->helperText('Değiştirmeyecekseniz boş bırakın.'),
            Toggle::make('is_admin')->label('Yönetim paneline erişebilir')
                ->disabled(fn ($record) => $record?->is(auth()->user())),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->withCount('orders'))
            ->columns([
                TextColumn::make('name')->label('Ad')->searchable()->description(fn (User $record) => $record->email),
                TextColumn::make('phone')->label('Telefon')->searchable()->placeholder('—'),
                TextColumn::make('orders_count')->label('Sipariş')->sortable(),
                IconColumn::make('is_admin')->label('Yönetici')->boolean(),
                TextColumn::make('created_at')->label('Kayıt')->date('d.m.Y')->sortable(),
            ])
            ->filters([TernaryFilter::make('is_admin')->label('Yönetici')])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageUsers::route('/')];
    }
}
