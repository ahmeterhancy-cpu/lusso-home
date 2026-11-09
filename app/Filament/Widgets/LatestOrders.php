<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrders extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 2];

    public function table(Table $table): Table
    {
        return $table
            ->heading('Son siparişler')
            ->query(Order::query()->latest()->limit(8))
            ->paginated(false)
            ->recordUrl(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('number')->label('Sipariş')->weight('bold')->description(fn (Order $record) => $record->created_at->diffForHumans()),
                TextColumn::make('customer')->label('Müşteri')->state(fn (Order $record) => $record->first_name.' '.$record->last_name)->description(fn (Order $record) => $record->city),
                TextColumn::make('total')->label('Tutar')->formatStateUsing(fn ($state) => money($state)),
                TextColumn::make('payment_method')->label('Ödeme')->formatStateUsing(fn ($state) => Order::methodName($state))
                    ->description(fn (Order $record) => $record->payment_status === 'paid' ? 'Ödendi' : 'Bekliyor'),
                TextColumn::make('status')->label('Durum')->badge()->formatStateUsing(fn ($state) => Order::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => OrderResource::statusColor($state)),
            ]);
    }
}
