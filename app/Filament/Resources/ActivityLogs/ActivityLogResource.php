<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Models\ActivityLog;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/** Panelde kim, ne zaman, neyi değiştirdi (salt okunur). */
class ActivityLogResource extends Resource
{
    use \App\Filament\Support\RestrictedArea;

    protected static string $area = 'settings';

    protected static ?string $model = ActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'Ayarlar';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Kayıt';

    protected static ?string $pluralModelLabel = 'İşlem Günlüğü';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('created_at')->label('Zaman')->dateTime('d.m.Y H:i:s'),
            TextEntry::make('user.name')->label('Kişi')->placeholder('Sistem'),
            TextEntry::make('action')->label('İşlem')->formatStateUsing(fn ($state) => ActivityLog::ACTIONS[$state] ?? $state),
            TextEntry::make('subject_type')->label('Kayıt türü')->formatStateUsing(fn ($state) => ActivityLog::SUBJECTS[$state] ?? '—'),
            TextEntry::make('subject_label')->label('Kayıt')->columnSpanFull(),
            KeyValueEntry::make('changes')->label('Değişiklikler')->columnSpanFull()
                ->state(fn (ActivityLog $record) => collect((array) $record->changes)->mapWithKeys(fn ($v, $k) => [$k => is_array($v) ? (($v['eski'] ?? '') === '' ? '—' : $v['eski']).'  →  '.(($v['yeni'] ?? '') === '' ? '—' : $v['yeni']) : (string) $v])->all())
                ->keyLabel('Alan')->valueLabel('Eski → Yeni'),
            TextEntry::make('ip')->label('IP')->placeholder('—'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                TextColumn::make('created_at')->label('Zaman')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('user.name')->label('Kişi')->placeholder('Sistem'),
                TextColumn::make('action')->label('İşlem')->badge()->formatStateUsing(fn ($state) => ActivityLog::ACTIONS[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'created' => 'success', 'deleted' => 'danger', 'updated' => 'info', default => 'gray' }),
                TextColumn::make('subject_type')->label('Tür')->formatStateUsing(fn ($state) => ActivityLog::SUBJECTS[$state] ?? '—'),
                TextColumn::make('subject_label')->label('Kayıt')->searchable()->limit(60)
                    ->description(fn (ActivityLog $record) => $record->changes ? collect(array_keys((array) $record->changes))->take(4)->join(', ') : null),
            ])
            ->filters([
                SelectFilter::make('action')->label('İşlem')->options(ActivityLog::ACTIONS),
                SelectFilter::make('subject_type')->label('Tür')->options(ActivityLog::SUBJECTS),
                SelectFilter::make('user_id')->label('Kişi')->relationship('user', 'name', fn ($query) => $query->where('is_admin', true)),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListActivityLogs::route('/')];
    }
}
