<?php

namespace App\Filament\Resources\ContactMessages;

use App\Models\ContactMessage;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static string|UnitEnum|null $navigationGroup = 'Satış';

    protected static ?string $modelLabel = 'Mesaj';

    protected static ?string $pluralModelLabel = 'İletişim Mesajları';

    public static function getNavigationBadge(): ?string
    {
        $n = ContactMessage::where('is_read', false)->count();

        return $n ? (string) $n : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name')->label('Ad soyad'),
            TextEntry::make('email')->label('E-posta')->copyable(),
            TextEntry::make('phone')->label('Telefon')->placeholder('—'),
            TextEntry::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i'),
            TextEntry::make('message')->label('Mesaj')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('is_read')->label('')->boolean()->trueIcon('heroicon-o-envelope-open')->falseIcon('heroicon-s-envelope')->falseColor('warning'),
                TextColumn::make('name')->label('Ad')->searchable()->description(fn (ContactMessage $record) => $record->email),
                TextColumn::make('message')->label('Mesaj')->limit(70),
                TextColumn::make('created_at')->label('Tarih')->since()->sortable(),
            ])
            ->recordActions([
                ViewAction::make()->after(fn (ContactMessage $record) => $record->update(['is_read' => true])),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageContactMessages::route('/')];
    }
}
