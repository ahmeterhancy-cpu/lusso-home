<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

/** Ürün, kategori, sayfa ve blog formlarında ortak "Arama motoru (SEO)" bölümü. */
class SeoFields
{
    public static function section(): Section
    {
        return Section::make('Arama motoru (SEO)')->icon('heroicon-o-magnifying-glass')->collapsible()->collapsed()->columnSpanFull()
            ->description('Boş bırakılırsa ad ve açıklamadan otomatik üretilir.')
            ->schema([
                TextInput::make('meta_title')->label('Sayfa başlığı (Google)')->maxLength(70)
                    ->hint(fn ($state) => mb_strlen((string) $state).'/60')->helperText('İdeal: 50–60 karakter'),
                Textarea::make('meta_description')->label('Açıklama (Google)')->rows(2)->maxLength(300)
                    ->hint(fn ($state) => mb_strlen((string) $state).'/155')->helperText('İdeal: 120–155 karakter')->live(onBlur: true),
            ]);
    }
}
