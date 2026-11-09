<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
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

    protected static ?int $navigationSort = 2;

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
            ->modifyQueryUsing(fn ($query) => $query->withCount('orders')
                ->withSum(['orders as spent' => fn ($o) => $o->where('payment_status', 'paid')], 'total')
                ->withMax('orders as last_order_at', 'created_at'))
            ->columns([
                TextColumn::make('name')->label('Ad')->searchable(['name', 'email'])->weight('bold')->description(fn (User $record) => $record->email),
                TextColumn::make('phone')->label('Telefon')->searchable()->placeholder('—')->description(fn (User $record) => $record->city),
                TextColumn::make('orders_count')->label('Sipariş')->sortable(),
                TextColumn::make('spent')->label('Toplam harcama')->formatStateUsing(fn ($state) => money($state ?? 0))->sortable(),
                TextColumn::make('last_order_at')->label('Son sipariş')->since()->placeholder('—')->sortable(),
                IconColumn::make('is_admin')->label('Yönetici')->boolean()->toggleable(),
                TextColumn::make('created_at')->label('Kayıt')->date('d.m.Y')->sortable()->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_admin')->label('Yönetici'),
                \Filament\Tables\Filters\Filter::make('buyers')->label('Sipariş verenler')->query(fn ($query) => $query->has('orders')),
            ])
            ->recordActions([
                ViewAction::make()->label('Aç')->modalWidth('3xl'),
                EditAction::make()->iconButton(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                TextEntry::make('name')->label('Ad soyad')->weight('bold'),
                TextEntry::make('email')->label('E-posta')->copyable(),
                TextEntry::make('phone')->label('Telefon')->placeholder('—')->copyable(),
                TextEntry::make('address')->label('Adres')->state(fn (User $record) => trim($record->address.' '.$record->city)) ->placeholder('—')->columnSpan(2),
                TextEntry::make('created_at')->label('Üyelik')->date('d.m.Y'),
            ]),
            Section::make('Siparişler')->schema([
                RepeatableEntry::make('orders')->hiddenLabel()->table([
                    TableColumn::make('Sipariş'), TableColumn::make('Tarih'), TableColumn::make('Tutar'), TableColumn::make('Durum'),
                ])->schema([
                    TextEntry::make('number')->url(fn ($record) => OrderResource::getUrl('view', ['record' => $record])),
                    TextEntry::make('created_at')->date('d.m.Y'),
                    TextEntry::make('total')->formatStateUsing(fn ($state) => money($state)),
                    TextEntry::make('status')->badge()->formatStateUsing(fn ($state) => Order::STATUSES[$state] ?? $state)->color(fn ($state) => OrderResource::statusColor($state)),
                ])->placeholder('Henüz sipariş yok'),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageUsers::route('/')];
    }
}
