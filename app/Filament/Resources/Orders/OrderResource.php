<?php

namespace App\Filament\Resources\Orders;

use App\Models\Order;
use App\Services\OrderService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|UnitEnum|null $navigationGroup = 'Satış';

    protected static ?string $modelLabel = 'Sipariş';

    protected static ?string $pluralModelLabel = 'Siparişler';

    protected static ?string $recordTitleAttribute = 'number';

    public static function getNavigationBadge(): ?string
    {
        $n = Order::whereIn('status', ['pending', 'processing'])->count();

        return $n ? (string) $n : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Durum')->columns(2)->columnSpan(1)->schema([
                Select::make('status')->label('Sipariş durumu')->options(Order::STATUSES)->required(),
                Select::make('payment_status')->label('Ödeme')->options(['unpaid' => 'Ödenmedi', 'paid' => 'Ödendi', 'failed' => 'Başarısız', 'refunded' => 'İade edildi'])->required(),
                TextInput::make('payment_ref')->label('Ödeme referansı')->columnSpanFull(),
            ]),
            Section::make('Müşteri ve teslimat')->columns(2)->columnSpan(2)->schema([
                TextInput::make('first_name')->label('Ad')->required(),
                TextInput::make('last_name')->label('Soyad')->required(),
                TextInput::make('email')->label('E-posta')->email()->required(),
                TextInput::make('phone')->label('Telefon')->required(),
                TextInput::make('address')->label('Adres')->required()->columnSpanFull(),
                TextInput::make('city')->label('Şehir')->required(),
                Textarea::make('note')->label('Müşteri notu')->columnSpanFull(),
            ]),
            Section::make('Ürünler')->columnSpanFull()->schema([
                RepeatableEntry::make('items')->label('')->table([
                    \Filament\Infolists\Components\RepeatableEntry\TableColumn::make('Ürün'),
                    \Filament\Infolists\Components\RepeatableEntry\TableColumn::make('Stok kodu'),
                    \Filament\Infolists\Components\RepeatableEntry\TableColumn::make('Fiyat'),
                    \Filament\Infolists\Components\RepeatableEntry\TableColumn::make('Adet'),
                    \Filament\Infolists\Components\RepeatableEntry\TableColumn::make('Toplam'),
                ])->schema([
                    TextEntry::make('name'),
                    TextEntry::make('sku')->placeholder('—'),
                    TextEntry::make('price')->formatStateUsing(fn ($state) => money($state)),
                    TextEntry::make('quantity'),
                    TextEntry::make('total')->formatStateUsing(fn ($state) => money($state)),
                ]),
                TextEntry::make('totals')->label('')->state(fn (Order $record) => 'Ara toplam: '.money($record->subtotal).' · Kargo: '.money($record->shipping).' · TOPLAM: '.money($record->total)),
            ]),
            Section::make('Geçmiş')->columnSpanFull()->collapsed()->schema([
                RepeatableEntry::make('events')->label('')->schema([
                    TextEntry::make('message')->label('')->prefix(fn ($record) => $record->created_at->format('d.m.Y H:i').' — '),
                ]),
            ]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('number')->label('Sipariş')->searchable()->weight('bold')
                    ->description(fn (Order $record) => $record->created_at->format('d.m.Y H:i')),
                TextColumn::make('customer')->label('Müşteri')->state(fn (Order $record) => $record->first_name.' '.$record->last_name)
                    ->description(fn (Order $record) => $record->phone)->searchable(['first_name', 'last_name', 'email', 'phone']),
                TextColumn::make('total')->label('Tutar')->formatStateUsing(fn ($state) => money($state))->sortable(),
                TextColumn::make('payment_method')->label('Ödeme')->formatStateUsing(fn ($state) => Order::METHODS[$state] ?? $state)
                    ->description(fn (Order $record) => $record->payment_status === 'paid' ? 'Ödendi' : ($record->payment_status === 'failed' ? 'Başarısız' : 'Bekliyor')),
                TextColumn::make('status')->label('Durum')->badge()->formatStateUsing(fn ($state) => Order::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning', 'processing' => 'info', 'shipped' => 'primary',
                        'completed' => 'success', default => 'danger',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')->label('Durum')->options(Order::STATUSES),
                SelectFilter::make('payment_method')->label('Ödeme yöntemi')->options(Order::METHODS),
            ])
            ->recordActions([
                Action::make('paid')->label('Ödendi')->icon('heroicon-o-banknotes')->color('success')
                    ->visible(fn (Order $record) => $record->payment_status !== 'paid' && in_array($record->payment_method, ['havale', 'kapida'], true))
                    ->requiresConfirmation()->modalDescription('Havale/kapıda ödemenin alındığını onaylıyor musunuz?')
                    ->action(function (Order $record) {
                        app(OrderService::class)->markPaid($record, 'elle', 'Ödeme yönetici tarafından onaylandı ('.auth()->user()->name.').');
                        Notification::make()->title('Ödeme alındı olarak işaretlendi')->success()->send();
                    }),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
