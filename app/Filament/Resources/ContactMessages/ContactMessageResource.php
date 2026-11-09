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
    use \App\Filament\Support\RestrictedArea;

    protected static string $area = 'sales';

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
            TextEntry::make('reply')->label('Yanıtınız')->columnSpanFull()->placeholder('Henüz yanıtlanmadı')
                ->belowContent(fn (ContactMessage $record) => $record->replied_at ? 'Gönderildi: '.$record->replied_at->format('d.m.Y H:i') : null),
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
                TextColumn::make('replied_at')->label('Yanıt')->since()->placeholder('Bekliyor')->badge()->color(fn ($state) => $state ? 'success' : 'warning'),
                TextColumn::make('created_at')->label('Tarih')->since()->sortable(),
            ])
            ->recordActions([
                ViewAction::make()->after(fn (ContactMessage $record) => $record->update(['is_read' => true])),
                \Filament\Actions\Action::make('reply')->label('Yanıtla')->icon('heroicon-o-paper-airplane')->color('primary')
                    ->modalHeading(fn (ContactMessage $record) => $record->name.' adlı kişiye yanıt')
                    ->modalDescription(fn (ContactMessage $record) => '“'.\Illuminate\Support\Str::limit($record->message, 200).'”')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('subject')->label('Konu')->default('Lusso Home Concept — mesajınız hakkında')->required(),
                        \Filament\Forms\Components\Textarea::make('body')->label('Yanıt')->rows(8)->required()
                            ->default(fn (ContactMessage $record) => "Merhaba {$record->name},\n\n\n\nSaygılarımızla,\nLusso Home Concept\n".setting('support_phone', '+90 539 109 99 00')),
                    ])
                    ->action(function (ContactMessage $record, array $data) {
                        try {
                            \Illuminate\Support\Facades\Mail::raw($data['body'], fn ($m) => $m->to($record->email, $record->name)->subject($data['subject'])
                                ->replyTo(setting('email', 'info@lussohomeconcept.com')));
                        } catch (\Throwable $e) {
                            \Filament\Notifications\Notification::make()->title('E-posta gönderilemedi')->body($e->getMessage())->danger()->send();

                            return;
                        }
                        $record->update(['reply' => $data['body'], 'replied_at' => now(), 'is_read' => true]);
                        \Filament\Notifications\Notification::make()->title('Yanıt gönderildi')->success()->send();
                    }),
                \Filament\Actions\Action::make('wa')->label('WhatsApp')->icon('heroicon-o-chat-bubble-left-right')->color('gray')
                    ->visible(fn (ContactMessage $record) => filled($record->phone))
                    ->url(fn (ContactMessage $record) => 'https://wa.me/'.\App\Filament\Resources\Orders\OrderResource::waNumber($record->phone), true),
                DeleteAction::make()->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageContactMessages::route('/')];
    }
}
