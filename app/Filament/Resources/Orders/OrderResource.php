<?php

namespace App\Filament\Resources\Orders;

use App\Models\Order;
use App\Services\OrderService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|UnitEnum|null $navigationGroup = 'Satış';

    protected static ?int $navigationSort = 0;

    protected static ?string $modelLabel = 'Sipariş';

    protected static ?string $pluralModelLabel = 'Siparişler';

    protected static ?string $recordTitleAttribute = 'number';

    public static function getGloballySearchableAttributes(): array
    {
        return ['number', 'email', 'phone', 'last_name'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return ['Müşteri' => $record->first_name.' '.$record->last_name, 'Tutar' => money($record->total)];
    }

    public static function getNavigationBadge(): ?string
    {
        $n = Order::whereIn('status', ['pending', 'processing'])->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function statusColor(?string $state): string
    {
        return match ($state) {
            'pending' => 'warning',
            'processing' => 'info',
            'shipped' => 'primary',
            'completed' => 'success',
            default => 'danger',
        };
    }

    public static function paymentLabel(Order $o): string
    {
        return match ($o->payment_status) {
            'paid' => 'Ödendi',
            'failed' => 'Başarısız',
            'refunded' => 'İade edildi',
            default => 'Bekliyor',
        };
    }

    /** Liste satırında ve ayrıntı sayfasında ortak işlemler. */
    public static function orderActions(): array
    {
        return [
            Action::make('status')->label('Durumu değiştir')->icon('heroicon-o-arrow-path')->color('primary')
                ->modalHeading(fn (Order $record) => $record->number.' — durum')
                ->fillForm(fn (Order $record) => ['status' => $record->status, 'tracking' => $record->tracking_no, 'notify' => true])
                ->schema([
                    Select::make('status')->label('Yeni durum')->options(Order::STATUSES)->required()->live(),
                    TextInput::make('tracking')->label('Kargo takip numarası')->visible(fn ($get) => $get('status') === 'shipped'),
                    Textarea::make('note')->label('Müşteriye not (isteğe bağlı)')->rows(3),
                    Toggle::make('notify')->label('Müşteriye e-posta gönder')->default(true),
                ])
                ->action(function (Order $record, array $data) {
                    app(OrderService::class)->changeStatus($record, $data['status'], $data['note'] ?? null, (bool) $data['notify'], $data['tracking'] ?? null);
                    Notification::make()->title('Sipariş durumu güncellendi')->success()->send();
                }),
            Action::make('paid')->label('Ödeme alındı')->icon('heroicon-o-banknotes')->color('success')
                ->visible(fn (Order $record) => $record->payment_status !== 'paid' && in_array($record->payment_method, ['havale', 'kapida'], true))
                ->requiresConfirmation()->modalDescription('Havale / kapıda ödemenin alındığını onaylıyor musunuz? Sipariş "Hazırlanıyor" durumuna geçer.')
                ->action(function (Order $record) {
                    app(OrderService::class)->markPaid($record, 'elle', 'Ödeme yönetici tarafından onaylandı ('.auth()->user()->name.').');
                    Notification::make()->title('Ödeme alındı olarak işaretlendi')->success()->send();
                }),
            Action::make('whatsapp')->label('WhatsApp')->icon('heroicon-o-chat-bubble-left-right')->color('gray')
                ->url(fn (Order $record) => 'https://wa.me/'.self::waNumber($record->phone).'?text='.rawurlencode('Merhaba '.$record->first_name.', Lusso Home Concept\'ten yazıyoruz. '.$record->number.' numaralı siparişiniz hakkında'), true),
            Action::make('print')->label('Yazdır')->icon('heroicon-o-printer')->color('gray')
                ->url(fn (Order $record) => route('admin.order.print', $record), true),
        ];
    }

    /** KKTC/TR numarasını wa.me biçimine çevirir (0533… → 90533…). */
    public static function waNumber(?string $phone): string
    {
        $d = preg_replace('/\D/', '', (string) $phone);
        if (str_starts_with($d, '00')) {
            $d = substr($d, 2);
        }
        if (str_starts_with($d, '0')) {
            $d = '90'.substr($d, 1);
        } elseif (strlen($d) === 10) {
            $d = '90'.$d;
        }

        return $d;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 3])->columnSpanFull()->schema([
                Section::make('Ürünler')->icon('heroicon-o-shopping-cart')->columnSpan(['lg' => 2])->schema([
                    RepeatableEntry::make('items')->hiddenLabel()->table([
                        TableColumn::make(''), TableColumn::make('Ürün'), TableColumn::make('Fiyat'), TableColumn::make('Adet'), TableColumn::make('Toplam'),
                    ])->schema([
                        TextEntry::make('thumb')->hiddenLabel()->state(fn ($record) => $record->product?->mainImage())
                            ->formatStateUsing(fn ($state) => $state ? new HtmlString('<img src="'.e($state).'" class="lusso-thumb" alt="">') : '—'),
                        TextEntry::make('name')->weight(FontWeight::Medium)->belowContent(fn ($record) => $record->sku)
                            ->url(fn ($record) => $record->product ? route('product', $record->product->slug) : null, true),
                        TextEntry::make('price')->formatStateUsing(fn ($state) => money($state)),
                        TextEntry::make('quantity'),
                        TextEntry::make('total')->formatStateUsing(fn ($state) => money($state))->weight(FontWeight::Bold),
                    ]),
                    Grid::make(4)->schema([
                        TextEntry::make('subtotal')->label('Ara toplam')->formatStateUsing(fn ($state) => money($state)),
                        TextEntry::make('discount')->label('İndirim')->color('success')
                            ->formatStateUsing(fn ($state) => (float) $state > 0 ? '−'.money($state) : '—')
                            ->belowContent(fn (Order $record) => collect((array) $record->discount_lines)->pluck('label')->join(', ') ?: null),
                        TextEntry::make('shipping')->label('Kargo')->formatStateUsing(fn ($state) => (float) $state > 0 ? money($state) : 'Ücretsiz'),
                        TextEntry::make('total')->label('Genel toplam')->formatStateUsing(fn ($state) => money($state))
                            ->size(TextSize::Large)->weight(FontWeight::Bold)->color('primary'),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    Section::make('Durum')->icon('heroicon-o-flag')->schema([
                        TextEntry::make('status')->label('Sipariş')->badge()->formatStateUsing(fn ($state) => Order::STATUSES[$state] ?? $state)->color(fn ($state) => self::statusColor($state)),
                        TextEntry::make('payment_status')->label('Ödeme')->badge()
                            ->formatStateUsing(fn ($state, Order $record) => self::paymentLabel($record).' · '.$record->methodLabel())
                            ->color(fn ($state) => $state === 'paid' ? 'success' : ($state === 'failed' ? 'danger' : 'warning')),
                        TextEntry::make('payment_ref')->label('Ödeme referansı')->placeholder('—')->copyable(),
                        TextEntry::make('tracking_no')->label('Kargo takip no')->placeholder('—')->copyable(),
                        TextEntry::make('created_at')->label('Sipariş tarihi')->dateTime('d.m.Y H:i'),
                        TextEntry::make('paid_at')->label('Ödeme tarihi')->dateTime('d.m.Y H:i')->placeholder('—'),
                    ]),
                    Section::make('Müşteri')->icon('heroicon-o-user')->schema([
                        TextEntry::make('customer')->hiddenLabel()->state(fn (Order $record) => $record->first_name.' '.$record->last_name)->weight(FontWeight::Bold)
                            ->belowContent(fn (Order $record) => $record->user_id ? 'Üye' : 'Misafir'),
                        TextEntry::make('phone')->label('Telefon')->url(fn (Order $record) => 'tel:'.preg_replace('/[^0-9+]/', '', $record->phone))->copyable(),
                        TextEntry::make('email')->label('E-posta')->url(fn (Order $record) => 'mailto:'.$record->email)->copyable(),
                        TextEntry::make('address')->label('Teslimat adresi')->state(fn (Order $record) => $record->address.', '.$record->city)
                            ->url(fn (Order $record) => 'https://www.google.com/maps/search/'.rawurlencode($record->address.' '.$record->city.' Kıbrıs'), true),
                        TextEntry::make('note')->label('Müşteri notu')->placeholder('—'),
                        TextEntry::make('admin_note')->label('İç not')->placeholder('—'),
                    ]),
                ]),
            ]),
            Section::make('Sipariş geçmişi')->icon('heroicon-o-clock')->columnSpanFull()->collapsible()->schema([
                RepeatableEntry::make('events')->hiddenLabel()->contained(false)->schema([
                    TextEntry::make('message')->hiddenLabel()->prefix(fn ($record) => $record->created_at->format('d.m.Y H:i').' — '),
                ]),
            ]),
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Müşteri ve teslimat')->columns(2)->schema([
                TextInput::make('first_name')->label('Ad')->required(),
                TextInput::make('last_name')->label('Soyad')->required(),
                TextInput::make('email')->label('E-posta')->email()->required(),
                TextInput::make('phone')->label('Telefon')->required(),
                TextInput::make('address')->label('Adres')->required()->columnSpanFull(),
                TextInput::make('city')->label('Şehir')->required(),
                TextInput::make('tracking_no')->label('Kargo takip no'),
                Textarea::make('note')->label('Müşteri notu')->columnSpanFull(),
                Textarea::make('admin_note')->label('İç not (müşteri görmez)')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('items'))
            ->recordUrl(fn (Order $record) => self::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('number')->label('Sipariş')->searchable()->weight(FontWeight::Bold)
                    ->description(fn (Order $record) => $record->created_at->format('d.m.Y H:i')),
                TextColumn::make('customer')->label('Müşteri')->state(fn (Order $record) => $record->first_name.' '.$record->last_name)
                    ->description(fn (Order $record) => $record->phone.' · '.$record->city)->searchable(['first_name', 'last_name', 'email', 'phone']),
                TextColumn::make('items_count')->label('Ürün')->suffix(' kalem')->toggleable(),
                TextColumn::make('total')->label('Tutar')->formatStateUsing(fn ($state) => money($state))->sortable()->weight(FontWeight::Bold),
                TextColumn::make('payment_method')->label('Ödeme')->formatStateUsing(fn ($state) => Order::METHODS[$state] ?? $state)
                    ->description(fn (Order $record) => self::paymentLabel($record))
                    ->color(fn (Order $record) => $record->payment_status === 'paid' ? 'success' : null),
                TextColumn::make('status')->label('Durum')->badge()->formatStateUsing(fn ($state) => Order::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => self::statusColor($state)),
            ])
            ->filters([
                SelectFilter::make('payment_method')->label('Ödeme yöntemi')->options(Order::METHODS),
                SelectFilter::make('city')->label('Şehir')->options(fn () => Order::query()->distinct()->orderBy('city')->pluck('city', 'city')->all()),
                Filter::make('date')->label('Tarih')->schema([
                    DatePicker::make('from')->label('Başlangıç'),
                    DatePicker::make('until')->label('Bitiş'),
                ])->columns(2)->query(fn (Builder $query, array $data) => $query
                    ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                    ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))),
            ])
            ->recordActions([
                ViewAction::make()->label('Aç'),
                \Filament\Actions\ActionGroup::make([...self::orderActions(), EditAction::make()->label('Bilgileri düzenle')]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('csv')->label('CSV olarak indir')->icon('heroicon-o-arrow-down-tray')
                        ->action(fn (Collection $records) => response()->streamDownload(function () use ($records) {
                            $out = fopen('php://output', 'w');
                            fwrite($out, "\xEF\xBB\xBF"); // Excel için UTF-8 BOM
                            fputcsv($out, ['Sipariş', 'Tarih', 'Ad', 'Soyad', 'Telefon', 'E-posta', 'Şehir', 'Adres', 'Ödeme', 'Ödeme durumu', 'Durum', 'Tutar'], ';');
                            foreach ($records as $o) {
                                fputcsv($out, [$o->number, $o->created_at->format('d.m.Y H:i'), $o->first_name, $o->last_name, $o->phone, $o->email, $o->city, $o->address,
                                    $o->methodLabel(), self::paymentLabel($o), $o->statusLabel(), number_format((float) $o->total, 2, ',', '')], ';');
                            }
                            fclose($out);
                        }, 'siparisler-'.now()->format('Ymd-His').'.csv')),
                    BulkAction::make('bulk_status')->label('Durumu değiştir')->icon('heroicon-o-arrow-path')
                        ->schema([
                            Select::make('status')->label('Yeni durum')->options(Order::STATUSES)->required(),
                            Toggle::make('notify')->label('Müşterilere e-posta gönder'),
                        ])
                        ->action(function (Collection $records, array $data) {
                            foreach ($records as $o) {
                                app(OrderService::class)->changeStatus($o, $data['status'], null, (bool) $data['notify']);
                            }
                            Notification::make()->title($records->count().' sipariş güncellendi')->success()->send();
                        })->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
