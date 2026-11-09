<?php

namespace App\Filament\Resources\XmlFeeds\Pages;

use App\Filament\Resources\XmlFeeds\XmlFeedResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageXmlFeeds extends ManageRecords
{
    protected static string $resource = XmlFeedResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('XML akışı ekle')->modalWidth('4xl')];
    }
}
