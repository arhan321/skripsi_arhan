<?php

namespace App\Filament\Admin\Resources\TouristDestinations\Pages;

use App\Filament\Admin\Resources\TouristDestinations\TouristDestinationResource;
use App\Services\TourHubMlService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTouristDestination extends EditRecord
{
    protected static string $resource = TouristDestinationResource::class;

    protected function afterSave(): void
    {
        app(TourHubMlService::class)->reloadDatasetSilently();
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Hapus')
                ->after(fn () => app(TourHubMlService::class)->reloadDatasetSilently()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
