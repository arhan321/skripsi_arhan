<?php

namespace App\Filament\Admin\Resources\TouristDestinations\Pages;

use App\Filament\Admin\Resources\TouristDestinations\TouristDestinationResource;
use App\Services\TourHubMlService;
use Filament\Resources\Pages\CreateRecord;

class CreateTouristDestination extends CreateRecord
{
    protected static string $resource = TouristDestinationResource::class;

    protected function afterCreate(): void
    {
        app(TourHubMlService::class)->reloadDatasetSilently();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
