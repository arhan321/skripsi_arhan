<?php

namespace App\Filament\Admin\Resources\TouristDestinations\Pages;

use App\Filament\Admin\Resources\TouristDestinations\TouristDestinationResource;
use App\Services\TourHubMlService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Throwable;

class ListTouristDestinations extends ListRecords
{
    protected static string $resource = TouristDestinationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Destinasi Wisata'),

            Action::make('reloadMlDataset')
                ->label('Reload Dataset FastAPI')
                ->requiresConfirmation()
                ->modalHeading('Reload dataset FastAPI?')
                ->modalDescription('Aksi ini akan meminta FastAPI membaca ulang data destinasi aktif dari database Laravel.')
                ->action(function (): void {
                    try {
                        app(TourHubMlService::class)->reloadDataset();

                        Notification::make()
                            ->title('Dataset FastAPI berhasil di-reload')
                            ->success()
                            ->send();
                    } catch (Throwable $e) {
                        report($e);

                        Notification::make()
                            ->title('Gagal reload dataset FastAPI')
                            ->body('Data Laravel tetap tersimpan. Cek koneksi FastAPI atau konfigurasi TOURHUB_ML_BASE_URL.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
