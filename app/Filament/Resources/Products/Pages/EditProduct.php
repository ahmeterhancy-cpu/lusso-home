<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('view')->label('Sitede gör')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                ->url(fn () => route('product', $this->record->slug), true),
            DeleteAction::make(),
        ];
    }
}
