<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\SheetSync;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use UnitEnum;

/** Google Drive'daki Excel / E-Tablo'dan otomatik fiyat ve stok güncellemesi. */
class ManageSheetSync extends Page implements HasSchemas
{
    use \App\Filament\Support\RestrictedArea;

    protected static string $area = 'catalog';

    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cloud-arrow-down';

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Drive ile Fiyat / Stok';

    protected static ?string $navigationLabel = 'Drive ile Fiyat / Stok';

    protected string $view = 'filament.pages.sheet-sync';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'sheet_url' => setting('sheet_url'),
            'sheet_enabled' => setting('sheet_enabled') === '1',
            'sheet_interval' => setting('sheet_interval', '60'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dosya bağlantısı')->schema([
                TextInput::make('sheet_url')->label('Google Drive / E-Tablo bağlantısı')->url()->maxLength(500)
                    ->placeholder('https://docs.google.com/spreadsheets/d/… veya https://drive.google.com/file/d/…')
                    ->helperText('Drive\'da dosyaya sağ tık → Paylaş → Genel erişim: "Bağlantıya sahip olan herkes" (Görüntüleyen) → Bağlantıyı kopyala.'),
                Toggle::make('sheet_enabled')->label('Otomatik güncelleme açık'),
                Select::make('sheet_interval')->label('Ne sıklıkla?')->options([
                    '15' => '15 dakikada bir', '30' => '30 dakikada bir', '60' => 'Saatte bir', '180' => '3 saatte bir',
                    '360' => '6 saatte bir', '720' => 'Günde iki kez', '1440' => 'Günde bir',
                ])->default('60'),
            ]),
        ])->statePath('data');
    }

    public function save(): void
    {
        $d = $this->form->getState();
        Setting::put([
            'sheet_url' => trim((string) $d['sheet_url']),
            'sheet_enabled' => ! empty($d['sheet_enabled']) ? '1' : '0',
            'sheet_interval' => (string) ($d['sheet_interval'] ?? '60'),
        ]);
        Notification::make()->title('Kaydedildi')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('run')->label('Şimdi güncelle')->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()->modalDescription('Dosya şimdi indirilir ve fiyat / stoklar güncellenir.')
                ->action(function () {
                    $this->save();
                    $r = app(SheetSync::class)->run(true);
                    if (! $r) {
                        Notification::make()->title('Önce dosya bağlantısını girin')->warning()->send();

                        return;
                    }
                    Notification::make()->title($r['ok'] ? 'Güncelleme tamamlandı' : 'Güncelleme yapılamadı')->body($r['summary'])
                        ->{$r['ok'] ? 'success' : 'danger'}()->persistent()->send();
                }),
        ];
    }

    public function lastResult(): ?array
    {
        return json_decode((string) setting('sheet_last_result'), true) ?: null;
    }
}
