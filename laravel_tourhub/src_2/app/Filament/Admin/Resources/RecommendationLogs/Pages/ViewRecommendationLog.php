<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\RecommendationLogs\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Admin\Resources\RecommendationLogs\RecommendationLogResource;

class ViewRecommendationLog extends ViewRecord
{
    protected static string $resource = RecommendationLogResource::class;

    // protected string $view = 'filament.admin.resources.recommendation-logs.pages.show';
    protected string $view = 'filament.admin.resources.recommendation-logs.pages.show';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToList')
                ->label('Kembali ke Log')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn (): string => RecommendationLogResource::getUrl('index')),
        ];
    }
}