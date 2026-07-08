<?php

namespace App\Filament\Admin\Resources\SystemRatings\Pages;

use App\Filament\Admin\Resources\SystemRatings\SystemRatingResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSystemRating extends ViewRecord
{
    protected static string $resource = SystemRatingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToRatings')
                ->label('Kembali ke Rating')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn (): string => SystemRatingResource::getUrl('index')),

            DeleteAction::make()
                ->label('Hapus Rating')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation(),
        ];
    }

    public function getTitle(): string
    {
        return 'Detail Rating Sistem';
    }
}
