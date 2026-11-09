<?php

namespace App\Filament\Resources\Orders;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class ManualOrderForm
{
    public static function schema(): array
    {
        return [
            Section::make('Ürünler')->schema([
                Repeater::make('items')->hiddenLabel()->minItems(1)->defaultItems(1)->addActionLabel('Ürün ekle')
                    ->schema([
                        Select::make('product_id')->label('Ürün')->required()->searchable()->columnSpan(3)
                            ->getSearchResultsUsing(fn (string $search) => Product::query()
                                ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                                ->limit(30)->get()->mapWithKeys(fn (Product $p) => [$p->id => $p->name.($p->sku ? ' ('.$p->sku.')' : '').' — '.money($p->finalPrice())])->all())
                            ->getOptionLabelUsing(fn ($value) => Product::find($value)?->name)
                            ->live()
                            ->afterStateUpdated(fn ($state, Set $set) => $set('price', $state ? Product::find($state)?->finalPrice() : null)),
                        TextInput::make('quantity')->label('Adet')->numeric()->minValue(1)->default(1)->required(),
                        TextInput::make('price')->label('Birim fiyat (₺)')->numeric()->minValue(0)->required()->columnSpan(2),
                    ])->columns(6),
                TextInput::make('discount')->label('İndirim (₺)')->numeric()->minValue(0)->default(0),
                TextInput::make('shipping')->label('Kargo (₺)')->numeric()->minValue(0)->default(0),
            ])->columns(2),
            Section::make('Müşteri')->columns(3)->schema([
                Select::make('user_id')->label('Üye (isteğe bağlı)')->searchable()->columnSpanFull()
                    ->getSearchResultsUsing(fn (string $search) => User::where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")->limit(20)->pluck('name', 'id')->all())
                    ->getOptionLabelUsing(fn ($value) => User::find($value)?->name)
                    ->live()->afterStateUpdated(function ($state, Set $set) {
                        if ($u = User::find($state)) {
                            $set('first_name', explode(' ', $u->name)[0]);
                            $set('last_name', trim(\Illuminate\Support\Str::after($u->name, ' ')) ?: '-');
                            $set('email', $u->email);
                            $set('phone', $u->phone);
                            $set('address', $u->address);
                            $set('city', $u->city);
                        }
                    }),
                TextInput::make('first_name')->label('Ad')->required(),
                TextInput::make('last_name')->label('Soyad')->required(),
                TextInput::make('phone')->label('Telefon')->required(),
                TextInput::make('email')->label('E-posta')->email(),
                TextInput::make('address')->label('Adres')->placeholder('Boş = mağazadan teslim'),
                TextInput::make('city')->label('Şehir'),
            ]),
            Section::make('Ödeme ve durum')->columns(3)->schema([
                Select::make('payment_method')->label('Ödeme yöntemi')->options(Order::METHODS + ['magaza' => 'Mağazada ödendi (nakit / POS)'])->required()->default('magaza')->live()
                    ->afterStateUpdated(fn ($state, Set $set) => $state === 'magaza' ? $set('paid', true) : null),
                Toggle::make('paid')->label('Ödeme alındı')->default(true)->inline(false),
                Select::make('status')->label('Durum')->options(Order::STATUSES)->default('processing'),
                Textarea::make('note')->label('Müşteri notu')->rows(2),
                Textarea::make('admin_note')->label('İç not')->rows(2)->columnSpan(2),
            ]),
        ];
    }
}
